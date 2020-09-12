<?php
/**
 * Api Version: v2
 * Api Document Date: 2018/12/15
 * Last Update: 2020/08/04
 */

namespace Dena\IranPayment\Gateways\PayPing;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

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
     * Ref ID variable
     *
     * @var string|null
     */
    protected ?string $ref_id = null;

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
     * Payer Card Number variable
     *
     * @var string|null
     */
    protected ?string $payer_card_number;

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
     * Set  Ref ID function
     *
     * @param string $ref_id
     * @return $this
     */
    public function setRefId(string $ref_id): self
    {
        $this->ref_id = $ref_id;

        return $this;
    }

    /**
     * Get  Ref ID function
     *
     * @return string
     */
    public function getRefId(): string
    {
        return $this->ref_id;
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
     * Set Payer Card Number function
     *
     * @param string $payer_card_number
     * @return $this
     */
    public function setPayerCardNumber(string $payer_card_number): self
    {
        $this->payer_card_number = $payer_card_number;

        return $this;
    }

    /**
     * Get Payer Card Number function
     *
     * @return string|null
     */
    public function getPayerCardNumber(): ?string
    {
        return $this->payer_card_number;
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
        if (!is_null($payer_identity)) {
            $this->setPayerIdentity($payer_identity);
        }

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.payping.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        return $this;
	}

    public function preparedAmount(): int
    {
        $amount = parent::preparedAmount();

        if ($this->getAddFees()) {
            $amount = $this->feeCalculator($amount);
        }

        return $amount;
    }

	protected function prePurchase(): void
	{
	    parent::prePurchase();

        if ($this->preparedAmount() < 100 || $this->preparedAmount() > 50000000) {
            throw InvalidDataException::invalidAmount();
        }

        $this->setClientRefId($this->getTransactionCode());
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
			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$ch_error = curl_error($ch);
			curl_close($ch);

            if ($ch_error) {
                throw GatewayException::connectionProblem(new Exception($ch_error));
            }

			$result = json_decode($response);
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
			throw GatewayException::unknownResponse($response);
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
	public function purchaseUri(): string
	{
        return str_replace('{code}', $this->getReferenceNumber(), self::REDIRECT_URL);
	}

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'پی‌پینگ',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/payping.png',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (isset($this->request['code']) && $this->request['code'] !== $this->getReferenceNumber()) {
            throw PayPingException::httpError(404);
        }

        if (isset($this->request['clientrefid']) && $this->request['clientrefid'] !== $this->getTransactionCode()) {
            throw PayPingException::httpError(404);
        }

        if (!isset($this->request['refid']) && $this->getTrackingCode() === null) {
            throw PayPingException::httpError(404);
        }

        if (isset($this->request['refid']) && $this->getTrackingCode() !== null && $this->request['refid'] !== $this->getTransactionCode()) {
            throw PayPingException::httpError(404);
        }

        if (isset($this->request['refid']) && $this->getTrackingCode() === null) {
            $this->transactionUpdate([
                'tracking_code' => $this->request['refid'],
            ]);
        }

        $this->setRefId($this->getTrackingCode());
    }

    /**
     * @throws GatewayException|PayPingException|TransactionFailedException
     */
	public function verify(): void
	{
		$fields = json_encode([
			'refId'	=> $this->getRefId(),
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
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
			$result	= curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$ch_error = curl_error($ch);
			curl_close($ch);

            if ($ch_error) {
                throw GatewayException::connectionProblem(new Exception($ch_error));
            }

			$raw_result = $result;
			$result = json_decode($result, true);
		} catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

		if($httpcode !== 200) {
			throw PayPingException::httpError($httpcode);
		}

        if (intval($result->amount) !== $this->preparedAmount()) {
            throw PayPingException::error(9);
        }

		if (isset($result->cardNumber) || isset($result->cardHashPan)) {
		    $this->setPayerCardNumber($result->cardNumber ?? $result->cardHashPan);
        }
	}

    protected function postVerify(): void
    {
        $this->transactionUpdate([
            'card_number' => $this->getPayerCardNumber(),
        ]);

        parent::postVerify();
    }

    private function feeCalculator(int $amount): int
    {
        $fees = $amount * 1 / 100;
        $amount += $fees > 5000 ? 5000 : $fees;
        return intval($amount);
    }
}
