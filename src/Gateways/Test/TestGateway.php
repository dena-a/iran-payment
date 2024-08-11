<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;

class TestGateway extends AbstractGateway implements GatewayInterface
{
    protected ?string $url;

    public function getName(): string
    {
        return 'test';
    }

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(Currency::IRR);

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.test.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        $this->url = $parameters['url'] ?? app('config')->get('iranpayment.test.url');

        return $this;
    }

    protected function prePurchase(): void
    {
        parent::prePurchase();

        if ($this->preparedAmount() < 0 || $this->preparedAmount() > 1000000000) {
            throw InvalidDataException::invalidAmount();
        }
    }

    public function purchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => uniqid(),
        ]);
    }

    /**
     * Purchase View Params function
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'تست',
            'method' => 'POST',
        ];
    }

    /**
     * Pay Link function
     */
    public function purchaseUri(): string
    {
        $url = filter_var($this->url, FILTER_VALIDATE_URL)
            ? $this->url
            : url($this->url);

        $url_parts = parse_url($url);
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        } else {
            $params = [];
        }

        $params['reference_number'] = $this->getReferenceNumber();

        $url_parts['query'] = http_build_query($params);

        return $url_parts['scheme'].'://'.$url_parts['host']
            .(isset($url_parts['port']) && strlen($url_parts['port']) ? ':'.$url_parts['port'] : '')
            .$url_parts['path']
            .(strlen($url_parts['query']) ? '?'.$url_parts['query'] : '');
    }

    public function verify(): void
    {
        if (($this->request['status'] ?? null) === 'error') {
            throw TestException::error(-100);
        }

        $trackingCode = rand(111111, 999999);
        $this->transactionSucceed([
            'card_number' => rand(1111, 9999).'********'.rand(1111, 9999),
            'tracking_code' => $trackingCode,
            'reference_number' => 'RefNum-'.$trackingCode,
        ]);
    }

    public function bankView()
    {
        return response()->view('iranpayment::pages.test', [
            'transaction_code' => $this->getTransactionCode(),
            'callback_url' => $this->preparedCallbackUrl(),
        ]);
    }
}
