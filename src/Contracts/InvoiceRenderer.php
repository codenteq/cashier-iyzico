<?php

namespace Codenteq\Iyzico\Contracts;

use Codenteq\Iyzico\Invoice;

interface InvoiceRenderer
{

    /*
     * Render the invoice with the given data.
     *
     * @param array $data
     * @param Invoice $invoice
     * @return string
     */
    public function render(Invoice $invoice, array $data): string;
}
