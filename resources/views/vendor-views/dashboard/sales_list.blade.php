<table class="table table-striped">
    <thead>
        <tr>
            <th>S No</th>
            <th>Date</th>
            <th>Payment Status</th>
            <th>Amount</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="activityTableBodyexp">
        @foreach ($invoices as $key => $inv)
            <tr>
                <td>
                    <div class="sno-cell">
                        <span class="sno-indicator"></span>
                        {{ $key + 1 }}
                    </div>
                </td>
                <td>{{ $inv->invoice_date ?? $inv->created_at }}</td>
                   <td>
                    @if ($inv->payment_status == 'Unpaid')
                        <span class="status-badge status-pending">{{ $inv->payment_status }}</span>
                    @else
                        <span class="status-badge status-completed">{{ $inv->payment_status }}</span>
                    @endif
                </td>
                <td>{{ _price($inv->total_amount) }}</td>
                <td class="type-expense">
                <a href="{{ $inv->pdf }}" style="width: fit-content; padding: 0 10px !important;" class="btn action-btn  btn-outline-primary">View Invoice</a>
                </td>
            </tr>
        @endforeach

    </tbody>
</table>
