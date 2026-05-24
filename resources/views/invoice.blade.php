<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@lang('cashier-iyzico::app.title')</title>

    <style>
        body {
            background: #fff none;
            font-family: DejaVu Sans, 'sans-serif';
            font-size: 12px;
        }

        .container {
            padding-top: 30px;
        }

        .table th {
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            padding: 8px 8px 8px 0;
            vertical-align: bottom;
        }

        .table tr.row td {
            border-bottom: 1px solid #ddd;
        }

        .table td {
            padding: 8px 8px 8px 0;
            vertical-align: top;
        }

        .table th:last-child,
        .table td:last-child {
            padding-right: 0;
        }

        .dates {
            color: #555;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <table style="margin-left: auto; margin-right: auto;" width="100%">
        <tr valign="top">
            <td width="180">
                <span style="font-size: 28px;">
                    @lang('cashier-iyzico::app.title')

                    @if ($invoice->paid())
                        <span style="color: #0c0; font-size: 20px;">@lang('cashier-iyzico::app.paid')</span>
                    @endif
                </span>

                <p>
                    @isset ($product)
                        <strong>@lang('cashier-iyzico::app.product'):</strong> {{ $product }}<br>
                    @endisset

                    <strong>@lang('cashier-iyzico::app.date'):</strong> {{ $invoice->date()->toFormattedDateString() }}<br>

                    @if ($dueDate = $invoice->dueDate())
                        <strong>@lang('cashier-iyzico::app.due_date'):</strong> {{ $dueDate->toFormattedDateString() }}<br>
                    @endif

                    @if ($invoiceId = $id ?? $invoice->number())
                        <strong>@lang('cashier-iyzico::app.invoice_number'):</strong> {{ $invoiceId }}<br>
                    @endif
                </p>
            </td>

            <td align="right">
                <span style="font-size: 28px; color: #ccc;">
                    <strong>{{ $header ?? $vendor ?? $invoice->account_name }}</strong>
                </span>
            </td>
        </tr>
        <tr valign="top">
            <td width="50%">
                <strong>{{ $vendor ?? $invoice->account_name }}</strong><br>

                @isset($street)
                    {{ $street }}<br>
                @endisset
                @isset($location)
                    {{ $location }}<br>
                @endisset

                @isset($country)
                    {{ $country }}<br>
                @endisset

                @isset($phone)
                    {{ $phone }}<br>
                @endisset

                @isset($email)
                    {{ $email }}<br>
                @endisset

                @isset($website)
                    <a href="{{ $website }}">{{ $website }}</a><br>
                @endisset

                @isset($vatId)
                    {{ $vatId }}<br>
                @else
                    {{$accountTaxId}}<br>
                @endisset
            </td>
            <td width="50%">
                <strong>@lang('cashier-iyzico::app.recipient')</strong><br>

                {{ $invoice->customer_name ?? $invoice->customer_email }}<br>

                @if ($address = $invoice->customer_address)
                    @if ($address->line1)
                        {{ $address->line1 }}<br>
                    @endif

                    @if ($address->line2)
                        {{ $address->line2 }}<br>
                    @endif

                    @if ($address->city)
                        {{ $address->city }}<br>
                    @endif

                    @if ($address->state || $address->postal_code)
                        {{ implode(' ', [$address->state, $address->postal_code]) }}<br>
                    @endif

                    @if ($address->country)
                        {{ $address->country }}<br>
                    @endif
                @endif

                @if ($invoice->customer_phone)
                    {{ $invoice->customer_phone }}<br>
                @endif

                @if ($invoice->customer_name)
                    {{ $invoice->customer_email }}<br>
                @endif

                {{$customerTaxId}}<br>
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2">
                @if ($invoice->description)
                    <p>
                        {{ $invoice->description }}
                    </p>
                @endif

                @if (isset($vatId))
                    <p>
                        {{ $vatId }}
                    </p>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table width="100%" class="table" border="0">
                    <tr>
                        <th align="left">@lang('cashier-iyzico::app.description')</th>
                        <th align="left">@lang('cashier-iyzico::app.qty')</th>
                        <th align="left">@lang('cashier-iyzico::app.unit_price')</th>

                        @if ($invoice->hasTax())
                            <th align="right">@lang('cashier-iyzico::app.tax')</th>
                        @endif

                        <th align="right">@lang('cashier-iyzico::app.amount')</th>
                    </tr>

                    @foreach ($invoice->invoiceLineItems() as $item)
                        <tr class="row">
                            <td>
                                {{ $item->description }}
                            </td>

                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->unitAmountExcludingTax() }}</td>

                            @if ($invoice->hasTax())
                                <td align="right">
                                    {{$invoice->subscription->tax_rate}}%
                                </td>
                            @endif

                            <td align="right">{{ $item->total() }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td></td>
                        <td colspan="{{ $invoice->hasTax() ? 3 : 2 }}">
                            <strong>@lang('cashier-iyzico::app.total')</strong>
                        </td>
                        <td align="right">
                            <strong>{{ $invoice->realTotal() }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

</body>
</html>