<?php
/**
 * Api Version: v1
 * Api Document Date: 2022/03/23
 * Last Update: 2022/03/23
 */

namespace Dena\IranPayment\Gateways\Novinopay;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Exception;

class Novinopay extends AbstractGateway implements GatewayInterface
{
    private const REQUEST_URL = 'https://api.novinopay.com/Payment/rest/v1/Request';

    private const VERIFY_URL = 'https://api.novinopay.com/Payment/rest/v1/Verification';

    public const CURRENCY = Currency::IRT;

    /**
     * Merchant ID variable
     */
    protected ?string $merchant_id;

    /**
     * Invoice ID variable
     */
    protected ?string $invoice_id;

    /**
     * Authority variable
     */
    protected ?string $authority;

    /**
     * Ref Id variable
     */
    protected ?string $ref_id;

    /**
     * Payment Url variable
     */
    protected ?string $payment_url;

    /**
     * Mask Card Number variable
     */
    protected ?string $mask_card_number = null;

    /**
     * Gateway Transaction Data variable
     */
    protected ?array $gateway_transaction_data = null;

    /**
     * Gateway Name function
     */
    public function getName(): string
    {
        return 'novinopay';
    }

    /**
     * Set Merchant ID function
     *
     * @return $this
     */
    public function setMerchantId(string $merchant_id): self
    {
        $this->merchant_id = $merchant_id;

        return $this;
    }

    /**
     * Get Merchant ID function
     */
    public function getMerchantId(): ?string
    {
        return $this->merchant_id;
    }

    /**
     * Set Invoice ID function
     *
     * @return $this
     */
    public function setInvoiceId(string $invoice_id): self
    {
        $this->invoice_id = $invoice_id;

        return $this;
    }

    /**
     * Get Invoice ID function
     */
    public function getInvoiceId(): ?string
    {
        return $this->invoice_id;
    }

    /**
     * Set Authority function
     *
     * @return $this
     */
    public function setAuthority(string $authority): self
    {
        $this->authority = $authority;

        return $this;
    }

    /**
     * Get Authority function
     */
    public function getAuthority(): ?string
    {
        return $this->authority;
    }

    /**
     * Set Ref ID function
     *
     * @return $this
     */
    public function setRefId(string $ref_id): self
    {
        $this->ref_id = $ref_id;

        return $this;
    }

    /**
     * Get Ref ID function
     */
    public function getRefId(): ?string
    {
        return $this->ref_id;
    }

    /**
     * Set Payment Url function
     *
     * @return $this
     */
    public function setPaymentUrl(string $payment_url): self
    {
        $this->payment_url = $payment_url;

        return $this;
    }

    /**
     * Get Payment Url function
     */
    public function getPaymentUrl(): ?string
    {
        return $this->payment_url;
    }

    /**
     * Set Mask Card Number function
     *
     * @return $this
     */
    public function setMaskCardNumber(string $mask_card_number): self
    {
        $this->mask_card_number = $mask_card_number;

        return $this;
    }

    /**
     * Get Mask Card Number function
     */
    public function getMaskCardNumber(): ?string
    {
        return $this->mask_card_number;
    }

    /**
     * Set Payment Url function
     *
     * @return $this
     */
    public function setGatewayTransactionData(array $gateway_transaction_data): self
    {
        $this->gateway_transaction_data = $gateway_transaction_data;

        return $this;
    }

    /**
     * Get Payment Url function
     */
    public function getGatewayTransactionData(): ?array
    {
        return $this->gateway_transaction_data;
    }

    /**
     * Initialize function
     *
     * @return $this
     *
     * @throws InvalidDataException
     */
    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);

        $this->setMerchantId($parameters['merchant_id'] ?? app('config')->get('iranpayment.novinopay.merchant-id'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.novinopay.callback-url')
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

        if ($this->preparedAmount() < 100 || $this->preparedAmount() > 1000000000) {
            throw InvalidDataException::invalidAmount();
        }

        $this->setInvoiceId($this->getTransactionCode());
    }

    /**
     * @throws GatewayException|NovinopayException|TransactionFailedException
     */
    public function purchase(): void
    {
        $fields = [
            'MerchantID' => $this->getMerchantId(),
            'Amount' => $this->preparedAmount(),
            'InvoiceID' => $this->getInvoiceId(),
            'Description' => $this->getDescription(),
            'Email' => $this->getEmail(),
            'Mobile' => $this->getMobile(),
            'CallbackURL' => $this->preparedCallbackUrl(),
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::REQUEST_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
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
        } catch (Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (! isset($result->Status)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        if (intval($result->Status) !== 100) {
            throw NovinopayException::error($result->Status);
        }

        if (! isset($result->Authority)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setAuthority($result->Authority);

        if (! isset($result->PaymentUrl)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setPaymentUrl($result->PaymentUrl);
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getAuthority(),
        ]);

        parent::postPurchase();
    }

    /**
     * Pay Link function
     */
    public function purchaseUri(): string
    {
        return $this->getPaymentUrl();
    }

    /**
     * Purchase View Params function
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'نوینو پرداخت',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/novinopay.png',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (isset($this->request['PaymentStatus']) && $this->request['PaymentStatus'] != 'OK') {
            throw NovinopayException::error(-20);
        }

        if (isset($this->request['Authority']) && $this->request['Authority'] !== $this->getReferenceNumber()) {
            throw NovinopayException::error(-19);
        }

        $this->setAuthority($this->getReferenceNumber());
    }

    /**
     * @throws GatewayException|NovinopayException|TransactionFailedException
     */
    public function verify(): void
    {
        $fields = [
            'MerchantID' => $this->getMerchantId(),
            'Authority' => $this->getAuthority(),
            'Amount' => $this->preparedAmount(),
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
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
        } catch (Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (! isset($result->Status)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        if (intval($result->Status) !== 100 && intval($result->Status) !== 101) {
            throw NovinopayException::error($result->Status);
        }

        if (! isset($result->RefID)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setRefId($result->RefID);

        if (intval($result->Amount) !== $this->preparedAmount()) {
            throw NovinopayException::error(-5);
        }

        if (! isset($result->MaskCardNumber)) {
            $this->setMaskCardNumber($result->MaskCardNumber);
        }

        $this->setGatewayTransactionData([
            'BankRRN' => $result->BankRRN ?? null,
            'BuyerIP' => $result->BuyerIP ?? null,
            'PaymentTime' => $result->PaymentTime ?? null,
        ]);
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate(
            [
                'tracking_code' => $this->getRefId(),
                'card_number' => $this->getMaskCardNumber(),
            ],
            $this->getGatewayTransactionData() ?? []
        );

        parent::postVerify();
    }
}
