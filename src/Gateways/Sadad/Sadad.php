<?php
/**
 * Api Version: ?
 * Api Document Date: 1397/06/17
 * Last Update: 2020/09/10
 */

namespace Dena\IranPayment\Gateways\Sadad;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Http\CurlRequest;

use DateTime;
use Carbon\Carbon;

class Sadad extends AbstractGateway implements GatewayInterface
{
    private const SEND_URL   = 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest';
    private const VERIFY_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify';
    private const TOKEN_URL  = "https://sadad.shaparak.ir/VPG/Purchase?Token={token}";
    public const CURRENCY    = Currency::IRR;

    /**
     * Merchant ID variable
     *
     * @var string|null
     */
    protected ?string $merchant_id;

    /**
     * Terminal ID variable
     *
     * @var string|null
     */
    protected ?string $terminal_id;

    /**
     * Terminal Key variable
     *
     * @var string|null
     */
    protected ?string $terminal_key;

    /**
     * Token variable
     *
     * @var string|null
     */
    protected ?string $token;

    /**
     * Order Id variable
     *
     * @var string|null
     */
    protected ?string $order_id;

    /**
     * Application Name variable
     *
     * @var string|null
     */
    protected ?string $app_name;

    /**
     * Local Date Time variable
     *
     * @var DateTime|null
     */
    protected ?DateTime $local_date_time;

    /**
     * System Trace Number variable
     *
     * @var string|null
     */
    protected ?string $system_trace_number;

    /**
     * Retrival Reference Number variable
     *
     * @var string|null
     */
    protected ?string $retrival_reference_number;

    /**
     * Response Description variable
     *
     * @var string|null
     */
    protected ?string $response_description;

    /**
     * Gateway Name function
     *
     * @return string
     */
    public function getName(): string
    {
        return 'sadad';
    }

    /**
     * Set Merchant Id function
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
     * Get Merchant Id function
     *
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return $this->merchant_id;
    }

    /**
     * Set Terminal Id function
     *
     * @param string $terminal_id
     * @return $this
     */
    public function setTerminalId(string $terminal_id): self
    {
        $this->terminal_id = $terminal_id;

        return $this;
    }

    /**
     * Get Terminal Id function
     *
     * @return string|null
     */
    public function getTerminalId(): ?string
    {
        return $this->terminal_id;
    }

    /**
     * Set Terminal Key function
     *
     * @param string $terminal_key
     * @return $this
     */
    public function setTerminalKey(string $terminal_key): self
    {
        $this->terminal_key = $terminal_key;

        return $this;
    }

    /**
     * Get Terminal Key function
     *
     * @return string|null
     */
    public function getTerminalKey(): ?string
    {
        return $this->terminal_key;
    }

    /**
     * Set Order Number function
     *
     * @param string $order_id
     * @return $this
     */
    public function setOrderId(string $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Get Order Id function
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->order_id;
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
     * Set Application Name function
     *
     * @param string $name
     * @return $this
     *
     */
    public function setAppName(string $name): self
    {
        $this->app_name = $name;

        return $this;
    }

    /**
     * Get Application Name function
     *
     * @return string|null
     */
    public function getAppName(): ?string
    {
        return $this->app_name;
    }

    /**
     * Set Local DateTime function
     *
     * @param DateTime $date_time
     * @return $this
     *
     */
    public function setLocalDateTime(DateTime $date_time): self
    {
        $this->local_date_time = $date_time;

        return $this;
    }

    /**
     * Get Local DateTime function
     *
     * @return string|null
     */
    public function getLocalDateTime(): ?DateTime
    {
        return $this->local_date_time;
    }

    /**
     * Set Retrival Reference Number function
     *
     * @param $retrival_reference_number
     * @return $this
     */
    public function setRetrivalReferenceNumber($retrival_reference_number): self
    {
        $this->retrival_reference_number = (string) $retrival_reference_number;

        return $this;
    }

    /**
     * Get Retrival Reference Number function
     *
     * @return string|null
     */
    public function getRetrivalReferenceNumber(): ?string
    {
        return $this->retrival_reference_number;
    }

    /**
     * Set System Trace Number function
     *
     * @param $system_trace_number
     * @return $this
     */
    public function setSystemTraceNumber($system_trace_number): self
    {
        $this->system_trace_number = (string) $system_trace_number;

        return $this;
    }

    /**
     * Get System Trace Number function
     *
     * @return string|null
     */
    public function getSystemTraceNumber(): ?string
    {
        return $this->system_trace_number;
    }

    /**
     * Set Response Description function
     *
     * @param $description
     * @return $this
     */
    public function setResponseDescription(string $description): self
    {
        $this->response_description = $description;

        return $this;
    }

    /**
     * Get Response Description function
     *
     * @return string|null
     */
    public function getResponseDescription(): ?string
    {
        return $this->response_description;
    }

    /**
     * Sign Data function
     *
     * @param string $str
     * @return string
     */
    private function signData(string $str): string
    {
        $key = base64_decode($this->terminal_key);
        $ciphertext = OpenSSL_encrypt($str,"DES-EDE3", $key, OPENSSL_RAW_DATA);

        return base64_encode($ciphertext);
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

        $this->setMerchantId(
            $parameters['merchant_id'] ?? app('config')->get('iranpayment.sadad.merchant_id')
        );
        $this->setTerminalId(
            $parameters['terminal_id'] ?? app('config')->get('iranpayment.sadad.terminal_id')
        );
        $this->setTerminalKey(
            $parameters['terminal_key'] ?? app('config')->get('iranpayment.sadad.terminal_key')
        );

        $this->setAppName($parameters['app_name'] ?? app('config')->get('iranpayment.sadad.app_name'));
        $this->setLocalDateTime($parameters['local_date_time'] ?? Carbon::now());

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.sadad.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        return $this;
    }

    protected function httpRequest(string $url, array $data = [], string $method = "POST") : object
    {
        $curl = new CurlRequest($url, $method);
        $result = $curl->execute(json_encode($data));

        return json_decode($result);
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

        if (empty($this->order_id)) {
            $this->setOrderId($this->getTransactionCode());
        }
    }

    /**
     * @throws GatewayException|SadadException|TransactionFailedException
     */
    public function purchase(): void
    {
        $terminalId = $this->getTerminalId();
        $orderId = $this->getOrderId();
        $preparedAmount = $this->preparedAmount();
        $signData = $this->signData("{$terminalId};{$orderId};{$preparedAmount}");
        $dateTime = $this->getLocalDateTime();
        if ($dateTime) {
            $dateTime = $dateTime->format(Carbon::DEFAULT_TO_STRING_FORMAT);
        }

        $data = [
            'TerminalId' => $terminalId,
            'MerchantId' => $this->getMerchantId(),
            'Amount' => $preparedAmount,
            'SignData' => $signData,
            'ReturnUrl' => $this->getCallbackUrl(),
            'LocalDateTime' => $dateTime,
            'OrderId' => $orderId,
            'UserId' => $this->getMobile(),
            'ApplicationName' => $this->getAppName(),
        ];

        $result = $this->httpRequest(self::SEND_URL, $data);

        if (!isset($result->Token)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        if (isset($result->ResCode) && $result->ResCode != 0) {
            throw SadadException::error($result->ResCode, $result->Description ?? null);
        }

        $this->setToken($result->Token);
        $this->setResponseDescription($result->Description);
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate(
            [],
            [
                'merchant_id' => $this->getMerchantId(),
                'terminal_id' => $this->getTerminalId(),
                'token' => $this->getToken(),
                'app_name' => $this->getAppName(),
                'local_date_time' => $this->getLocalDateTime(),
                'purchase_description' => $this->getResponseDescription(),
            ]
        );

        parent::postPurchase();
    }

    /**
     * Pay Link function
     *
     * @return string
     */
    public function purchaseUri(): string
    {
        return str_replace('{token}', $this->getToken(), self::TOKEN_URL);
    }

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'بانک ملی - پرداخت الکترونیک سداد',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/sadad.png',
            'bank_url' => $this->purchaseUri(),
            'method' => 'GET',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (isset($this->request['ResCode']) && $this->request['ResCode'] != 0) {
            throw SadadException::error($this->request['ResCode'], $this->request['Description']);
        }

        $token = $this->getTransaction()->gateway_data['token'] ?? null;
        if (!isset($token)) {
            throw SadadException::error(-1);
        }

        if (isset($this->request['Token']) && $this->request['Token'] !== $token) {
            throw SadadException::error(-1);
        }

        $this->setToken($token);
        $this->setOrderId($this->getTransactionCode());
    }

    /**
     * @throws GatewayException|SadadException|TransactionFailedException
     */
    public function verify(): void
    {
        $token = $this->getToken();

        $data = [
            'Token' => $token,
            'SignData' => $this->signData($token),
        ];

        $result = $this->httpRequest(self::VERIFY_URL, $data);
        if (
            !isset(
                $result->Amount,
                $result->SystemTraceNo,
                $result->RetrivalRefNo,
                $result->ResCode
            )
        ) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        if ($result->ResCode != 0) {
            throw SadadException::error($result->ResCode);
        }

        if (intval($result->Amount) !== $this->preparedAmount()) {
            throw SadadException::error(1101);
        }

        $this->setSystemTraceNumber($result->SystemTraceNo);
        $this->setRetrivalReferenceNumber($result->RetrivalRefNo);
        $this->setResponseDescription($result->Description);
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate(
            [
                'tracking_code' => $this->getSystemTraceNumber(),
                'reference_number' => $this->getRetrivalReferenceNumber(),
            ],
            [
                'system_trace_number' => $this->getSystemTraceNumber(),
                'retrival_reference_number' => $this->getRetrivalReferenceNumber(),
                'verify_description' => $this->getResponseDescription(),
            ]
        );

        parent::postVerify();
    }
}
