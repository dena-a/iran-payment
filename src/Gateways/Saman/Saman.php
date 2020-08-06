<?php
/**
 * Api Version: ?
 * Api Document Date: 1398/04/01
 * Last Update: 2020/08/03
 */

namespace Dena\IranPayment\Gateways\Saman;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\InvalidGatewayMethodException;

use Dena\IranPayment\Helpers\Currency;

use Exception;
use SoapFault;
use SoapClient;

/**
 * 'Saman'
 *
 * @method setTrackingCode($code)
 * @method setCardNumber($number)
 * @method setReferenceNumber($number)
 */
class Saman extends AbstractGateway implements GatewayInterface
{
    private const TOKEN_URL = 'https://sep.shaparak.ir/Payments/InitPayment.asmx?wsdl';
    private const PAYMENT_URL = 'https://sep.shaparak.ir/Payment.aspx';
    private const VERIFY_URL = 'https://verify.sep.ir/Payments/ReferencePayment.asmx?wsdl';
    private const CURRENCY = Currency::IRR;

    /**
     * Merchant ID variable
     *
     * @var string|null
     */
    protected ?string $merchant_id;

    /**
     * ResNum variable
     *
     * @var string|null
     */
    protected ?string $res_num;

    /**
     * Token variable
     *
     * @var string|null
     */
    protected ?string $token;

    /**
     * Gateway Name function
     *
     * @return string
     */
    public function getName(): string
    {
        return 'saman';
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
     * Set ResNum function
     *
     * @param string|null $res_num
     * @return $this
     */
    public function setResNum(string $res_num): self
    {
        $this->res_num = $res_num;

        return $this;
    }

    /**
     * Get ResNum function
     *
     * @return string|null
     */
    public function getResNum(): ?string
    {
        return $this->res_num;
    }

    /**
     * Set Token function
     *
     * @param string|null $token
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

	    $this->setMerchantId(app('config')->get('iranpayment.saman.merchant-id'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.saman.callback-url')
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

        $this->setResNum($this->getTransactionCode());
	}

    /**
     * @throws GatewayException
     * @throws SamanException
     */
	public function purchase(): void
	{
		try{
            $soap = new SoapClient(self::TOKEN_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            $result = $soap->RequestToken(
				$this->getMerchantId(),
				$this->getTransactionCode(),
				$this->preparedAmount()
			);
        } catch(SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (is_numeric($result)) {
            throw SamanException::error($result);
        }

        $this->setToken($result);
	}

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getToken(),
        ]);

        parent::postPurchase();
    }

	public function preVerify(): void
	{
	    parent::preVerify();

		if ($this->request['State'] ?? null !== 'OK' || $this->request['StateCode'] ?? null !== '0' ) {
			switch ($this->request->get('StateCode')) {
				case '-1':
					$ex	= new SamanException(-101);
					break;
				case '51':
					$ex	= new SamanException(51);
					break;
				default:
					$ex	= new SamanException(-100);
					break;
			}
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		if ($this->request->get(app('config')->get('iranpayment.transaction_query_param', 'tc')) !== $this->getTransactionCode()) {
			$ex	= new SamanException(-14);
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}
		if ($this->request->get('MID') !== $this->merchant_id) {
			$ex	= new SamanException(-4);
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		$this->transactionUpdate([
			'card_number'		=> $this->request->get('SecurePan'),
			'tracking_code'		=> $this->request->get('TRACENO'),
			'reference_number'	=> $this->request->get('RefNum'),
		]);

		$this->transactionVerifyPending();
	}

	public function verify(): void
	{
		try{
			$soap = new SoapClient(self::VERIFY_URL, [
				'encoding'				=> 'UTF-8',
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$result = $soap->verifyTransaction(
				$this->getReferenceNumber(),
				$this->merchant_id
			);
		} catch(SoapFault $ex) {
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		} catch(Exception $ex){
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		if ($result <= 0) {
			$ex	= new SamanException($result);
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		if ($result != $this->preparedAmount()) {
			$ex	= new SamanException(-102);
			$this->setDescription($ex->getMessage());
			$this->transactionFailed();
			throw $ex;
		}

		$this->transactionSucceed();
	}

	public function gatewayRedirectView()
	{
		$this->transactionPending();

		return view('iranpayment::pages.saman', [
			'transaction_code'	=> $this->getTransactionCode(),
			'token'				=> $this->token,
			'bank_url'			=> self::PAYMENT_URL,
			'redirect_url'		=> $this->getCallbackUrl(),
		]);
	}

	public function gatewayPayView()
	{
		return $this->gatewayRedirectView();
	}

    /**
     * @return string
     * @throws InvalidGatewayMethodException
     */
	public function gatewayPayUri(): string
    {
		throw new InvalidGatewayMethodException;
	}

    /**
     * @return string
     * @throws InvalidGatewayMethodException
     */
	public function gatewayPayRedirect(): string
    {
		throw new InvalidGatewayMethodException;
	}
}
