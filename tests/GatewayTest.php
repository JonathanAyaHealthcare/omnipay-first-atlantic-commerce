<?php

namespace Omnipay\OmnipayFirstAtlanticCommerce\Tests;

use Omnipay\OmnipayFirstAtlanticCommerce\Enums\ModificationType;
use Omnipay\OmnipayFirstAtlanticCommerce\Enums\TransactionCode;
use Omnipay\OmnipayFirstAtlanticCommerce\Gateway;
use Omnipay\OmnipayFirstAtlanticCommerce\Message\Requests\AuthorizeRequest;
use Omnipay\OmnipayFirstAtlanticCommerce\Message\Requests\HostedPagePreprocessRequest;
use Omnipay\OmnipayFirstAtlanticCommerce\Message\Requests\HostedPageResultsRequest;
use Omnipay\OmnipayFirstAtlanticCommerce\Message\Requests\PurchaseRequest;
use Omnipay\OmnipayFirstAtlanticCommerce\Message\Requests\TransactionModificationRequest;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setMerchantId(123456)
            ->setMerchantPassword('abcdefg')
            ->setAcquirerId(12345)
            ->setTestMode(true);
    }

    public function testGateCanBeInitialized(): void
    {
        self::assertEquals('First Atlantic Commerce.', $this->gateway->getName());
        self::assertEquals('FAC', $this->gateway->getShortName());
        self::assertArraySubset([
            'merchantId' => 123456,
            'merchantPassword' => 'abcdefg',
            'acquirerId' => 12345,
            'testMode' => true,
        ], $this->gateway->getParameters());
    }

    public function testAuthorize(): void
    {
        $request = $this->gateway->authorize([
        'amount' => '10.00',
        'currency' => 'USD',
        'transactionId' => '1234',
        'card' => $this->getValidCard(),
    ]);

        self::assertInstanceOf(AuthorizeRequest::class, $request);
        self::assertSame('000000001000', $request->getData()['TransactionDetails']['Amount']);
        self::assertSame('840', $request->getData()['TransactionDetails']['Currency']);
    }

    public function testPurchase(): void
    {
        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'USD',
            'transactionId' => '1234',
            'card' => $this->getValidCard(),
        ]);

        self::assertInstanceOf(PurchaseRequest::class, $request);
        self::assertSame('000000001000', $request->getData()['TransactionDetails']['Amount']);
        self::assertSame('840', $request->getData()['TransactionDetails']['Currency']);
        self::assertSame(8, $request->getData()['TransactionDetails']['TransactionCode']);
    }

    public function testPurchaseWithCreateCard(): void
    {
        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'USD',
            'transactionId' => '1234',
            'card' => $this->getValidCard(),
            'createCard' => true,
        ]);

        self::assertInstanceOf(PurchaseRequest::class, $request);
        self::assertSame('000000001000', $request->getData()['TransactionDetails']['Amount']);
        self::assertSame('840', $request->getData()['TransactionDetails']['Currency']);
        self::assertSame(136, $request->getData()['TransactionDetails']['TransactionCode']);

    }

    public function testCapture(): void
    {
        $request = $this->gateway->capture([
            'amount' => '10.00',
            'currency' => 'USD',
            'transactionId' => '1234',
        ]);

        self::assertInstanceOf(TransactionModificationRequest::class, $request);
        self::assertSame('000000001000', $request->getData()['Amount']);
        self::assertSame('840', $request->getData()['Currency']);
        self::assertSame(ModificationType::CAPTURE, $request->getData()['ModificationType']);

    }

    public function testHostedPage(): void
    {
        $request = $this->gateway->hostedPage([
            'amount' => '10.00',
            'currency' => 'USD',
            'transactionId' => '1234',
            'transactionCode' => TransactionCode::SINGLE_PASS + TransactionCode::REQUEST_TOKEN,
            'cardHolderResponseURL' => 'https://merchant/response/page.php',
        ]);

        self::assertInstanceOf(HostedPagePreprocessRequest::class, $request);
        self::assertSame('000000001000', $request->getData()['TransactionDetails']['Amount']);
        self::assertSame('840', $request->getData()['TransactionDetails']['Currency']);
        self::assertSame('https://merchant/response/page.php', $request->getData()['CardHolderResponseURL']);
        self::assertSame('136', $request->getData()['TransactionDetails']['TransactionCode']);
    }

    public function testHostedPageResult(): void
    {
        $request = $this->gateway->hostedPageResult([
            'token' => '_JBfLQJNiEmFBtnF3AfoeQ2',
        ]);

        self::assertInstanceOf(HostedPageResultsRequest::class, $request);
        self::assertSame('_JBfLQJNiEmFBtnF3AfoeQ2', $request->getData()['string']);
    }
}
