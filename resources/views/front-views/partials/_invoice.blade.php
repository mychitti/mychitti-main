<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header, .footer {
            text-align: center;
        }
        .details, .totals {
            margin-top: 10px;
            border: 1px solid #000;
        }
        .details td, .totals td {
            padding: 5px;
            border: 1px solid #000;
        }
        .totals {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    @if ($order->store)
        <h2>{{ $order->store->name }}</h2>
        <p>{{ $order->store->address }}</p>
        <p>{{ translate('Phone') }}: {{ $order->store->phone }}</p>
    @endif
    <hr>
    <h3>{{ translate('Cash Receipt') }}</h3>
</div>

<table>
    <tr>
        <td>{{ translate('Order ID') }}: {{ $order['id'] }}</td>
        <td align="right">{{ translate('Date') }}: {{ date('d/M/Y H:i', strtotime($order['created_at'])) }}</td>
    </tr>
    @if ($order->store?->gst_status)
        <tr>
            <td>{{ translate('GST No') }}: {{ $order->store->gst_code }}</td>
        </tr>
    @endif
</table>

<table class="details">
    @php($address = json_decode($order->delivery_address, true))
    @if ($order->order_type == 'parcel')
        {{-- Parcel Orders --}}
        <tr>
            <td>{{ translate('Sender Name') }}</td>
            <td>{{ $address['contact_person_name'] ?? $order->customer->f_name . ' ' . $order->customer->l_name }}</td>
        </tr>
        <tr>
            <td>{{ translate('Sender Phone') }}</td>
            <td>{{ $address['contact_person_number'] ?? $order->customer->phone }}</td>
        </tr>
        <tr>
            <td>{{ translate('Sender Address') }}</td>
            <td>{{ $address['address'] ?? '' }}</td>
        </tr>
        @php($receiver = $order->receiver_details)
        <tr>
            <td>{{ translate('Receiver Name') }}</td>
            <td>{{ $receiver['contact_person_name'] ?? '' }}</td>
        </tr>
        <tr>
            <td>{{ translate('Receiver Phone') }}</td>
            <td>{{ $receiver['contact_person_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td>{{ translate('Receiver Address') }}</td>
            <td>{{ $receiver['address'] ?? '' }}</td>
        </tr>
    @else
        {{-- Regular Orders --}}
        <tr>
            <td>{{ translate('Contact Name') }}</td>
            <td>{{ $address['contact_person_name'] ?? $order->customer->f_name . ' ' . $order->customer->l_name }}</td>
        </tr>
        <tr>
            <td>{{ translate('Phone') }}</td>
            <td>{{ $address['contact_person_number'] ?? $order->customer->phone }}</td>
        </tr>
        <tr>
            <td>{{ translate('Address') }}</td>
            <td>{{ $address['address'] ?? '' }}</td>
        </tr>
    @endif
</table>

<table class="details">
    <tr>
        <th>{{ translate('Description') }}</th>
        <th>{{ translate('Price') }}</th>
    </tr>
    @php($sub_total = 0)
    @php($total_tax = 0)
    @foreach ($order->details as $detail)
        @php($item = json_decode($detail->item_details, true))
        <tr>
            <td>
                <strong>{{ $item['name'] }}</strong>
                @if (!empty(json_decode($detail->variation, true)))
                    <br>
                    {{ translate('Variation') }}:
                    @foreach (json_decode($detail->variation, true) as $variation)
                        {{ $variation['name'] ?? '' }},
                    @endforeach
                @endif
            </td>
            <td>{{ \App\CentralLogics\Helpers::format_currency($detail->price * $detail->quantity) }}</td>
        </tr>
        @php($sub_total += $detail->price * $detail->quantity)
        @php($total_tax += $detail->tax_amount * $detail->quantity)
    @endforeach
</table>

<table class="totals">
    <tr>
        <td>{{ translate('Subtotal') }}:</td>
        <td>{{ \App\CentralLogics\Helpers::format_currency($sub_total) }}</td>
    </tr>
    <tr>
        <td>{{ translate('Tax') }}:</td>
        <td>{{ \App\CentralLogics\Helpers::format_currency($total_tax) }}</td>
    </tr>
    <tr>
        <td>{{ translate('Delivery Charge') }}:</td>
        <td>{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}</td>
    </tr>
    <tr>
        <td>{{ translate('Total') }}:</td>
        <td>{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</td>
    </tr>
</table>

<div class="footer">
                <img src="{{ asset('/public/assets/admin/img/invoice-star.png') }}" alt="image" class="w-100">
                <div class="text-uppercase text-center">{{ translate('THANK YOU') }}</div>
                <img src="{{ asset('/public/assets/admin/img/invoice-star.png') }}" alt="image" class="w-100">
                <div class="copyright">
                    &copy; {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}.
                    <span
                        class="d-none d-sm-inline-block">{{ \App\Models\BusinessSetting::where(['key' => 'footer_text'])->first()->value }}</span>
                </div>
            </div>

</body>
</html>
