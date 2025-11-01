<?php

namespace Codenteq\Iyzico;

class InvoiceLineItem
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function unitAmountExcludingTax(): string
    {
        return number_format($this->data['base_price'], 2) . ' TL';
    }

    public function total(): string
    {
        return number_format($this->data['iyzico_price'] * $this->data['quantity'], 2) . ' TL';
    }
}
