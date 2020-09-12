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
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

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
     * Trans Id variable
     *
     * @var string|null
     */
    protected ?string $trans_id;

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
     * Set Trans ID function
     *
     * @param string $trans_id
     * @return $this
     */
    public function setTransId(string $trans_id): self
    {
        $this->trans_id = $trans_id;

        return $this;
    }

    /**
     * Get Trans ID function
     *
     * @return string|null
     */
    public function getTransId(): ?string
    {
        return $this->trans_id;
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

        if ($this->preparedAmount() < 10000 || $this->preparedAmount() > 500000000) {
            throw InvalidDataException::invalidAmount();
        }

        if ($this->getValidCardNumber() !== null && !preg_match('/^([0-9]{16}|[0-9]{20})$/', $this->getValidCardNumber())) {
            throw InvalidDataException::invalidCardNumber();
        }

        $this->setFactorNumber($this->getTransactionCode());
	}

    /**
     * @throws GatewayException|PayIrException|TransactionFailedException
     */
	public function purchase(): void
	{
		$fields = http_build_query([
			'api' => $this->getApi(),
			'amount' => $this->preparedAmount(),
			'redirect' => urlencode($this->preparedCallbackUrl()),
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
			$response = curl_exec($ch);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
				throw GatewayException::connectionProblem(new Exception($ch_error));
            }
            
			$result = json_decode($response);
		} catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
		}

		if(!isset($result->token)) {
			if (isset($result->errorCode)) {
				throw PayIrException::error($result->errorCode);
			}

			throw GatewayException::unknownResponse($response);
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
	public function purchaseUri(): string
	{
        return str_replace('{token}', $this->getReferenceNumber(), self::TOKEN_URL);
	}

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'پی',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/pay.png',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (isset($this->request['status']) && $this->request['status'] !== "1") {
            throw PayIrException::error('-5');
        }

        if (isset($this->request['token']) && $this->request['token'] !== $this->getReferenceNumber()) {
            throw PayIrException::error('-8');
        }

        $this->setToken($this->getReferenceNumber());
    }

    /**
     * @throws GatewayException|PayIrException|TransactionFailedException
     */
	public function verify(): void
	{
		$fields = http_build_query([
			'api'	=> $this->getApi(),
			'token'	=> $this->getToken(),
		]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
			$response = curl_exec($ch);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
                throw GatewayException::connectionProblem(new Exception($ch_error));
			}

			$result = json_decode($result);
        } catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if(!isset($result->amount, $result->transId, $result->cardNumber)) {
            if (isset($result->errorCode)) {
                throw PayIrException::error($result->errorCode);
            }

            throw GatewayException::unknownResponse($response);
        }

		if (intval($result->amount) !== $this->preparedAmount()) {
            throw PayIrException::error('-5');
		}

        $this->setTransId($result->transId);
        $this->setPayerCardNumber($result->cardNumber);
	}

    protected function postVerify(): void
    {
        $this->transactionUpdate([
            'tracking_code' => $this->getTransId(),
            'card_number' => $this->getPayerCardNumber(),
        ]);

        parent::postVerify();
    }
}
