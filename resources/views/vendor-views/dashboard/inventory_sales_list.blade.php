<table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table  table-striped">
    <thead class="bg-white">
        <tr>
            <th class="border-0">S no</th>
            <th class="border-0">Invoice Id</th>
            <th class="border-0">Item</th>
            <th class="border-0">Qty</th>
            <th class="border-0">Unit Price</th>
            <th class="border-0">Status</th>
            <th class="border-0">Created at</th>
            <th class="border-0">Action</th>
        </tr>
    </thead>
    <tbody id="activityTableBodyexp">
     @foreach ($sale_order_items as $key => $item)
                                <tr>
                                    <td>
                                     <div class="sno-cell">
                        <span class="sno-indicator"></span>
                        {{ $key + 1 }}
                    </div></td>
                                    <td>
                                        @if ($item->order?->invoice?->pdf)
                                            <a target="_blank"
                                                href="{{ asset('storage/app/public/invoice') . '/' . $item->order?->invoice?->pdf }}">
                                                {{ $item->order?->invoice?->invoice_id }}</a>
                                        @else
                                            {{ $item->order?->invoice?->invoice_id }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->item?->id)
                                            <div style="width: 300px;text-align: start !important;white-space: normal;">

                                                <a href="{{ route('vendor.inventory.item.detail', [$item->item?->id]) }}">
                                                    {{ $item->item?->item_name }}</a>
                                            </div>
                                        @else
                                            Deleted
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-success rounded ml-1"> {{ $item->qty }}</span>
                                    </td>
                                    <td>
                                        {{ _price($item->unit_price) }}
                                    </td>
                                    <td>
                                        @if ($item->status == 'completed')
                                            <span
                                                class="badge badge-soft-success rounded ml-1">{{ ucfirst($item->status) }}</span>
                                        @elseif($item->status == 'returned')
                                            <span
                                                class="badge badge-soft-warning rounded ml-1">{{ ucfirst($item->status) }}</span>
                                        @elseif($item->status == 'cancelled')
                                            <span
                                                class="badge badge-soft-danger rounded ml-1">{{ ucfirst($item->status) }}</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->created_at }}
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fa-solid fa-bars"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if (hasPermission('inventory_sale', 'status_change'))
                                                    @if ($item->status != 'completed')
                                                        <a class="dropdown-item text-success form-alert" href="javascript:;"
                                                            data-id="completed-{{ $item['id'] }}"
                                                            data-message="{{ translate('Want to mark this order item as completed ') }}"
                                                            title="{{ translate('messages.completed') }}"></i>
                                                            <i class="tio-checkmark-circle-outlined"></i> Completed
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.sale.order-status', [$item->id, 'completed']) }}"
                                                            method="get" id="completed-{{ $item['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                    @if ($item->status != 'returned')
                                                        <a class="dropdown-item text-warning form-alert" href="javascript:;"
                                                            data-id="returned-{{ $item['id'] }}"
                                                            data-message="{{ translate('Want to mark this order item as returned ') }}"
                                                            title="{{ translate('messages.returned') }}"></i>
                                                            <i class="tio-replay"></i> Returned
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.sale.order-status', [$item->id, 'returned']) }}"
                                                            method="get" id="returned-{{ $item['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                    @if ($item->status != 'cancelled')
                                                        <a class="dropdown-item text-danger form-alert" href="javascript:;"
                                                            data-id="cancelled-{{ $item['id'] }}"
                                                            data-message="{{ translate('Want to mark this order item as cancelled ') }}"
                                                            title="{{ translate('messages.cancelled') }}"></i>
                                                            <i class="tio-blocked"></i> Cancelled
                                                        </a>
                                                        <form
                                                            action="{{ route('vendor.inventory.sale.order-status', [$item->id, 'cancelled']) }}"
                                                            method="get" id="cancelled-{{ $item['id'] }}">
                                                            @csrf @method('get')
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            @endforeach

    </tbody>
</table>
