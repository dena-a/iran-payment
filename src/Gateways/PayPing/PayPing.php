<?php
/**
 * Api Version: v2
 * Api Document Date: 2018/12/15
 * Last Update: 2020/08/04
 */

namespace Dena\IranPayment\Gateways\PayPing;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Helpers\Currency;

class PayPing extends AbstractGateway implements GatewayInterface
{
    private const PAY_URL      = "https://api.payping.ir/v2/pay";
    private const VERIFY_URL   = "https://api.payping.ir/v2/pay/verify";
    private const REDIRECT_URL = "https://api.payping.ir/v2/pay/gotoipg/{code}";
    public const CURRENCY      = Currency::IRT;

	/**
	 * Token variable
	 *
	 * @var string|null
	 */
	protected ?string $token;

    /**
     * Client Ref ID variable
     *
     * @var string|null
     */
    protected ?string $client_ref_id;

    /**
     * Code variable
     *
     * @var string|null
     */
    protected ?string $code;

	/**
	 * Add Fees variable
	 *
	 * @var bool
	 */
	private bool $add_fees = false;

    /**
     * Payer Identity variable
     *
     * @var string|null
     */
    protected ?string $payer_identity;

	/**
	 * Gateway Name function
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'payping';
	}

    /**
     * Set Token function
     *
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

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
     * Set Client Ref ID function
     *
     * @param string $client_ref_id
     * @return $this
     */
    public function setClientRefId(string $client_ref_id): self
    {
        $this->client_ref_id = $client_ref_id;

        return $this;
    }

    /**
     * Get Client Ref ID function
     *
     * @return string
     */
    public function getClientRefId(): string
    {
        return $this->client_ref_id;
    }

    /**
     * Set Code function
     *
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get Code function
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set Add Fees function
     *
     * @param bool $add_fees
     * @return $this
     */
    public function setAddFees(bool $add_fees): self
    {
        $this->add_fees = $add_fees;

        return $this;
    }

    /**
     * Get Token function
     *
     * @return bool
     */
    public function getAddFees(): bool
    {
        return $this->add_fees;
    }

    /**
     * Set Payer Identity function
     *
     * @param string $payer_identity
     * @return $this
     */
    public function setPayerIdentity(string $payer_identity): self
    {
        $this->payer_identity = $payer_identity;

        return $this;
    }

    /**
     * Get Payer Identity function
     *
     * @return string
     */
    public function getPayerIdentity(): string
    {
        return $this->payer_identity;
    }

	/**
	 * Gateway Title function
	 *
	 * @return string
	 */
	public function gatewayTitle(): string
	{
		return 'پی‌پینگ';
	}

	/**
	 * Gateway Image function
	 *
	 * @return string
	 */
	public function gatewayImage(): string
	{
		return 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/payping.png';
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

        $this->setToken($parameters['token'] ?? app('config')->get('iranpayment.payping.merchant-id'));

        $this->setAddFees($parameters['add_fees'] ?? app('config')->get('iranpayment.payping.add_fees', false));

        $payer_identity = $parameters['payer_identity'] ?? $this->getMobile() ?? $this->getEmail() ?? null;
        is_null($payer_identity) ?: $this->setPayerIdentity($payer_identity);

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.payping.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        return $this;
	}

	protected function prePurchase(): void
	{
	    parent::prePurchase();

        if ($this->preparedAmount() < 100 || $this->preparedAmount() > 50000000) {
            throw InvalidDataException::invalidAmount();
        }

        $this->setClientRefId($this->getTransactionCode());
	}

    public function preparedAmount(): int
    {
        $amount = parent::preparedAmount();

        if ($this->getAddFees()) {
            $amount = $this->feeCalculator($amount);
        }

        return $amount;
    }

    /**
     * @throws GatewayException
     * @throws PayPingException
     */
    public function purchase(): void
	{
		$fields = json_encode([
			'amount'		=> $this->preparedAmount(),
			'returnUrl'		=> $this->preparedCallbackUrl(),
			'clientRefId'	=> $this->getClientRefId(),
			'payerName'     => $this->getFullname(),
			'payerIdentity'	=> $this->getMobile() ?? $this->getEmail(),
			'description'	=> $this->getDescription(),
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::PAY_URL);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				"Authorization: bearer {$this->getToken()}",
				'Content-Type: application/json',
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
			$result	= curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$ch_error = curl_error($ch);
			curl_close($ch);

            if ($ch_error) {
                throw GatewayException::connectionProblem(new Exception($ch_error));
            }

			$result = json_decode($result);
		} catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
		}

		if ($http_code !== 200) {
            if (isset($result->Error)) {
                throw new PayPingException($result->Error, $http_code);
            }

            throw PayPingException::httpError($http_code);
        }

		if(!isset($result->code)) {
			throw GatewayException::unknownResponse();
		}

		$this->setCode($result->code);
	}

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getCode(),
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
        return str_replace('{code}', $this->getReferenceNumber(), self::REDIRECT_URL);
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

	public function verify(): void
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

		$fields = json_encode([
			'refId'	=> $this->request->refid,
			'amount'=> $this->preparedAmount(),
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
                "Authorization: bearer {$this->getToken()}",
				'Content-Type: application/json',
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->gateway_request_options['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->gateway_request_options['connection_timeout'] ?? 60);
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

    private function feeCalculator(int $amount): int
    {
        $fees = $amount * 1 / 100;
        $amount += $fees > 5000 ? 5000 : $fees;
        return intval($amount);
    }
}
