<?php

namespace Codenteq\Iyzico\Services;

use Iyzipay\Model\Subscription\SubscriptionPricingPlan;
use Iyzipay\Model\Subscription\RetrieveList;
use Iyzipay\Options;
use Iyzipay\Request\Subscription\SubscriptionCreatePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionDeletePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionListPricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionRetrievePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionUpdatePricingPlanRequest;

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
    public function create(string $productReferenceCode, array $data): SubscriptionPricingPlan
    {
        $request = new SubscriptionCreatePricingPlanRequest;
        $request->setProductReferenceCode($productReferenceCode);
        $request->setName($data['name']);
        $request->setPrice($data['price']);
        $request->setCurrencyCode($data['currency_code']);
        $request->setPaymentInterval($data['payment_interval']);
        $request->setPlanPaymentType($data['plan_payment_type']);
        $request->setPaymentIntervalCount($data['payment_interval_count']);
        $request->setRecurrenceCount($data['recurrence_count']);
        $request->setTrialPeriodDays($data['trial_period_days']);

        return SubscriptionPricingPlan::create($request, $this->options);
    }

    /**
     * Update a pricing plan in Iyzico
     */
    public function update(string $pricingPlanReferenceCode, array $data): SubscriptionPricingPlan
    {
        $request = new SubscriptionUpdatePricingPlanRequest;
        $request->setPricingPlanReferenceCode($pricingPlanReferenceCode);
        $request->setName($data['name']);
        $request->setTrialPeriodDays($data['trial_period_days']);

        return SubscriptionPricingPlan::update($request, $this->options);
    }

    /**
     * Retrieve pricing plan detail from Iyzico
     */
    public function retrieve(string $pricingPlanReferenceCode): SubscriptionPricingPlan
    {
        $request = new SubscriptionRetrievePricingPlanRequest;
        $request->setPricingPlanReferenceCode($pricingPlanReferenceCode);

        return SubscriptionPricingPlan::retrieve($request, $this->options);
    }

    /**
     * Delete a pricing plan in Iyzico
     */
    public function delete(string $pricingPlanReferenceCode): SubscriptionPricingPlan
    {
        $request = new SubscriptionDeletePricingPlanRequest;
        $request->setPricingPlanReferenceCode($pricingPlanReferenceCode);

        return SubscriptionPricingPlan::delete($request, $this->options);
    }

    /**
     * List pricing plans for a product in Iyzico
     */
    public function list(string $productReferenceCode, array $params = []): RetrieveList
    {
        $request = new SubscriptionListPricingPlanRequest;
        $request->setProductReferenceCode($productReferenceCode);
        $request->setPage($params['page']);
        $request->setCount($params['count']);

        return RetrieveList::pricingPlan($request, $this->options);
    }
}
