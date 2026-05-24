<?php

namespace Codenteq\Iyzico;

use App\Models\User;
use Carbon\Carbon;
use Codenteq\Iyzico\Contracts\InvoiceRenderer;
use Codenteq\Iyzico\Models\Subscription;
use Illuminate\Support\Facades\View;

class Invoice
{
    public User $user;
    public Subscription $subscription;

    public function __construct(User $user, Subscription $subscription)
    {
        $this->user = $user;
        $this->subscription = $subscription;
    }

    public function view(array $data): \Illuminate\View\View
    {
        return View::make('cashier-iyzico::invoice', array_merge($data, [
            'invoice' => $this,
        ]));
    }

    public function pdf(array $data): string
    {
        return app(InvoiceRenderer::class)->render($this, $data);
    }

    public function paid(): bool
    {
        return $this->subscription->ends_at === null || $this->subscription->ends_at->isFuture();
    }

    public function date(): Carbon
    {
        return $this->subscription->created_at;
    }

    public function dueDate(): ?Carbon
    {
        return $this->subscription->trial_ends_at;
    }

    public function number(): string
    {
        return 'INV-' . $this->subscription->id;
    }

    public function __get($key)
    {
        $properties = [
            'account_name' => config('app.name', 'Laravel'),
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => $this->user->phone ?? null,
            'customer_address' => null,
            'description' => $this->subscription->name . ' Aboneliği',
        ];

        return $properties[$key] ?? null;
    }

    public function invoiceLineItems(): array
    {
        return [
            new InvoiceLineItem([
                'description' => $this->subscription->name,
                'quantity' => 1,
                'iyzico_price' => $this->subscription->iyzico_price,
                'tax_rate' => $this->subscription->tax_rate,
                'tax_price' => $this->subscription->tax_price,
                'base_price' => $this->subscription->base_price,
            ])
        ];
    }

    public function hasTax(): bool
    {
        return $this->subscription->tax_rate > 0;
    }

    public function realTotal(): string
    {
        return number_format($this->subscription->iyzico_price, 2) . ' TL';
    }

    public function amountDue(): string
    {
        return $this->paid() ? '0.00 TL' : number_format($this->subscription->price, 2) . ' TL';
    }

}
