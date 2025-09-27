<?php
/**
 * Api Version: 2022-02-02
 * Api Document Date: 1402/11/25
 * Last Update: 2025/07/26
 */

namespace Dena\IranPayment\Gateways\Tara;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayRefundableInterface;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Tara extends AbstractGateway implements GatewayInterface, GatewayRefundableInterface
{
    private const LOGIN_URL = 'https://pay.tara360.ir/pay/api/v2/authenticate';

    private const TOKEN_URL = 'https://pay.tara360.ir/pay/api/getToken';

    private const SEND_URL = 'https://pay.tara360.ir/pay/api/ipgPurchase';

    private const VERIFY_URL = 'https://pay.tara360.ir/pay/api/purchaseVerify';

    private const REFUND_AUTH_URL = 'https://club.tara-club.ir/club/api/v1/user/login/refund';

    private const REFUND_UNLIMITED_URL = 'https://club.tara-club.ir/club/api/v1/user/purchase/limited/refund';

    private const REFUND_LIMITED_URL = 'https://club.tara-club.ir/club/api/v1/user/purchase/limited/refund/partial';

    public const CURRENCY = Currency::IRR;

    /**
     * Username variable
     */
    protected string $username;

    /**
     * Password variable
     */
    protected string $password;

    /**
     * Access Token variable
     */
    protected ?string $access_token;

    /**
     * Gateway Transaction Data variable
     */
    protected ?array $gateway_transaction_data = null;

    /**
     * Token Payment Uri
     */
    protected ?string $token_payment_url = null;

    /**
     * Token Verify Uri
     */
    protected ?string $token_verify_url = null;

    /**
     * Tracking Code variable
     */
    protected ?string $tracking_code;

    /**
     * Valid Ip Tara
     */
    protected ?string $tara_valid_ip = null;

    /**
     * Tara Service Amount List
     */
    protected array $tara_service_amount_list = [];

    /**
     * Tara Items
     */
    protected array $tara_items = [];

    /**
     * Tara Invoice Details
     */
    protected array $tara_extra_invoice_details = [];

    /**
     * Gateway Name function
     */
    public function getName(): string
    {
        return 'tara';
    }

    /**
     * Set Tracking Code function
     *
     * @return $this
     */
    public function setTrackingCode(string $tracking_code): self
    {
        $this->tracking_code = $tracking_code;

        return $this;
    }

    /**
     * Get Tracking Code function
     */
    public function getTrackingCode(): ?string
    {
        return $this->tracking_code;
    }

    /**
     * Set Tara Valid Ip function
     *
     * @return $this
     */
    public function setTaraValidIp(string $tara_valid_ip): self
    {
        $this->tara_valid_ip = $tara_valid_ip;

        return $this;
    }

    /**
     * Get Tara Valid Ip function
     */
    public function getTaraValidIp(): ?string
    {
        return $this->tara_valid_ip;
    }

    /**
     * Set Valid Ip Tara function
     *
     * @return $this
     */
    public function setTokenPaymentUrl(string $token_payment_url): self
    {
        $this->token_payment_url = $token_payment_url;

        return $this;
    }

    /**
     * Get Valid Ip Tara function
     */
    public function getTokenPaymentUrl(): ?string
    {
        return $this->token_payment_url;
    }

    /**
     * Set Valid Ip Tara function
     *
     * @return $this
     */
    public function setTokenVerifyUrl(string $token_verify_url): self
    {
        $this->token_verify_url = $token_verify_url;

        return $this;
    }

    /**
     * Get Valid Ip Tara function
     */
    public function getTokenVerifyUrl(): ?string
    {
        return $this->token_verify_url;
    }

    /**
     * Set Username function
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get Username function
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set Password function
     *
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get Password function
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set Access Token function
     *
     * @return $this
     */
    public function setAccessToken(string $access_token): self
    {
        $this->access_token = $access_token;

        return $this;
    }

    /**
     * Get Access Token function
     */
    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    /**
     * Set Gateway Transaction Data function
     *
     * @return $this
     */
    public function setGatewayTransactionData(array $gateway_transaction_data): self
    {
        $this->gateway_transaction_data = $gateway_transaction_data;

        return $this;
    }

    /**
     * Get Gateway Transaction Data function
     */
    public function getGatewayTransactionData(): ?array
    {
        return $this->gateway_transaction_data;
    }

    /**
     * Set Tara Service Amount List function
     *
     * @return $this
     */
    public function setTaraServiceAmountList(array $tara_service_amount_list): self
    {
        $this->tara_service_amount_list = $tara_service_amount_list;

        return $this;
    }

    /**
     * Get Tara Service Amount List function
     */
    public function getTaraServiceAmountList(): ?array
    {
        return $this->tara_service_amount_list;
    }

    /**
     * Set Tara Items function
     *
     * @return $this
     */
    public function setTaraItems(array $tara_items): self
    {
        $this->tara_items = $tara_items;

        return $this;
    }

    /**
     * Get Tara Items function
     */
    public function getTaraItems(): ?array
    {
        return $this->tara_items;
    }

    /**
     * Set Tara Invoice Details function
     *
     * @return $this
     */
    public function setTaraExtraInvoiceDetails(array $tara_extra_invoice_details): self
    {
        $this->tara_extra_invoice_details = $tara_extra_invoice_details;

        return $this;
    }

    /**
     * Get Tara Invoice Details function
     */
    public function getTaraExtraInvoiceDetails(): ?array
    {
        return $this->tara_extra_invoice_details;
    }

    /**
     * Initialize function
     *
     * @return $this
     *
     * @throws InvalidDataException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TaraException
     * @throws GatewayException
     */
    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);

        $this->setUsername($parameters['username'] ?? app('config')->get('iranpayment.tara.username'));
        $this->setPassword($parameters['password'] ?? app('config')->get('iranpayment.tara.password'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.tara.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        $this->setTaraValidIp(app('config')->get('iranpayment.tara.valid_ip') ?? request()->ip());

        $this->loginAuth();

        return $this;
    }

    /**
     * @throws InvalidDataException
     */
    protected function prePurchase(): void
    {
        parent::prePurchase();

        if ($this->preparedAmount() < 1200 || $this->preparedAmount() > 1000000000) {
            throw InvalidDataException::invalidAmount();
        }

        if (is_null($this->getTransactionCode())) {
            throw InvalidDataException::invalidCode();
        }
    }

    public function purchase(): void
    {
        $taraExtraInvoiceDetails = $this->getTaraExtraInvoiceDetails();
        $data = [
             'ip'                  => $this->getTaraValidIp(),
             'callBackUrl'         => $this->preparedCallbackUrl(),
             'amount'              => (string)$this->preparedAmount(),
             'mobile'              => $this->getMobile(),
             'orderId'             => $this->getTransactionCode(),
             'taraInvoiceItemList' => $this->getTaraItems(),
             'serviceAmountList'   => $this->getTaraServiceAmountList(),
             'vat'                 => (int) isset($taraExtraInvoiceDetails['vat'])? $taraExtraInvoiceDetails['vat'] : '0',
             'additionalData'      => (int) isset($taraExtraInvoiceDetails['additionalData'])? $taraExtraInvoiceDetails['additionalData'] : null,
         ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::TOKEN_URL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent: WEB',
                'Authorization: Bearer '.$this->getAccessToken(),
                'Content-Type: application/json',
                'Accept: application/json',
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (($http_code != 200 && isset($result['result']) && $result['result'] !== '0') || empty($result['token'])) {
            throw TaraException::error($result['result']);
        }

        $this->setTokenPaymentUrl($result['token']);
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getTokenPaymentUrl(),
        ]);

        parent::postPurchase();
    }

    public function purchaseUri(): string
    {
        return self::SEND_URL ."?username=" . $this->getUsername(). "&token=" .$this->getTokenPaymentUrl();
    }

    /**
     * Purchase View Params function
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'تارا',
            'image' => 'https://cdn.drdr.ir/public/gateway/tara.png',
            'bank_url' => $this->purchaseUri(),
            'method' => 'POST',
        ];
    }

    /**
     * @throws IranPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (! isset($this->request['result']) && empty($this->request['result'])) {
            throw InvalidRequestException::notFound();
        }

        if ($this->request['result'] !== '0') {
            throw TaraException::error($this->request['result']);
        }

        $this->setTokenVerifyUrl($this->request['token']);
    }

    /**
     * @throws GatewayException
     * @throws TaraException
     */
    public function verify(): void
    {
        try {
            $data = [
                'ip'    => $this->getTaraValidIp(),
                'token' => $this->getTokenVerifyUrl(),
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent: WEB',
                'Authorization: Bearer '.$this->getAccessToken(),
                'Content-Type: application/json',
                'Accept: application/json',
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (($http_code != 200 && isset($result['result']) && $result['result'] !== '0') || empty($result['token'])) {
            throw TaraException::error($result['result']);
        }

        $this->setTrackingCode($this->request['channelRefNumber']);

        $this->setGatewayTransactionData([
             'serviceAmountList'   => $result['serviceAmountList'] ?? null,
             'amount'              => $result['amount'] ?? null,
             'type'                => $result['type'] ?? null,
             'rrn'                 => $result['rrn'] ?? null,
             'channelRefNumber'    => $this->request['channelRefNumber'],
             'additionalData'      => $this->request['additionalData'] ?? null,
             'token_verify'        => $result['token'],
             'orderId'             => $this->request['orderId'] ?? null,
             'desc'                => $this->request['desc'] ?? $result['description'] ?? null,
        ]);
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate(
            params:      [
                             'tracking_code' => $this->getTrackingCode(),
                         ],
            gatewayData: $this->getGatewayTransactionData() ?? []
        );

        parent::postVerify();
    }

    /**
     * @throws TaraException
     * @throws GatewayException
     */
    private function loginAuth(): void
    {
        $fields = [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::LOGIN_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ch_error = curl_error($ch);
            curl_close($ch);

            if ($ch_error) {
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code !== 200) {
            throw GatewayException::connectionProblem(new \Exception((string) $http_code));
        }

        if (empty($result['accessToken'])) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setAccessToken($result['accessToken']);
    }

    /**
     * @param bool $limitRefund
     * @param int $limitRefundAmount
     * @param array $refundItems
     * @param string $description
     * @return array
     * @throws ContainerExceptionInterface
     * @throws GatewayException
     * @throws NotFoundExceptionInterface
     * @throws TaraException
     * @throws TransactionNotFoundException
     */
    public function refund(
        bool $limitRefund = false,
        int $limitRefundAmount = 0,
        array $refundItems = [],
        string $description = ''
    ): array {
        $transaction = $this->payableTransactions(
            $this->getPayableId(),
            null,
            IranPaymentTransaction::T_SUCCEED
        );
        if (count($transaction) === 0) {
            throw new TransactionNotFoundException;
        }

        $this->setTransaction($transaction->first());
        $this->setTrackingCode($transaction->first()->tracking_code);

        if ($limitRefund) {
            return $this->refundSomeItemsInvoice(
                $limitRefundAmount,
                $description,
                $refundItems
            );
        }

        return $this->refundAllItemsInvoice($description);
    }

    /**
     * @param string $description
     * @return array
     * @throws ContainerExceptionInterface
     * @throws GatewayException
     * @throws NotFoundExceptionInterface
     * @throws TaraException
     */
    private function refundAllItemsInvoice(string $description): array
    {
        $data = [
            'description' => $description,
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::REFUND_UNLIMITED_URL.'/'.$this->getTrackingCode());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent: WEB',
                'Authorization: Bearer '.$this->loginRefundAuth(),
                'Content-Type: application/json',
                'Accept: application/json',
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code != 200 && isset($result['success']) && $result['success'] !== true) {
            throw TaraException::error(intval($result['data']['code'] ?? 2299));
        }

        $this->transactionUpdate([
            'gateway_data' => array_merge([
                'gateway_data' => (array) $this->getTransaction()->gateway_data ?? []
            ], [
                'refundType' => 'unlimited',
                'refundStatus' => $http_code,
                'refundResponse' => $result,
            ]),
        ]);

        return $result;
    }

    /**
     * @param int $amount
     * @param string $description
     * @param array $refundItems
     * @return array
     * @throws ContainerExceptionInterface
     * @throws GatewayException
     * @throws NotFoundExceptionInterface
     * @throws TaraException
     */
    private function refundSomeItemsInvoice(
        int $amount,
        string $description,
        array $refundItems
    ): array {
        $data = [
            'amount'      => $amount,
            'items'       => $refundItems, // optional
            'description' => $description,
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::REFUND_LIMITED_URL.'/'.$this->getTrackingCode());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent: WEB',
                'Authorization: Bearer '.$this->loginRefundAuth(),
                'Content-Type: application/json',
                'Accept: application/json',
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code != 200 && isset($result['success']) && $result['success'] !== true) {
            throw TaraException::error(intval($result['data']['code'] ?? 2299));
        }

        $this->transactionUpdate([
            'gateway_data' => array_merge([
                'gateway_data' => (array) $this->getTransaction()->gateway_data ?? []
            ], [
                'refundType' => 'limited',
                'refundStatus' => $http_code,
                'refundResponse' => $result,
            ]),
        ]);

        return $result;
    }

    /**
     * @return string
     * @throws ContainerExceptionInterface
     * @throws GatewayException
     * @throws NotFoundExceptionInterface
     */
    private function loginRefundAuth(): string
    {
        $fields = [
            'principal' => app('config')->get('iranpayment.tara.refund.username'),
            'password'  => app('config')->get('iranpayment.tara.refund.password'),
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::REFUND_AUTH_URL);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ch_error = curl_error($ch);
            curl_close($ch);

            if ($ch_error) {
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code !== 200) {
            throw GatewayException::connectionProblem(new \Exception((string) $http_code));
        }

        if (empty($result['accessCode'])) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        return $result['accessCode'];
    }
}
