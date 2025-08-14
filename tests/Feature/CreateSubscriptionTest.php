<?php

namespace Codenteq\Iyzico\Tests\Feature;

use App\Models\User;
use Codenteq\Iyzico\Enums\PaymentIntervalEnum;
use Codenteq\Iyzico\Enums\SubscriptionStatusEnum;
use Codenteq\Iyzico\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Iyzipay\Model\Subscription\SubscriptionPricingPlan;
use Iyzipay\Model\Subscription\SubscriptionProduct;
use Iyzipay\Options;
use Iyzipay\Request\Subscription\SubscriptionCreatePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateProductRequest;
use Tests\TestCase;

class CreateSubscriptionTest extends TestCase
{
    use RefreshDatabase;

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

        $productRequest->setName('Laravel Cashier Iyzico Product '.random_int(1, 1000));

        $this->product = SubscriptionProduct::create($productRequest, $this->options);

        $paymentPlanRequest = new SubscriptionCreatePricingPlanRequest;

        $paymentPlanRequest->setName('Premium Plan');
        $paymentPlanRequest->setProductReferenceCode($this->product->getReferenceCode());
        $paymentPlanRequest->setPlanPaymentType('RECURRING');
        $paymentPlanRequest->setPaymentIntervalCount(1);
        $paymentPlanRequest->setPaymentInterval(PaymentIntervalEnum::MONTHLY->value);
        $paymentPlanRequest->setCurrencyCode('TRY');
        $paymentPlanRequest->SetPrice(79.99);

        $this->paymentPlan = SubscriptionPricingPlan::create($paymentPlanRequest, $this->options);
    }

    public function test_user_can_create_subscription()
    {
        $user = User::factory()->create();

        $subscription = $user->newSubscription($this->product->getName(), $this->paymentPlan->getName())
            ->create([
                'pricing_plan_reference_code' => $this->paymentPlan->getReferenceCode(),
                'status' => SubscriptionStatusEnum::ACTIVE->value,
                'price' => $this->paymentPlan->getPrice(),
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

        $this->assertTrue($user->subscribed($this->product->getName()));
        $this->assertInstanceOf(Subscription::class, $subscription);
    }
}
