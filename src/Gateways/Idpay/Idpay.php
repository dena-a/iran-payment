<?php
/**
 * Api Version: v1.1
 * Api Document Date: 1393/09/23
 * Last Update: 2020/08/03
 */

namespace Dena\IranPayment\Gateways\Idpay;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

use Dena\IranPayment\Gateways\PayIr\PayIr;
use Dena\IranPayment\Helpers\Currency;

use Exception;

class Idpay extends AbstractGateway implements GatewayInterface
{
    private const WS_URL       = "https://api.idpay.ir/v1.1/payment";
    private const VERIFY_URL = "https://api.idpay.ir/v1.1/payment/verify";
    public const CURRENCY        = Currency::IRR;

    /**
     * Factor Number variable
     *
     * @var string|null
     */
    protected ?string $factor_number;

	/**
	 * Merchant ID variable
	 *
	 * @var string|null
	 */
	protected ?string $merchant_id;

    /**
     * Authority variable
     *
     * @var string|null
     */
    protected ?string $authority;

    /**
     * Ref Id variable
     *
     * @var string|null
     */
    protected ?string $ref_id;

    /**
     * Sandbox mod variable
     *
     * @var string
     */
    protected string $type = 'normal';

	/**
	 * Gateway Name function
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'idpay';
	}

    /**
     * Trans Id variable
     *
     * @var string|null
     */
    protected ?string $trans_id;

    /**
     * Mask Card Number variable
     *
     * @var string|null
     */
    protected ?string $mask_card_number = null;

    /**
     * Set Merchant ID function
     *
     * @param string $merchant_id
     * @return $this
     */
    public function setMerchantId(string $merchant_id): self
    {
        $this->merchant_id = $merchant_id;

        return $this;
    }

    /**
     * Get Merchant ID function
     *
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return $this->merchant_id;
    }

    /**
     * Set Type function
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get Type function
     *
     * @return string
     */
    public function getType(): string
    {
        if($this->type == 'sandbox'){
            $this->type = 1;
        }else {
            $this->type = 0;
        }
        return $this->type;
    }

    /**
     * Set Authority function
     *
     * @param string $authority
     * @return $this
     */
    public function setAuthority(string $authority): self
    {
        $this->authority = $authority;

        return $this;
    }

    /**
     * Get Authority function
     *
     * @return string|null
     */
    public function getAuthority(): ?string
    {
        return $this->authority;
    }

    /**
     * Set Ref ID function
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
     * Payment Url variable
     *
     * @var string|null
     */
    protected ?string $payment_url;

    /**
     * Get Ref ID function
     *
     * @return string|null
     */
    public function getRefId(): ?string
    {
        return $this->ref_id;
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

        $this->setType($parameters['type'] ?? app('config')->get('iranpayment.idpay.type', 'normal'));

        $this->setMerchantId($parameters['merchant_id'] ?? app('config')->get('iranpayment.idpay.merchant-id'));

        $this->setDescription($parameters['description'] ?? app('config')->get('iranpayment.idpay.description', 'تراكنش خرید'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.idpay.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        return $this;
    }

    public function preparedAmount(): int
    {
        $amount = parent::preparedAmount();

        return $amount;
    }

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
     * @throws InvalidDataException
     */
    protected function prePurchase(): void
    {
        parent::prePurchase();
        $this->setFactorNumber($this->getTransactionCode());

    }

    /**
     * @throws GatewayException
     * @throws TransactionFailedException
     */

	public function purchase(): void
	{
		$fields = [
            'order_id' => $this->getFactorNumber(),
            'amount' => $this->preparedAmount(),
            'desc' => $this->getDescription(),
            'mail' => $this->getEmail(),
            'phone' => $this->getMobile(),
            'callback' => $this->preparedCallbackUrl(),
		];
        $header = [
            'Content-Type: application/json',
            'X-API-KEY:' .$this->merchant_id,
            'X-SANDBOX:' .$this->getType()
        ];
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::WS_URL);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $response = curl_exec($ch);
            $ch_error = curl_error($ch);
            curl_close($ch);

            if ($ch_error) {
                throw GatewayException::connectionProblem(new Exception($ch_error));
            }
            $result = json_decode($response);

        } catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        };

        if(isset($result->error_message)){
            throw IdpayException::error($result->error_code);
        }

        $this->setAuthority($result->id);
        $this->setPaymentUrl($result->link);
	}

    /**
     * Set Payment Url function
     *
     * @param string $payment_url
     * @return $this
     */
    public function setPaymentUrl(string $payment_url): self
    {
        $this->payment_url = $payment_url;

        return $this;
    }

    /**
     * Get Payment Url function
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        return $this->payment_url;
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getAuthority(),
        ]);
        parent::postPurchase();
    }

    /**
     * Purchase Uri function
     *
     * @return string
     * @throws InvalidDataException
     */
	public function purchaseUri(): string
	{
        return $this->getPaymentUrl();
	}

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'آیدی پی',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/idpay.png',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (! isset($this->request['tc'])) {
            throw IdpayException::error(53);
        }

	}

    /**
     * @throws GatewayException
     * @throws ZarinpalException
     * @throws TransactionFailedException
     */
	public function verify(): void
	{
		$fields = [
			'id' => $this->getReferenceNumber(),
			'order_id' => $this->getTransactionCode()
		];

        $header = [
            'Content-Type: application/json',
            'X-API-KEY:' .$this->merchant_id,
            'X-SANDBOX:' .$this->getType()
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type" => "application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $response = curl_exec($ch);
            $ch_error = curl_error($ch);
            curl_close($ch);
            $result = json_decode($response);
        } catch(RequestException $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (isset($result->error_code) || intval($result->status) !== 100 && intval($result->status) !== 101) {

            throw IdpayException::error($result->error_code);
        }

        if (!isset($result->id)) {

            throw GatewayException::unknownResponse(json_encode($result));
        }

        if (intval($result->amount) !== $this->preparedAmount()) {
            throw IdpayException::error(53);
        }

        $this->setTransId($result->track_id);
        $this->setMaskCardNumber($result->payment->card_no);

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
     * Set Mask Card Number function
     *
     * @param string $mask_card_number
     * @return $this
     */
    public function setMaskCardNumber(string $mask_card_number): self
    {
        $this->mask_card_number = $mask_card_number;

        return $this;
    }

    /**
     * Get Mask Card Number function
     *
     * @return string|null
     */
    public function getMaskCardNumber(): ?string
    {
        return $this->mask_card_number;
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate([
            'tracking_code' => $this->getTransId(),
            'card_number' => $this->getMaskCardNumber(),
        ]);

        parent::postVerify();
    }
}
