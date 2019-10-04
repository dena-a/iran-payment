<?php

namespace Dena\IranPayment\Providers\PayPing;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\PayBackNotPossibleException;
use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\GatewayInterface;

use Dena\IranPayment\Helpers\Currency;

class PayPing extends BaseProvider implements GatewayInterface
{
	/**
	 * Token variable
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * Client Ref ID variable
	 *
	 * @var [type]
	 */
	protected $client_ref_id = null;

	/**
	 * Add Fees variable
	 *
	 * @var bool
	 */
	private $add_fees;

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
	public function gatewayName()
	{
		return 'payping';
	}

	/**
	 * Gateway Title function
	 *
	 * @return string
	 */
	public function gatewayTitle()
	{
		return 'پی‌پینگ';
	}

	/**
	 * Gateway Image function
	 *
	 * @return string
	 */
	public function gatewayImage()
	{
		return 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/payping.png';
	}

	/**
	 * Set Defaults function
	 *
	 * @return void
	 */
	private function setDefaults()
	{
		$this->setGatewayCurrency(Currency::IRT);
		$this->setToken(config('iranpayment.payping.merchant-id'));
		$this->setCallbackUrl(config('iranpayment.payping.callback-url', config('iranpayment.callback-url')));

		$this->add_fees = config('iranpayment.payping.add_fees', false);
	}

	/**
	 * Set Token function
	 *
	 * @param string $token
	 * @return self
	 */
	public function setToken(string $token)
	{
		$this->token = $token;

		return $this;
	}

	/**
	 * Set Client Ref ID function
	 *
	 * @param $client_ref_id
	 * @return self
	 */
	public function setClientRefId($client_ref_id)
	{
		$this->client_ref_id = $client_ref_id;

		return $this;
	}

	/**
	 * Get Client Ref ID function
	 *
	 * @return string
	 */
	public function getClientRefId()
	{
		return $this->client_ref_id;
	}

	public function gatewayPayPrepare()
	{
		if ($this->getPreparedAmount() < 100) {
			throw InvalidDataException::invalidAmount();
		}

		$this->setClientRefId($this->transaction->code);
	}

	public function gatewayPay()
	{
		$amount = $this->getPreparedAmount();
		if ($this->add_fees) {
			$fees = $amount * 1 / 100;
			$amount += $fees > 5000 ? 5000 : $fees;
			$amount = intval($amount);
		}

		$fields = json_encode([
			'amount'		=> $amount,
			'returnUrl'		=> $this->getCallbackUrl(),
			'clientRefId'	=> $this->getClientRefId(),
			'payerIdentity'	=> $this->getMobile(),
			'description'	=> $this->getDescription(),
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.payping.ir/v1/pay');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Authorization: bearer '.$this->token,
				'Content-Type: application/json',
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
			$result	= curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

		if($httpcode != 200 || !isset($result->code)) {
			if (isset($result->Error)) {
				$this->transactionFailed($result->Error);
				throw new PayPingException($result->Error, $httpcode);
			}

			$this->transactionFailed(json_encode($result));
			throw GatewayException::unknownResponse();
		}

		$this->transactionUpdate([
			'reference_number'	=> $result->code
		]);
	}

	/**
	 * Pay Link function
	 *
	 * @return string
	 */
	public function gatewayPayUri()
	{
		$reference_number = $this->getReferenceNumber();
		return "https://api.payping.ir/v1/pay/gotoipg/$reference_number";
	}

	/**
	 * Pay View function
	 *
	 * @return View
	 */
	public function gatewayPayView()
	{
		return $this->generalRedirectView();
	}

	public function gatewayPayRedirect()
	{
		return redirect($this->gatewayPayUri());
	}

	public function gatewayVerifyPrepare()
	{
		//
	}

	public function gatewayVerify()
	{
		if (!isset($this->request->refid)) {
			$ex = InvalidRequestException::notFound();
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		$this->transactionVerifyPending([
			'tracking_code' => $this->request->refid,
		]);

		$amount = $this->getPreparedAmount();
		if ($this->add_fees) {
			$fees = $amount * 1 / 100;
			$amount += $fees > 5000 ? 5000 : $fees;
			$amount = intval($amount);
		}

		$fields = json_encode([
			'refId'	=> $this->request->refid,
			'amount'=> $amount,
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.payping.ir/v1/pay/verify');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Authorization: bearer '.$this->token,
				'Content-Type: application/json',
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout); 
			$result	= curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
				$this->transactionFailed($ch_error);
				throw GatewayException::connectionProblem();
			}
			
			$raw_result = $result;
			$result = json_decode($result, true);
		} catch(Exception $ex) {
			$this->transactionFailed($ex->getMessage());
			throw $ex;
		}

		if($httpcode != 200) {
			#TODO: Check PayPing error codes. No documents published
			if (isset($result->Error)) {
				$this->transactionFailed($result->Error);
				throw new PayPingException($result->Error, $httpcode);
			} elseif (isset($result[12])) {
				$this->transactionFailed($result[12]);
				throw new PayPingException($result[12], $httpcode);
			} elseif (isset($result[120])) {
				$this->transactionFailed($result[120]);
				throw new PayPingException($result[120], $httpcode);
			}

			$this->transactionFailed($raw_result);
			throw GatewayException::unknownResponse();
		}
	}

	/**
	 * Pay Back function
	 *
	 * @throws PayBackNotPossibleException
	 */
	public function gatewayPayBack()
	{
		throw new PayBackNotPossibleException;
	}
}