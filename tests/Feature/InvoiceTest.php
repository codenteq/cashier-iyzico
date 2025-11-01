<?php

namespace Codenteq\Iyzico\Tests\Feature;

use App\Models\User;
use Codenteq\Iyzico\Enums\PaymentIntervalEnum;
use Codenteq\Iyzico\Enums\SubscriptionStatusEnum;
use Codenteq\Iyzico\Models\Subscription;
use Codenteq\Iyzico\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Iyzipay\Model\Subscription\SubscriptionPricingPlan;
use Iyzipay\Model\Subscription\SubscriptionProduct;
use Iyzipay\Options;
use Iyzipay\Request\Subscription\SubscriptionCreatePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateProductRequest;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    //use RefreshDatabase;

    private SubscriptionPricingPlan $paymentPlan;

    private SubscriptionProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cashier.iyzico.webhook.verify' => false,
        ]);

        $this->options = new Options;
        $this->options->setApiKey(config('cashier.iyzico.api_key'));
        $this->options->setSecretKey(config('cashier.iyzico.secret_key'));
        $this->options->setBaseUrl(config('cashier.iyzico.base_url'));

        $productRequest = new SubscriptionCreateProductRequest;

        $productRequest->setName('Laravel Cashier Iyzico Product '. Str::uuid()->toString());

        $this->product = SubscriptionProduct::create($productRequest, $this->options);

        $paymentPlanRequest = new SubscriptionCreatePricingPlanRequest;

        $paymentPlanRequest->setName('Premium Plan');
        $paymentPlanRequest->setProductReferenceCode($this->product->getReferenceCode());
        $paymentPlanRequest->setPlanPaymentType('RECURRING');
        $paymentPlanRequest->setPaymentIntervalCount(1);
        $paymentPlanRequest->setPaymentInterval(PaymentIntervalEnum::MONTHLY->value);
        $paymentPlanRequest->setCurrencyCode('TRY');
        $paymentPlanRequest->SetPrice(100.00);

        $this->paymentPlan = SubscriptionPricingPlan::create($paymentPlanRequest, $this->options);
    }

    public function test_invoice_download()
    {
        $user = User::factory()->create();

        $subscription = $user->newSubscription($this->product->getName(), $this->paymentPlan->getName())
            ->create([
                'pricing_plan_reference_code' => $this->paymentPlan->getReferenceCode(),
                'status' => SubscriptionStatusEnum::ACTIVE->value,
                'invoice' => [
                    'basePrice' =>  $this->paymentPlan->getPrice() - 10,
                    'taxPrice' => 10,
                    'taxRate' => 10,
                    'totalPrice' => $this->paymentPlan->getPrice(),
                ],
                'customer' => [
                    'name' => 'Ahmet Sefa',
                    'surname' => 'Arsiv',
                    'email' => 'example@test.com',
                    'gsmNumber' => '+905301112233',
                    'identityNumber' => '12345678901',
                    'billingAddress' => [
                        'contactName' => 'Ahmet Sefa Arsiv',
                        'city' => 'Istanbul',
                        'country' => 'Turkey',
                        'address' => 'Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1',
                        'zipCode' => '34732',
                    ],
                    'shippingAddress' => [
                        'contactName' => 'Ahmet Sefa Arsiv',
                        'city' => 'Istanbul',
                        'country' => 'Turkey',
                        'address' => 'Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1',
                        'zipCode' => '34732',
                    ],
                ],
                'card' => [
                    'cardHolderName' => 'Ahmet Sefa Arsiv',
                    'cardNumber' => '5528790000000008',
                    'expireMonth' => '12',
                    'expireYear' => '2030',
                    'cvc' => '123',
                ],
            ]);


        $response = $user->downloadInvoice([
            'name' => 'Codenteq Yazılım ve Bilişim Teknolojileri A.Ş.',
            'street' => 'Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1',
            'city' => 'Istanbul',
            'postalCode' => '34732',
            'country' => 'Turkey',
            'phone' => '+905301112233',
            'email' => 'info@codenteq.com',
            'vatId' => '1234567890',
            'website' => 'https://codenteq.com',
            'accountTaxId' => "1234567890",
            "customerTaxId" => "0987654321",
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($user->subscribed($this->product->getName()));
    }
}
