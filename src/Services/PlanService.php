<?php

namespace Codenteq\Iyzico\Services;

use Iyzipay\Model\Subscription\SubscriptionPricingPlan;
use Iyzipay\Options;
use Iyzipay\Request\Subscription\SubscriptionCreatePricingPlanRequest;

class PlanService
{
    protected Options $options;

    public function __construct()
    {
        $this->options = new Options;
        $this->options->setApiKey(config('cashier.iyzico.api_key'));
        $this->options->setSecretKey(config('cashier.iyzico.secret_key'));
        $this->options->setBaseUrl(config('cashier.iyzico.base_url'));
    }

    /**
     * Create a pricing plan in Iyzico
     */
    public function create(SubscriptionCreatePricingPlanRequest $request): SubscriptionPricingPlan
    {
        return SubscriptionPricingPlan::create($request, $this->options);
    }
}
