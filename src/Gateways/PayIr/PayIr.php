<?php
/**
 * Api Version: ?
 * Api Document Date: 2020/08/03
 * Last Update: 2020/08/03
 */

namespace Dena\IranPayment\Gateways\PayIr;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\InvalidRequestException;

use Dena\IranPayment\Helpers\Currency;

class PayIr extends AbstractGateway implements GatewayInterface
{
    private const SEND_URL   = "https://pay.ir/pg/send";
    private const VERIFY_URL = "https://pay.ir/pg/verify";
    private const TOKEN_URL  = "https://pay.ir/pg/{token}";
    public const CURRENCY    = Currency::IRR;

    /**
     * API variable
     *
     * @var string|null
     */
    protected ?string $api;

    /**
     * Factor Number variable
     *
     * @var string|null
     */
    protected ?string $factor_number;

    /**
     * Token variable
     *
     * @var string|null
     */
    protected ?string $token;

    /**
     * Gateway Name function
     *
     * @return string
     */
    public function getName(): string
    {
        return 'pay.ir';
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
     * Get API function
     *
     * @return string|null
     */
    public function getApi(): ?string
    {
        return $this->api;
    }

    /**
     * Set Factor Number function
     *
     * @param string $factor_number
     * @return $this
     */
    public function setFactorNumber(string $factor_number): self
    {
        $this->factor_number = $factor_number;

        return $this;
    }

    /**
     * Get Factor Number function
     *
     * @return string|null
     */
    public function getFactorNumber(): ?string
    {
        return $this->factor_number;
    }

    /**
     * Set Token function
     *
     * @param $token
     * @return $this
     */
    public function setToken($token): self
    {
        $this->token = (string) $token;

        return $this;
    }

    /**
     * Get Token function
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Initialize function
     *
     * @param array $parameters
     * @return $this
     * @throws InvalidDataException
     */
    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);

        $this->setApi($parameters['api'] ?? app('config')->get('iranpayment.payir.merchant-id'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.payir.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        return $this;
    }

    /**
     * @throws InvalidDataException
     */
	protected function prePurchase(): void
	{
        parent::prePurchase();

        if ($this->preparedAmount() < 1000) {
            throw InvalidDataException::invalidAmount();
        }

        if ($this->getValidCardNumber() !== null && !preg_match('/^([0-9]{16}|[0-9]{20})$/', $this->getValidCardNumber())) {
            throw InvalidDataException::invalidCardNumber();
        }

        $this->setFactorNumber($this->getTransactionCode());
	}

    public function preparedCallbackUrl(): ?string
    {
        return urlencode(parent::preparedCallbackUrl());
    }

    /**
     * @throws GatewayException
     * @throws PayIrException
     */
	public function purchase(): void
	{
		$fields = http_build_query([
			'api' => $this->getApi(),
			'amount' => $this->preparedAmount(),
			'redirect' => $this->preparedCallbackUrl(),
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
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
			$result	= curl_exec($ch);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
				throw GatewayException::connectionProblem(new Exception($ch_error));
			}

			$result = json_decode($result);
		} catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
		}

		if(!isset($result->token)) {
			if (isset($result->errorCode, $result->errorMessage)) {
				throw PayIrException::error($result->errorCode);
			}

			throw GatewayException::unknownResponse($result);
		}

		$this->setToken($result->token);
	}

	protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getToken(),
        ]);

        parent::postPurchase();
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

	public function verify(): void
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
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->gateway_request_options['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->gateway_request_options['connection_timeout'] ?? 60);
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

		if (intval($result->amount) != $this->preparedAmount()) {
			$ex = GatewayException::inconsistentResponse();
			$this->transactionFailed($ex->getMessage());
			throw $ex;
		}

		$this->transactionUpdate([
			'tracking_code' => $result->transId,
			'card_number' 	=> $result->cardNumber,
		]);
	}
}
