<?php
/**
 * Api Version: v1.3
 * Api Document Date: 1393/09/23
 * Last Update: 2020/08/03
 */

namespace Dena\IranPayment\Gateways\Zarinpal;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Exception;
use SoapClient;
use SoapFault;

class Zarinpal extends AbstractGateway implements GatewayInterface
{
    private const WSDL_URL = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';

    private const WEB_GATE_URL = 'https://www.zarinpal.com/pg/StartPay/{Authority}';

    private const ZARIN_GATE_URL = 'https://www.zarinpal.com/pg/StartPay/{Authority}/ZarinGate';

    private const SANDBOX_URL = 'https://sandbox.zarinpal.com/pg/StartPay/{Authority}';

    public const CURRENCY = Currency::IRT;

    /**
     * Merchant ID variable
     */
    protected ?string $merchant_id;

    /**
     * Authority variable
     */
    protected ?string $authority;

    /**
     * Ref Id variable
     */
    protected ?string $ref_id;

    /**
     * Sandbox mod variable
     */
    protected string $type = 'normal';

    /**
     * Add Fees variable
     */
    private bool $add_fees = false;

    /**
     * Gateway Name function
     */
    public function getName(): string
    {
        return 'zarinpal';
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
     * Set Type function
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get Type function
     */
    public function getType(): string
    {
        return $this->type;
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
     * Set Add Fees function
     *
     * @return $this
     */
    public function setAddFees(bool $add_fees): self
    {
        $this->add_fees = $add_fees;

        return $this;
    }

    /**
     * Get Token function
     */
    public function getAddFees(): bool
    {
        return $this->add_fees;
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

        $this->setMerchantId($parameters['merchant_id'] ?? app('config')->get('iranpayment.zarinpal.merchant-id'));

        $this->setType($parameters['type'] ?? app('config')->get('iranpayment.zarinpal.type', 'normal'));

        $this->setAddFees($parameters['add_fees'] ?? app('config')->get('iranpayment.zarinpal.add_fees', false));

        $this->setDescription($parameters['description'] ?? app('config')->get('iranpayment.zarinpal.description', 'تراكنش خرید'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.zarinpal.callback-url')
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

    /**
     * @throws GatewayException
     * @throws TransactionFailedException
     */
    public function purchase(): void
    {
        $fields = [
            'MerchantID' => $this->getMerchantId(),
            'Amount' => $this->preparedAmount(),
            'Description' => $this->getDescription(),
            'Email' => $this->getEmail(),
            'Mobile' => $this->getMobile(),
            'CallbackURL' => $this->preparedCallbackUrl(),
        ];

        try {
            $soap = new SoapClient(self::WSDL_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            $result = $soap->PaymentRequest($fields);
        } catch (SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (! isset($result->Status)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        if ($result->Status !== 100) {
            throw ZarinpalException::error($result->Status);
        }

        if (! isset($result->Authority)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setAuthority($result->Authority);
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
     * @throws InvalidDataException
     */
    public function purchaseUri(): string
    {
        switch ($this->getType()) {
            case 'normal':
                $url = self::WEB_GATE_URL;
                break;
            case 'zaringate':
            case 'zarin-gate':
            case 'zarin_gate':
                $url = self::ZARIN_GATE_URL;
                break;
            case 'sandbox':
                $url = self::SANDBOX_URL;
                break;
            default:
                throw new InvalidDataException('نوع گیت وارد شده نامعتبر است.');
        }

        return str_replace('{Authority}', $this->getReferenceNumber(), $url);
    }

    /**
     * Purchase View Params function
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'زرین‌پال',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/zp.png',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (isset($this->request['Status']) && $this->request['Status'] != 'OK') {
            throw ZarinpalException::error(-22);
        }

        if (isset($this->request['Authority']) && $this->request['Authority'] !== $this->getReferenceNumber()) {
            throw ZarinpalException::error(-11);
        }

        $this->setAuthority($this->getReferenceNumber());
    }

    /**
     * @throws GatewayException
     * @throws ZarinpalException
     * @throws TransactionFailedException
     */
    public function verify(): void
    {
        $fields = [
            'MerchantID' => $this->getMerchantId(),
            'Authority' => $this->getAuthority(),
            'Amount' => $this->preparedAmount(),
        ];

        try {
            $soap = new SoapClient(self::WSDL_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            $result = $soap->PaymentVerification($fields);
        } catch (SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (! isset($result->Status)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        if ($result->Status !== 100 && $result->Status !== 101) {
            throw ZarinpalException::error($result->Status);
        }

        if (! isset($result->RefID)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setRefId($result->RefID);
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate([
            'tracking_code' => $this->getRefId(),
        ]);

        parent::postVerify();
    }

    private function feeCalculator(int $amount): int
    {
        $fees = $amount * 1 / 100;
        $amount += $fees;

        return intval($amount);
    }
}
