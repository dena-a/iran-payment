<?php

namespace Dena\IranPayment\Providers\PayIr;

use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\GatewayInterface;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\PayBackNotPossibleException;

use Dena\IranPayment\Helpers\Currency;

class PayIr extends BaseProvider implements GatewayInterface
{
    private const SEND_URL   = "https://pay.ir/pg/send";
    private const VERIFY_URL = "https://pay.ir/pg/verify";
    private const TOKEN_URL  = "https://pay.ir/pg/{token}";
    public const CURRENCY    = Currency::IRR;

    /**
     * API variable
     *
     * @var string
     */
    protected string $api;

    /**
     * Factor Number variable
     *
     * @var string|null
     */
    protected ?string $factor_number = null;

    /**
     * Constructor function
     */
    public function __construct()
	{
		parent::__construct();
		$this->setDefaults();
	}

    /**
     * Gateway Name function
     *
     * @return string
     */
    public function gatewayName(): string
    {
        return 'pay.ir';
    }

	/**
	 * Set Defaults function
	 *
	 * @return void
	 */
	private function setDefaults()
	{
		$this->setGatewayCurrency(self::CURRENCY);
		$this->setApi(config('iranpayment.payir.merchant-id'));
		$this->setCallbackUrl(config('iranpayment.payir.callback-url', config('iranpayment.callback-url')));
	}

    /**
     * Set API function
     *
     * @param string $api
     * @return $this
     */
	public function setApi(string $api): self
	{
		$this->api = $api;

		return $this;
	}

    /**
     * Set Factor Number function
     *
     * @param $factor_number
     * @return $this
     */
	public function setFactorNumber($factor_number): self
	{
		$this->factor_number = (string) $factor_number;

		return $this;
	}

    /**
     * Get Factor Number function
     *
     * @return string
     */
	public function getFactorNumber(): ?string
	{
		return $this->factor_number;
	}

	public function gatewayPayPrepare(): void
	{
		if ($this->getPreparedAmount() < 1000) {
			throw InvalidDataException::invalidAmount();
		}

		$this->setFactorNumber($this->transaction->code);
	}

	public function gatewayPay(): void
	{
		$fields = http_build_query([
			'api' => $this->api,
			'amount' => $this->getPreparedAmount(),
			'redirect' => urlencode($this->getCallbackUrl()),
			'factorNumber' => $this->getFactorNumber(),
			'mobile' => $this->getMobile(),
			'description' => $this->getDescription(),
            'validCardNumber' => $this->getValidCardNumber(),
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::SEND_URL);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
			$result	= curl_exec($ch);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
				$this->transactionFailed($ch_error);
				throw GatewayException::connectionProblem();
			}

			$result = json_decode($result);
		} catch(Exception $ex) {
			$this->transactionFailed($ex->getMessage());
			throw $ex;
		}

		if(!isset($result->token)) {
			if (isset($result->errorCode, $result->errorMessage)) {
				$this->transactionFailed($result->errorMessage);
				throw PayIrException::error($result->errorCode);
			}

			$this->transactionFailed(json_encode($result));
			throw GatewayException::unknownResponse();
		}

		$this->transactionUpdate([
			'reference_number'	=> $result->token
		]);
	}

	/**
	 * Pay Link function
	 *
	 * @return string
	 */
	public function gatewayPayUri(): string
	{
		return str_replace('{token}', $this->getReferenceNumber(), self::TOKEN_URL);
	}

	public function gatewayPayView()
	{
		return view('iranpayment::pages.payir', [
			'transaction_code'	=> $this->getTransactionCode(),
			'bank_url'			=> $this->gatewayPayUri(),
		]);
	}

	public function gatewayPayRedirect()
	{
		return redirect($this->gatewayPayUri());
	}

	public function gatewayVerifyPrepare(): void
	{
		//
	}

	public function gatewayVerify(): void
	{
		if (!isset($this->request->token, $this->request->status)) {
			$ex = InvalidRequestException::notFound();
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		$this->transactionVerifyPending();

		$token = $this->getReferenceNumber();
		$fields = http_build_query([
			'api'	=> $this->api,
			'token'	=> $token,
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
			$result	= curl_exec($ch);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
				$this->transactionFailed($ch_error);
				throw GatewayException::connectionProblem();
			}

			$result = json_decode($result);

		} catch(Exception $ex) {
			$this->transactionFailed($ex->getMessage());
			throw $ex;
		}

		if(!isset($result->amount, $result->transId, $result->cardNumber)) {
			if (isset($result->errorCode, $result->errorMessage)) {
				$this->transactionFailed($result->errorMessage);
				throw PayIrException::error($result->errorCode);
			}

			$this->transactionFailed(json_encode($result));
			throw GatewayException::unknownResponse();
		}

		if (intval($result->amount) != $this->getPreparedAmount()) {
			$gwex = GatewayException::inconsistentResponse();
			$this->transactionFailed($gwex->getMessage());
			throw $gwex;
		}

		$this->transactionUpdate([
			'tracking_code' => $result->transId,
			'card_number' 	=> $result->cardNumber,
		]);
	}

	/**
	 * Pay Back function
	 *
	 * @throws PayBackNotPossibleException
	 */
	public function gatewayPayBack(): void
	{
		throw new PayBackNotPossibleException;
	}
}
