<?php

namespace Dena\IranPayment\Gateways\Sep;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Http\CurlRequest;

class Sep extends AbstractGateway implements GatewayInterface
{
    private const TOKEN_URL = 'https://sep.shaparak.ir/onlinepg/onlinepg';

    private const SEND_URL = 'https://sep.shaparak.ir/OnlinePG/OnlinePG';

    private const VERIFY_URL = 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction';

    /**
     * Terminal ID variable
     */
    protected ?int $terminal_id;

    /**
     * ResNum variable
     */
    protected ?string $res_num;

    /**
     * Token variable
     */
    protected ?string $token;

    public const CURRENCY = Currency::IRR;

    /**
     * Set Terminal Id function
     *
     * @return $this
     */
    public function setTerminalId(int $terminal_id): self
    {
        $this->terminal_id = $terminal_id;

        return $this;
    }

    /**
     * Get Terminal Id function
     */
    public function getTerminalId(): ?int
    {
        return $this->terminal_id;
    }

    /**
     * Set ResNum function
     *
     * @return $this
     */
    public function setResNum(?string $res_num): self
    {
        $this->res_num = $res_num;

        return $this;
    }

    /**
     * Get ResNum function
     */
    public function getResNum(): ?string
    {
        return $this->res_num;
    }

    /**
     * Set Token function
     *
     * @return $this
     */
    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get Token function
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getName(): string
    {
        return 'sep';
    }

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);

        $this->setTerminalId(app('config')->get('iranpayment.sep.terminal-id'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.sep.callback-url')
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
     * @throws SepException
     * @throws GatewayException
     */
    public function purchase(): void
    {
        $this->requestGetToken();
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getToken(),
        ]);

        parent::postPurchase();
    }

    /**
     * @throws GatewayException
     */
    public function purchaseUri(): string
    {
        throw GatewayException::notSupportedMethod();
    }

    /**
     * Purchase View Params function
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'بانک سامان',
            'image' => 'https://cdn.drdr.ir/public/gateway/sep.png',
            'bank_url' => self::SEND_URL,
            'method' => 'POST',
            'form_data' => [
                'Token' => $this->getToken(),
            ],
        ];
    }

    public function preVerify(): void
    {
        parent::preVerify();

        if (! isset($this->request['RefNum']) && empty($this->request['RefNum'])) {
            throw InvalidRequestException::notFound();
        }

        $transaction = $this->getTransaction();

        $referenceTransaction = $this->findTransactionByReferenceNumber($this->request['Token']);

        if ($referenceTransaction && $transaction->id != $referenceTransaction->id) {
            throw InvalidRequestException::unProcessableVerify();
        }
    }

    /**
     * @throws SepException
     * @throws GatewayException
     */
    public function verify(): void
    {
        $data = [
            'RefNum' => $this->request['RefNum'],
            'TerminalNumber' => $this->getTerminalId(),
        ];

        $result = $this->httpRequest(self::VERIFY_URL, $data);

        if (! $result->Success && $result->ResultCode !== 0) {
            throw SepException::verifyError($result->ResultCode, $result->ResultDescription);
        }

        $this->transactionUpdate([
            'card_number' => $result->TransactionDetail->MaskedPan ?? null,
            'tracking_code' => $result->TransactionDetail->StraceNo ?? null,
        ], [
            'RRN' => $result->TransactionDetail->RRN ?? null,
            'RefNum' => $result->TransactionDetail->RefNum ?? null,
            'OrginalAmount' => $result->TransactionDetail->OrginalAmount ?? null,
            'StraceDate' => $result->TransactionDetail->StraceDate ?? null,
        ]);
    }

    /**
     * @throws SepException
     * @throws GatewayException
     */
    private function requestGetToken(): void
    {
        $data = [
            'action' => 'token',
            'TerminalId' => $this->getTerminalId(),
            'Amount' => $this->preparedAmount(),
            'ResNum' => $this->getResNum(),
            'RedirectUrl' => $this->preparedCallbackUrl(),
            'CellNumber' => $this->getMobile() ? $this->mobileReformat($this->getMobile()) : '',
        ];

        $result = $this->httpRequest(self::TOKEN_URL, $data);

        if (isset($result->status) && $result->status != 1) {
            throw SepException::purchaseError($result->errorCode, $result->errorDesc);
        }

        if (! isset($result->token)) {
            throw GatewayException::unknownResponse(json_encode($result));
        }

        $this->setToken($result->token);
    }

    /**
     * @throws GatewayException
     */
    private function httpRequest(string $url, array $data = [], string $method = 'POST'): object
    {
        $curl = new CurlRequest($url, $method);
        $result = $curl->execute(json_encode($data));

        return json_decode($result);
    }

    private function mobileReformat($mobile): array|string|null
    {
        return preg_replace('/^(\+989|00989|989|09|9)/', '9', $mobile);
    }
}
