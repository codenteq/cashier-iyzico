<?php

namespace Codenteq\Iyzico\Concerns;

use App\Models\User;
use Codenteq\Iyzico\Invoice;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

trait ManagesInvoices
{

    /**
     * Download invoice as PDF
     *
     * @param array{
     *     name: string,
     *     street: string,
     *     city: string,
     *     postalCode: string,
     *     country: string,
     *     phone: string,
     *     email: string,
     *     website: string,
     *     vatId: string,
     *     accountTaxId?: string,
     *     customerTaxId?: string
     * } $data Company and invoice data
     * @return Response PDF file response
     */
    public function downloadInvoice(array $data): Response
    {

        $subscription = $this->findSubscriptionByIyzicoId($this->iyzico_id);

        $invoice = new Invoice($this, $subscription);

        $filename = $subscription->name ?? Str::slug(config('app.name'));
        $subscriptionMonth = $subscription->created_at->month;
        $subscriptionYear = $subscription->created_at->year;
        $filename .= '_'.$subscriptionMonth.'_'.$subscriptionYear;

        return new Response($invoice->pdf($data), 200, [
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'application/pdf',
            'X-Vapor-Base64-Encode' => 'True',
        ]);
    }
}
