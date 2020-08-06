<?php

namespace Dena\IranPayment\Gateways;

use Dena\IranPayment\Traits\UserData;
use Dena\IranPayment\Traits\PaymentData;
use Dena\IranPayment\Traits\TransactionData;

use Exception;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\SucceedRetryException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\TransactionFailedException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Exceptions\GatewayPaymentNotSupportViewException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\Helpers\Currency;

use Illuminate\Support\Facades\View;

/**
 * @method getName()
 * @method purchase()
 * @method purchaseUri()
 * @method verify()
 * @method gatewayPayView()
 */
abstract class AbstractGateway
{
	use UserData,
        PaymentData,
        TransactionData;

    /**
     * Request variable
     *
     * @var array
     */
	protected array $request;

    /**
     * Gateway Request Options variable
     *
     * @var array
     */
    protected array $gateway_request_options = [];

    /**
     * Boot Gateway function
     *
     * @param array $parameters
     * @return $this
     * @throws InvalidDataException
     */
    public function initialize(array $parameters = []): self
    {
        $this->setRequest($parameters['request'] ?? app('request')->all());

        $this->setCurrency($parameters['currency'] ?? app('config')->get('iranpayment.currency', Currency::IRR));

        $this->setCallbackUrl($parameters['callback_url'] ?? app('config')->get('iranpayment.callback-url'));

        $this->setGatewayRequestOptions(array_merge(
            [
                'timeout' => app('config')->get('iranpayment.timeout', 30),
                'connection_timeout' => app('config')->get('iranpayment.connection_timeout', 60),
            ],
            $parameters['gateway_request_options'] ?? [],
        ));

        return $this;
    }

	/**
	 * Set Request function
	 *
	 * @param array $request
	 * @return $this
	 */
	public function setRequest(array $request): self
	{
		$this->request = $request;

		return $this;
	}

    /**
     * Get Request function
     *
     * @return array
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Set Gateway Request Options function
     *
     * @param array $options
     * @return $this
     */
    public function setGatewayRequestOptions(array $options): self
    {
        $this->gateway_request_options = $options;

        return $this;
    }

    /**
     * Get Gateway Request Options function
     *
     * @return array
     */
    public function getGatewayRequestOptions(): array
    {
        return $this->gateway_request_options;
    }

    /**
     * @throws InvalidDataException
     */
    protected function prePurchase(): void
    {
        if ($this->preparedAmount() <= 0) {
            throw InvalidDataException::invalidAmount();
        }

        if (!in_array($this->getCurrency(), [Currency::IRR, Currency::IRT])) {
            throw InvalidDataException::invalidCurrency();
        }

        if (filter_var($this->preparedCallbackUrl(), FILTER_VALIDATE_URL) === false) {
            throw InvalidDataException::invalidCallbackUrl();
        }

        $this->newTransaction();
    }

    protected function postPurchase(): void
    {

    }

    /**
     * Pay function
     *
     * @return $this
     * @throws IranPaymentException
     */
    public function ready(): self
	{
		try {
            $this->prePurchase();

            $this->purchase();

            $this->postPurchase();
		} catch (Exception $ex) {
            if ($this->getTransaction() !== null) {
                $this->transactionFailed($ex->getMessage());
            }

		    if (!$ex instanceof IranPaymentException) {
                $ex = IranPaymentException::unknown($ex);
            }

		    throw $ex;
		}

		return $this;
	}

    /**
     * Pay Redirect function
     *
     * @throws IranPaymentException
     */
    public function redirect()
    {
        if (!isset($this->transaction)) {
            throw new TransactionNotFoundException();
        }

        try {
            $this->transactionPending();

            return redirect($this->purchaseUri());
        } catch (IranPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw IranPaymentException::unknown($ex);
        }
    }

    /**
     * Pay View function
     *
     * @return View
     * @throws TransactionNotFoundException
     * @throws GatewayPaymentNotSupportViewException
     */
	public function view()
	{
		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->transactionPending();

			return $this->gatewayPayView();
		} catch (GatewayPaymentNotSupportViewException|Exception $ex) {
			throw $ex;
		}
	}

    /**
     * Pay Uri function
     *
     * @return string
     * @throws TransactionNotFoundException
     * @throws Exception
     */
	public function uri()
	{
		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->transactionPending();

			return $this->gatewayPayUri();
		} catch (Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * General Redirect View function
	 */
	public function generalRedirectView()
	{
		if (method_exists($this, 'gatewayTitle')) {
			$title = $this->gatewayTitle();
		}

		if (method_exists($this, 'gatewayImage')) {
			$image = $this->gatewayImage();
		}

		return view('iranpayment::pages.redirect', [
			'title'				=> $title ?? 'درحال انتقال به درگاه پرداخت...',
			'image'				=> $image ?? null,
			'transaction_code'	=> $this->getTransactionCode(),
			'bank_url'			=> $this->gatewayPayUri(),
		]);
	}

    /**
     * @throws InvalidDataException
     * @throws SucceedRetryException
     * @throws InvalidRequestException
     * @throws TransactionNotFoundException
     */
    protected function preVerify(): void
    {
        if(!isset($this->transaction)) {
            $transaction_code_field = app('config')->get('iranpayment.transaction_query_param', 'tc');
            if (isset($this->request[$transaction_code_field])) {
                $this->findTransaction($this->request[$transaction_code_field]);
            } else {
                throw new TransactionNotFoundException();
            }
        }

        if ($this->transaction->status == IranPaymentTransaction::T_SUCCEED) {
            throw new SucceedRetryException;
        } elseif (!in_array($this->transaction->status, [
            IranPaymentTransaction::T_PENDING,
            IranPaymentTransaction::T_VERIFY_PENDING,
        ])) {
            throw InvalidRequestException::unProcessableVerify();
        }

        $this->setCurrency($this->transaction->currency);
        $this->setAmount($this->transaction->amount);
    }

    protected function postVerify(): void
    {
        $this->transactionSucceed();
    }

    /**
     * Confirm function
     *
     * @param IranPaymentTransaction|null $transaction
     * @return self
     * @throws IranPaymentException
     */
	public function confirm(IranPaymentTransaction $transaction = null)
	{
		if(isset($transaction)) {
			$this->setTransaction($transaction);
		}

		try {
            $this->preVerify();

            $this->verify();

            $this->postVerify();
        } catch (TransactionFailedException $ex) {
            $this->transactionFailed($ex->getMessage());

            throw $ex;
        } catch (IranPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw IranPaymentException::unknown($ex);
        }

		return $this;
	}
}
