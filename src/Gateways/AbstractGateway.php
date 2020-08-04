<?php

namespace Dena\IranPayment\Gateways;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Dena\IranPayment\Traits\UserData;
use Dena\IranPayment\Traits\PaymentData;
use Dena\IranPayment\Traits\TransactionData;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\SucceedRetryException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\PayBackNotPossibleException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Exceptions\GatewayPaymentNotSupportViewException;
use Dena\IranPayment\Exceptions\GatewayPaymentNotSupportRedirectException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\Helpers\Currency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * @method getName()
 * @method gatewayPay()
 * @method gatewayPayLink()
 * @method gatewayPayRedirect()
 * @method gatewayPayUri()
 * @method gatewayPayView()
 * @method gatewayVerifyPrepare()
 * @method gatewayVerify()
 * @method gatewayPayBack()
 */
abstract class AbstractGateway
{
	use UserData,
        PaymentData,
        TransactionData;

	protected $request;
    protected array $gateway_request_options = [];

    abstract public function initialize(array $parameters = []): GatewayInterface;

    /**
     * Boot Gateway function
     *
     * @param array $parameters
     * @return $this
     * @throws InvalidDataException
     */
    public function boot(array $parameters = []): self
    {
        $this->setRequest($parameters['request'] ?? app('request'));

        $this->setCurrency($parameters['currency'] ?? app('config')->get('iranpayment.currency', Currency::IRR));

        $this->setCallbackUrl($parameters['callback_url'] ?? app('config')->get('iranpayment.callback-url'));

        $this->setGatewayRequestOptions(array_merge(
            [
                'timeout' => app('config')->get('iranpayment.timeout', 30),
                'connection_timeout' => app('config')->get('iranpayment.connection_timeout', 60),
            ],
            $parameters['gateway_request_options'] ?? [],
        ));

        $this->initialize($parameters);

        return $this;
    }

	/**
	 * Set Request function
	 *
	 * @param Request $request
	 * @return self
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
		return $this;
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
                throw IranPaymentException::unknown($ex);
            }

		    throw $ex;
		}

		return $this;
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
     * Pay Redirect function
     *
     * @return View
     * @throws TransactionNotFoundException
     * @throws GatewayPaymentNotSupportRedirectException
     * @throws Exception
     */
	public function redirect()
	{
		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->transactionPending();

			return $this->gatewayPayRedirect();
		} catch (GatewayPaymentNotSupportRedirectException|Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * General Redirect View function
	 *
	 * @return View
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
     * Verify function
     *
     * @param IranPaymentTransaction|null $transaction
     * @return self
     * @throws InvalidRequestException
     * @throws SucceedRetryException
     * @throws TransactionNotFoundException
     * @throws InvalidDataException
     */
	public function verify(IranPaymentTransaction $transaction = null)
	{
		if(isset($transaction)) {
			$this->setTransaction($transaction);
		} elseif(!isset($this->transaction)) {
			$transaction_code_field = app('config')->get('iranpayment.transaction_query_param', 'tc');
			if (isset($this->request->$transaction_code_field)) {
				$this->searchTransactionCode($this->request->$transaction_code_field);
			}
		}

		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
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

		try {
			$this->gatewayVerifyPrepare();

			$this->gatewayVerify();

			$this->transactionSucceed();
		} catch (Exception $ex) {
			throw $ex;
		}

		return $this;
	}

    /**
     * Pay Back function
     *
     * @param IranPaymentTransaction|null $transaction
     * @return void
     * @throws GatewayException
     * @throws PayBackNotPossibleException
     * @throws TransactionNotFoundException
     */
	protected function payBack(IranPaymentTransaction $transaction = null)
	{
		if(isset($transaction)) {
			$this->setTransaction($transaction);
		} elseif(!isset($this->transaction)) {
			$transaction_code_field = app('config')->get('iranpayment.transaction_query_param', 'tc');
			if (isset($this->request->$transaction_code_field)) {
				$this->searchTransactionCode($this->request->$transaction_code_field);
			}
		}

		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->gatewayPayBack();

			$this->transactionPaidBack();
		} catch (PayBackNotPossibleException|GatewayException|Exception $ex) {
			throw $ex;
		}
	}
}
