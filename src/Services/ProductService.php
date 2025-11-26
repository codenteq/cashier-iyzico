<?php

namespace Codenteq\Iyzico\Services;

use Iyzipay\Model\Subscription\SubscriptionProduct;
use Iyzipay\Options;
use Iyzipay\Request\Subscription\SubscriptionCreateProductRequest;

class ProductService
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
     * Create a product in Iyzico
     */
    public function create(SubscriptionCreateProductRequest $request): SubscriptionProduct
    {
        return SubscriptionProduct::create($request, $this->options);
    }
}
