<?php

namespace Dena\IranPayment\Gateways\BehPardakht;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Illuminate\Support\Carbon;
use SoapClient;
use Exception;
use SoapFault;

class BehPardakht extends AbstractGateway implements GatewayInterface
{
    private const SEND_URL    = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    private const PAYMENT_URL = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';
    private const VERIFY_URL  = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    private const CURRENCY    = Currency::IRR;

    /**
     * Terminal ID variable
     *
     * @var int|null
     */
    protected ?int $terminal_id;

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
     * Order ID variable
     *
     * @var int|null
     */
    protected ?int $order_id;

    /**
     * Ref Id variable
     *
     * @var string|null
     */
    protected ?string $ref_id;

    /**
     * Gateway Transaction Data variable
     *
     * @var array|null
     */
    protected ?array $gateway_transaction_data = null;

    public function getName(): string
    {
        return 'behpardakht';
    }

    /**
     * Set Terminal Id function
     *
     * @param int $terminal_id
     * @return $this
     */
    public function setTerminalId(int $terminal_id): self
    {
        $this->terminal_id = $terminal_id;

        return $this;
    }

    /**
     * Get Terminal Id function
     *
     * @return int|null
     */
    public function getTerminalId(): ?int
    {
        return $this->terminal_id;
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
     * Set Order Id function
     *
     * @param int $order_id
     * @return $this
     */
    public function setOrderId(int $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Get Order Id function
     *
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    /**
     * Set Ref Id function
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
     * Get Ref Id function
     *
     * @return string|null
     */
    public function getRefId(): ?string
    {
        return $this->ref_id;
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

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);

        $this->setTerminalId(app('config')->get('iranpayment.behpardakht.terminal-id'));
        $this->setUsername(app('config')->get('iranpayment.behpardakht.username'));
        $this->setPassword(app('config')->get('iranpayment.behpardakht.password'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.behpardakht.callback-url')
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

        if ($this->preparedAmount() < 100) {
            throw InvalidDataException::invalidAmount();
        }

        // crc32($this->getTransactionCode())
        $this->setOrderId($this->getTransaction()->id);
    }

    public function purchase(): void
    {
        try {
            $soap = new SoapClient(self::SEND_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            $result = $soap->bpPayRequest([
                'terminalId' => $this->getTerminalId(),
                'userName' => $this->getUsername(),
                'userPassword' => $this->getPassword(),
                'callBackUrl' => $this->getCallbackUrl(),
                'amount' => $this->preparedAmount(),
                'localDate' => Carbon::now()->format('Ymd'),
                'localTime' => Carbon::now()->format('Gis'),
                'orderId' => $this->getOrderId(),
                'mobileNo' => $this->mobileReformat($this->getMobile()),
                'additionalData' => 'behpardakht',
                'payerId' => 0
            ]);
        } catch(SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        // fault has happened in bank gateway
        if ($result->return == 21) {
            throw BehPardakhtException::error(21);
        }

        list($resCode, $refId) = explode(',', $result->return);

        // purchase was not successful
        if ($resCode != '0') {
            throw BehPardakhtException::error((int)$resCode);
        }

        $this->setRefId($refId);
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getRefId(),
        ]);

        parent::postPurchase();
    }

    /**
     * @return string
     * @throws GatewayException
     */
    public function purchaseUri(): string
    {
        throw GatewayException::notSupportedMethod();
    }

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'بانک ملت (به پرداخت)',
            'image' => 'https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/behpardakht.png',
            'bank_url' => self::PAYMENT_URL,
            'method' => 'POST',
            'form_data' => [
                'RefId' => $this->getRefId(),
                'MobileNo' => $this->mobileReformat($this->getMobile()),
            ],
        ];
    }

    public function preVerify(): void
    {
        parent::preVerify();

        if (! isset($this->request['ResCode']) && empty($this->request['ResCode'])) {
            throw BehPardakhtException::error(44);
        }

        if (isset($this->request['ResCode']) && $this->request['ResCode'] != '0') {
            throw BehPardakhtException::error((int)$this->request['ResCode']);
        }

        if (
            (isset($this->request['RefId']) && $this->request['RefId'] != $this->getTransaction()->reference_number)
            &&
            (isset($this->request['SaleOrderId']) && $this->request['SaleOrderId'] != $this->getTransaction()->id )
        ) {
            throw BehPardakhtException::error(44);
        }
    }

    public function verify(): void
    {
        // verify purchase request
        $orderId = $this->request['SaleOrderId'] ?? null;
        $verifySaleOrderId = $this->request['SaleOrderId'] ?? null;
        $verifySaleReferenceId = $this->request['SaleReferenceId'] ?? null;

        $data = [
            'terminalId' => $this->getTerminalId(),
            'userName' => $this->getUsername(),
            'userPassword' => $this->getPassword(),
            'orderId' => $orderId,
            'saleOrderId' => $verifySaleOrderId,
            'saleReferenceId' => $verifySaleReferenceId
        ];

        try {
            $soap = new SoapClient(self::VERIFY_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            // step1: verify request
            $verifyResponse = (int)$soap->bpVerifyRequest($data)->return;
            if ($verifyResponse != 0) {
                // rollback money and throw exception
                // avoid rollback if request was already verified
                if ($verifyResponse != 43) {
                    $soap->bpReversalRequest($data);
                }

                throw BehPardakhtException::error($verifyResponse);
            }

            // step2: settle request
            $settleResponse = $soap->bpSettleRequest($data)->return;
            if ($settleResponse != 0) {
                // rollback money and throw exception
                // avoid rollback if request was already settled/reversed
                if ($settleResponse != 45 && $settleResponse != 48) {
                    $soap->bpReversalRequest($data);
                }

                throw BehPardakhtException::error($settleResponse);
            }

        } catch (BehPardakhtException $exception) {
            throw $exception;
        } catch (SoapFault|Exception $exception) {
            throw GatewayException::connectionProblem($exception);
        }

        $this->setGatewayTransactionData([
            'ResCode' => $this->request['ResCode'] ?? null,
            'SaleOrderId' => $this->request['SaleOrderId'] ?? null,
            'SaleReferenceId' => $this->request['SaleReferenceId'] ?? null,
        ]);
    }

    protected function postVerify(): void
    {
        $this->transactionUpdate(
            [
                'tracking_code' => $this->request['SaleReferenceId'] ?? null,
                'card_number' => $this->request['CardHolderPan'] ?? null
            ],
            $this->getGatewayTransactionData() ?? []
        );

        parent::postVerify();
    }

    protected function mobileReformat($mobile)
    {
        return str_replace('09', '989', $mobile);
    }
}