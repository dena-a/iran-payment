<?php

namespace Dena\IranPayment\Gateways;

use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\SucceedRetryException;
use Dena\IranPayment\Exceptions\TransactionFailedException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Dena\IranPayment\Traits\PaymentData;
use Dena\IranPayment\Traits\TransactionData;
use Dena\IranPayment\Traits\UserData;
use Exception;

abstract class AbstractGateway implements GatewayInterface
{
    use PaymentData,
        TransactionData,
        UserData;

    /**
     * Request variable
     */
    protected array $request;

    /**
     * Gateway Request Options variable
     */
    protected array $gateway_request_options = [];

    abstract public function verify(): void;

    abstract public function purchase(): void;

    /**
     * Initialize Gateway function
     *
     * @return $this
     *
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
     * @return $this
     */
    public function setRequest(array $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get Request function
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Set Gateway Request Options function
     *
     * @return $this
     */
    public function setGatewayRequestOptions(array $options): self
    {
        $this->gateway_request_options = $options;

        return $this;
    }

    /**
     * Get Gateway Request Options function
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

        if (! in_array($this->getCurrency(), [Currency::IRR, Currency::IRT])) {
            throw InvalidDataException::invalidCurrency();
        }

        if (filter_var($this->preparedCallbackUrl(), FILTER_VALIDATE_URL) === false) {
            throw InvalidDataException::invalidCallbackUrl();
        }

        $this->newTransaction([
            'full_name' => $this->getFullname(),
            'email' => $this->getEmail(),
            'mobile' => $this->getMobile(),
            'description' => $this->getDescription(),
        ]);
    }

    protected function postPurchase(): void
    {
        $this->transactionPending();
    }

    /**
     * Pay function
     *
     * @return $this
     *
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

            if (! $ex instanceof IranPaymentException) {
                $ex = IranPaymentException::unknown($ex);
            }

            throw $ex;
        }

        return $this;
    }

    /**
     * Alias for Purchase Uri function
     *
     * @throws IranPaymentException
     */
    public function uri(): string
    {
        try {
            return $this->purchaseUri();
        } catch (IranPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw IranPaymentException::unknown($ex);
        }
    }

    /**
     * Redirect to Purchase Uri function
     *
     * @throws IranPaymentException
     */
    public function redirect()
    {
        try {
            return response()->redirectTo($this->purchaseUri());
        } catch (IranPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw IranPaymentException::unknown($ex);
        }
    }

    /**
     * Purchase View Params function
     */
    protected function purchaseViewParams(): array
    {
        return [];
    }

    /**
     * @return mixed
     */
    public function purchaseView(array $data = [])
    {
        $parameters = array_merge(
            [
                'view' => 'iranpayment::pages.redirect',
                'title' => null,
                'image' => null,
                'method' => 'GET',
                'form_data' => [],
            ],
            $this->purchaseViewParams(),
            $data
        );

        return response()->view($parameters['view'], array_merge(
            [
                'transaction_code' => $parameters['transaction_code'] ?? $this->getTransactionCode(),
                'bank_url' => $parameters['bank_url'] ?? $this->purchaseUri(),
            ],
            $parameters
        ));
    }

    /**
     * Alias for Purchase View function
     *
     * @return mixed
     */
    public function view(array $data = [])
    {
        return $this->purchaseView($data);
    }

    /**
     * @throws IranPaymentException
     */
    protected function preVerify(): void
    {
        if (! isset($this->transaction)) {
            $transaction_code_field = app('config')->get('iranpayment.transaction_query_param', 'tc');
            if (isset($this->request[$transaction_code_field])) {
                $this->findTransaction($this->request[$transaction_code_field]);
            } else {
                throw new TransactionNotFoundException;
            }
        }

        if ($this->transaction->status == IranPaymentTransaction::T_SUCCEED) {
            throw new SucceedRetryException;
        } elseif (! in_array($this->transaction->status, [
            IranPaymentTransaction::T_PENDING,
            IranPaymentTransaction::T_VERIFY_PENDING,
        ])) {
            throw InvalidRequestException::unProcessableVerify();
        }

        $this->setCurrency($this->transaction->currency);
        $this->setAmount($this->transaction->amount);

        $this->transactionVerifyPending();
    }

    protected function postVerify(): void
    {
        $this->transactionSucceed();
    }

    /**
     * Confirm function
     *
     * @return self
     *
     * @throws IranPaymentException
     */
    public function confirm(?IranPaymentTransaction $transaction = null)
    {
        if (isset($transaction)) {
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

    public function statusView(array $parameters = [])
    {
        return response()->view('iranpayment::pages.status', array_merge(
            [
                'status' => $parameters['status'] ?? $this->getTransaction()->status,
                'transaction_code' => $parameters['transaction_code'] ?? $this->getTransactionCode(),
                'button_url' => url('/'),
                'button_text' => 'بازگشت به صفحه‌اصلی',
            ],
            $parameters
        ));
    }
}
