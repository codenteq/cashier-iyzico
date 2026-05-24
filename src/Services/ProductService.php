<?php

namespace Codenteq\Iyzico\Services;

use Iyzipay\Model\Subscription\SubscriptionProduct;
use Iyzipay\Model\Subscription\RetrieveList;
use Iyzipay\Options;
use Iyzipay\Request\Subscription\SubscriptionCreateProductRequest;
use Iyzipay\Request\Subscription\SubscriptionDeleteProductRequest;
use Iyzipay\Request\Subscription\SubscriptionListProductsRequest;
use Iyzipay\Request\Subscription\SubscriptionRetrieveProductRequest;
use Iyzipay\Request\Subscription\SubscriptionUpdateProductRequest;

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
    public function create(array $data): SubscriptionProduct
    {
        $request = new SubscriptionCreateProductRequest;
        $request->setName($data['name']);
        $request->setDescription($data['description']);

        return SubscriptionProduct::create($request, $this->options);
    }

    /**
     * Update a product in Iyzico
     */
    public function update(string $productReferenceCode, array $data): SubscriptionProduct
    {
        $request = new SubscriptionUpdateProductRequest;
        $request->setProductReferenceCode($productReferenceCode);
        $request->setName($data['name']);
        $request->setDescription($data['description']);

        return SubscriptionProduct::update($request, $this->options);
    }

    /**
     * Retrieve product detail from Iyzico
     */
    public function retrieve(string $productReferenceCode): SubscriptionProduct
    {
        $request = new SubscriptionRetrieveProductRequest;
        $request->setProductReferenceCode($productReferenceCode);

        return SubscriptionProduct::retrieve($request, $this->options);
    }

    /**
     * Delete a product in Iyzico
     */
    public function delete(string $productReferenceCode): SubscriptionProduct
    {
        $request = new SubscriptionDeleteProductRequest;
        $request->setProductReferenceCode($productReferenceCode);

        return SubscriptionProduct::delete($request, $this->options);
    }

    /**
     * List products in Iyzico
     */
    public function list(array $params = []): RetrieveList
    {
        $request = new SubscriptionListProductsRequest;
        $request->setPage($params['page']);
        $request->setCount($params['count']);

        return RetrieveList::products($request, $this->options);
    }
}
