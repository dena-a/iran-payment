<?php
/**
 * Api Version: ?
 * Api Document Date: 2020/08/03
 * Last Update: 2020/08/03
 */

namespace Dena\IranPayment\Gateways\Sadad;

use Carbon\Carbon;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\IranPaymentException;
use Dena\IranPayment\Exceptions\TransactionFailedException;
use Dena\IranPayment\Gateways\Sadad\SadadException;
use Dena\IranPayment\Helpers\Currency;

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

    private function signData($str = null): string
    {
        $str = $str ?? $this->terminal_id.";".$this->order_id.";".$this->preparedAmount();
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

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.sadad.callback-url')
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

        if (empty($this->order_id)) {
            $this->setOrderId($this->getTransaction()->id);
        }
	}

    /**
     * @throws GatewayException|SadadException|TransactionFailedException
     */
	public function purchase(): void
	{   
        $data = json_encode([
            'TerminalId' => $this->terminal_id,
            'MerchantId' => $this->merchant_id,
            'Amount' => $this->preparedAmount(),
            'SignData' => $this->signData(),
            'ReturnUrl' => $this->callback_url,
            'LocalDateTime' => Carbon::now(),
            'OrderId' => $this->order_id,
            'UserId' => $this->getMobile(),
            'ApplicationName' => app('config')->get('iranpayment.app_name') ?? null
        ]);

		try {
            $curl = curl_init(self::SEND_URL);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");  
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->getGatewayRequestOptions()['connection_timeout'] ?? 60);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                ['Content-Type: application/json','Content-Length: ' . strlen($data)]
            );
            $result = curl_exec($curl);
            $ch_error = curl_error($curl);
            curl_close($curl);

			if ($ch_error) {
				throw GatewayException::connectionProblem(new Exception($ch_error));
            }

			$result = json_decode($result);
		} catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
		}

		if(!isset($result->Token)) {
			if (isset($result->ResCode) && $result->ResCode != 0) {
                throw SadadException::error($result->ResCode, $result->Description ?? null);
			}

			throw GatewayException::unknownResponse($result);
		}

        $this->setToken($result->Token);
        $this->setDescription($result->Description);
	}

	protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getToken(),
            'description' => $this->getDescription(),
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
            'title' => 'بانک ملی - پرداخت الکترونیک سداد',
            'image' =>
                'https://raw.githubusercontent.com/
                dena-a/iran-payment/master/resources/assets/img/sadad.png',
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

        if (isset($this->request['Token']) && $this->request['Token'] !== $this->getReferenceNumber()) {
            throw SadadException::error(-1);
        }

        $this->setToken($this->getReferenceNumber());
        $this->setOrderId($this->request['OrderId'] ?? $this->getTransactionCode());
    }

    /**
     * @throws GatewayException|SadadException|TransactionFailedException
     */
	public function verify(): void
	{
        $token = $this->getToken();

		$data = json_encode([
            'Token'	=> $token,
            'SignData' => $this->signData($token),
        ]);

		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch, 
                CURLOPT_HTTPHEADER,
                ['Content-Type: application/json', 'Content-Length: '.strlen($data)]
            );
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->getGatewayRequestOptions()['timeout'] ?? 30);
            curl_setopt(
                $ch,
                CURLOPT_CONNECTTIMEOUT,
                $this->getGatewayRequestOptions()['connection_timeout'] ?? 60
            );
			$result	= curl_exec($ch);
			$ch_error = curl_error($ch);
			curl_close($ch);

			if ($ch_error) {
                throw GatewayException::connectionProblem(new Exception($ch_error));
			}

			$result = json_decode($result);
        } catch(Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (
            !isset(
                $result->Amount,
                $result->SystemTraceNo,
                $result->RetrivalRefNo,
                $result->ResCode
            )
        ) {
            throw GatewayException::unknownResponse($result);
        }

        if ($result->ResCode != 0) {
            throw SadadException::error($result->ResCode);
        }

		if (intval($result->Amount) !== $this->preparedAmount()) {
            throw SadadException::error(1101);
		}

        $this->setSystemTraceNumber($result->SystemTraceNo);
        $this->setRetrivalReferenceNumber($result->RetrivalRefNo);
        $this->setDescription($result->Description);
	}

    protected function postVerify(): void
    {
        $this->transactionUpdate([
            'tracking_code' => $this->getTrackingCode(),
            'reference_number' => $this->getReferenceNumber(),
        ]);

        parent::postVerify();
    }
}
