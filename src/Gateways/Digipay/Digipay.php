<?php

namespace Dena\IranPayment\Gateways\Digipay;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Digipay extends AbstractGateway implements GatewayInterface
{
    private const LOGIN_URL = "https://api.mydigipay.com/digipay/api/oauth/token";
    private const REQUEST_URL = "https://api.mydigipay.com/digipay/api/tickets/business?type={ticketType}";
    private const VERIFY_URL = "https://api.mydigipay.com/digipay/api/purchases/verify/{trackingCode}?type={ticketType}";
    private const DELIVER_URL = "https://api.mydigipay.com/digipay/api/purchases/deliver?type={ticketType}";
    public const CURRENCY = Currency::IRR;

    /**
     * Provider ID variable
     *
     * @var string|null
     */
    protected ?string $provider_id;

    /**
     * Payment Url variable
     *
     * @var string|null
     */
    protected ?string $payment_url;

    /**
     * Tracking Code variable
     *
     * @var string|null
     */
    protected ?string $tracking_code;

    /**
     * Ticket variable
     *
     * @var string|null
     */
    protected ?string $ticket;

    /**
     * Ticket Type variable
     *
     * @var int|null
     */
    protected ?int $ticket_type;

    /**
     * Username variable
     *
     * @var string|null
     */
    protected ?string $username;

    /**
     * Password variable
     *
     * @var string|null
     */
    protected ?string $password;

    /**
     * Grant Type variable
     *
     * @var string|null
     */
    protected ?string $grant_type;

    /**
     * Client Id variable
     *
     * @var string|null
     */
    protected ?string $client_id;

    /**
     * Client Secret variable
     *
     * @var string|null
     */
    protected ?string $client_secret;

    /**
     * Access Token variable
     *
     * @var string|null
     */
    protected ?string $access_token;

    /**
     * Gateway Transaction Data variable
     *
     * @var array|null
     */
    protected ?array $gateway_transaction_data = null;

    /**
     * Gateway Name function
     *
     * @return string
     */
    public function getName(): string
    {
        return 'digipay';
    }

    /**
     * Set Ticket function
     *
     * @param string $ticket
     * @return $this
     */
    public function setTicket(string $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get Ticket function
     *
     * @return string|null
     */
    public function getTicket(): ?string
    {
        return $this->ticket;
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

    /**
     * Set Provider ID function
     *
     * @param string $provider_id
     * @return $this
     */
    public function setProviderId(string $provider_id): self
    {
        $this->provider_id = $provider_id;

        return $this;
    }

    /**
     * Get Provider ID function
     *
     * @return string|null
     */
    public function getProviderId(): ?string
    {
        return $this->provider_id;
    }

    /**
     * Set Tracking Code function
     *
     * @param string $tracking_code
     * @return $this
     */
    public function setTrackingCode(string $tracking_code): self
    {
        $this->tracking_code = $tracking_code;

        return $this;
    }

    /**
     * Get Tracking Code function
     *
     * @return string|null
     */
    public function getTrackingCode(): ?string
    {
        return $this->tracking_code;
    }

    /**
     * Set Ticket Type function
     *
     * @param int $ticket_type
     * @return $this
     */
    public function setTicketType(int $ticket_type): self
    {
        $this->ticket_type = $ticket_type;

        return $this;
    }

    /**
     * Get Ticket Type function
     *
     * @return int|null
     */
    public function getTicketType(): ?int
    {
        return $this->ticket_type;
    }

    /**
     * Set Username function
     *
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get Username function
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set Grant Type function
     *
     * @param string $grant_type
     * @return $this
     */
    public function setGrantType(string $grant_type = 'password'): self
    {
        $this->grant_type = $grant_type;

        return $this;
    }

    /**
     * Get Grant Type function
     *
     * @return string|null
     */
    public function getGrantType(): ?string
    {
        return $this->grant_type;
    }

    /**
     * Set Password function
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get Password function
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set Client Id function
     *
     * @param string $client_id
     * @return $this
     */
    public function setClientId(string $client_id): self
    {
        $this->client_id = $client_id;

        return $this;
    }

    /**
     * Get Client Id function
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    /**
     * Set Client Secret function
     *
     * @param string $client_secret
     * @return $this
     */
    public function setClientSecret(string $client_secret): self
    {
        $this->client_secret = $client_secret;

        return $this;
    }

    /**
     * Get Client Secret function
     *
     * @return string|null
     */
    public function getClientSecret(): ?string
    {
        return $this->client_secret;
    }

    /**
     * Set Access Token function
     *
     * @param string $access_token
     * @return $this
     */
    public function setAccessToken(string $access_token): self
    {
        $this->access_token = $access_token;

        return $this;
    }

    /**
     * Get Access Token function
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    /**
     * Set Gateway Transaction Data function
     *
     * @param array $gateway_transaction_data
     * @return $this
     */
    public function setGatewayTransactionData(array $gateway_transaction_data): self
    {
        $this->gateway_transaction_data = $gateway_transaction_data;

        return $this;
    }

    /**
     * Get Gateway Transaction Data function
     *
     * @return array|null
     */
    public function getGatewayTransactionData(): ?array
    {
        return $this->gateway_transaction_data;
    }

    /**
     * Initialize function
     *
     * @param array $parameters
     * @return $this
     * @throws InvalidDataException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DigipayException
     * @throws GatewayException
     */
    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);
        $this->setClientId($parameters['client_id'] ?? app('config')->get('iranpayment.digipay.client_id'));
        $this->setClientSecret($parameters['client_secret'] ?? app('config')->get('iranpayment.digipay.client_secret'));
        $this->setUsername($parameters['username'] ?? app('config')->get('iranpayment.digipay.username'));
        $this->setPassword($parameters['password'] ?? app('config')->get('iranpayment.digipay.password'));
        $this->setGrantType($parameters['grant_type'] ?? app('config')->get('iranpayment.digipay.grant_type'));
        $this->setTicketType($parameters['ticket_type'] ?? app('config')->get('iranpayment.digipay.ticket_type'));
        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.digipay.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        $this->oauth();

        return $this;
    }

    /**
     * @throws InvalidDataException
     */
    protected function prePurchase(): void
    {
        parent::prePurchase();

        if ($this->preparedAmount() < 100 || $this->preparedAmount() > 500000000) {
            throw InvalidDataException::invalidAmount();
        }

        if (is_null($this->getTransactionCode())) {
            throw InvalidDataException::invalidCode();
        }

        $this->setProviderId($this->getTransactionCode());
    }

    public function purchase(): void
    {
        $data = [
            'cellNumber' => $this->getMobile(), // e.g. 09xxxxxxxxx
            'amount' => $this->preparedAmount(),
            'providerId' => $this->getProviderId(),
            'callbackUrl' => $this->preparedCallbackUrl()
        ];

        try {
            $endpoint = str_replace('{ticketType}', $this->getTicketType(), self::REQUEST_URL);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent: WEB',
                'Digipay-Version: 2022-02-02',
                'Authorization: Bearer ' . $this->getAccessToken(),
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code != 200 && isset($result['result']['status']) && $result['result']['status'] !== 0) {
            throw DigipayException::error($result['result']['status']);
        }

        $this->setPaymentUrl($result['redirectUrl']);
        $this->setTicket($result['ticket']);
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getTicket(),
        ]);

        parent::postPurchase();
    }

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
            'title' => 'دیجی پی',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/digipay.png',
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

        if (! isset($this->request['type']) && empty($this->request['type'])) {
            throw DigipayException::error(9000);
        }

        if (! isset($this->request['trackingCode']) && empty($this->request['trackingCode'])) {
            throw DigipayException::error(9000);
        }

        $this->setTicketType($this->request['type']);
        $this->setTrackingCode($this->request['trackingCode']);
    }

    /**
     * @throws GatewayException
     * @throws DigipayException
     */
    public function verify(): void
    {
        try {
            $endpoint = str_replace(
                ['{trackingCode}', '{ticketType}'],
                [$this->getTrackingCode(), $this->getTicketType()],
                self::VERIFY_URL
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->getAccessToken(),
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }
            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code != 200 && $result['result']['status'] !== 0) {
            throw DigipayException::error($result['result']['status']);
        }

        $this->setTrackingCode($result["trackingCode"]);

        $this->deliver($this->getTrackingCode(), $this->getTicketType());

        $this->setGatewayTransactionData([
            'rrn' => $result['rrn'] ?? null,
            'fpCode' => $result['fpCode'] ?? null,
            'fpName' => $result['fpName'] ?? null,
            'amount' => $result['amount'] ?? null,
            'paymentGateway' => $result['paymentGateway'] ?? null,
            'additionalInfo' => $result['additionalInfo'] ?? null,
            'finalizeDate' => $result['finalizeDate'] ?? null,
        ]);
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate(
            [
                'tracking_code' => $this->getTrackingCode(),
            ],
            $this->getGatewayTransactionData() ?? []
        );

        parent::postVerify();
    }

    public function deliver(string $trackingCode, int $type): void
    {
        $data = [
            'deliveryDate' => now()->timestamp,
            'invoiceNumber' => $this->getPayableId(),
            'trackingCode' => $trackingCode,
            'products' => [$this->transaction->description]
        ];

        try {
            $endpoint = str_replace('{ticketType}', $type, self::DELIVER_URL);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent: WEB',
                'Digipay-Version: 2022-02-02',
                'Authorization: Bearer ' . $this->getAccessToken(),
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
                throw GatewayException::connectionProblem(new \Exception($ch_error));
            }

            $result = json_decode($response, true);
        } catch (\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code != 200 && isset($result['result']['status']) && $result['result']['status'] !== 0) {
            throw DigipayException::error($result['result']['status']);
        }
    }

    /**
     * @return void
     * @throws DigipayException
     * @throws GatewayException
     */
    private function oauth(): void
    {
        $fields = [
            'username'   => $this->getUsername(),
            'password'   => $this->getPassword(),
            'grant_type' => $this->getGrantType(),
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::LOGIN_URL);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->getClientId()}:{$this->getClientSecret()}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                 'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode("{$this->getClientId()}:{$this->getClientSecret()}"),
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
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

            $result = json_decode($response);
        } catch(\Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($http_code !== 200) {
            throw GatewayException::connectionProblem(new \Exception((string) $http_code));
        }

        if (! isset($result->access_token)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setAccessToken($result->access_token);
    }
}
