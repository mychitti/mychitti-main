  @if (hasPermission('inventory_item', 'list'))

                                <div class=" px-0 w-100">
                                    <div class="card h-100">
                                        <div class="card-header d-flex flex-wrap items_header">
                                            <h3 class="mx-3 mt-2 mb-0">List</h3>
                                            <div class="d-flex gap-2 flex-wrap">

                                                <form action="" class="h-100">
                                                    <!-- Search -->
                                                    <div class="input-group input--group search_bar2"
                                                        style="flex-wrap: nowrap !important; ">
                                                        <input type="hidden" name="tab" value="items">

                                                        <input type="search"
                                                            style="height: 100%;padding: 11px 10px;"
                                                            name="search" value="{{ request()?->search ?? null }}"
                                                            class="form-control "
                                                            placeholder="{{ translate('messages.search by item or SKU ID') }}">
                                                        <button type="submit" class="btn btn--secondary "><i
                                                                class="tio-search"></i></button>
                                                    </div>
                                                    <!-- End Search -->
                                                </form>
                                                
                                            </div>

                                        </div>
                                        <div class="table-responsive datatable-custom" id="table-div">
                                            <table id="datatable"
                                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="border-0">{{ translate('sl') }}</th>
                                                        <th class="border-0"> ID</th>
                                                        <th class="border-0"> Name</th>
                                                        <th class="border-0 hide_on_phone"> Item Type</th>
                                                        <th class="border-0 hide_on_phone"> Brand</th>
                                                        <th class="border-0 hide_on_phone"> Model Number</th>
                                                        <th class="border-0 hide_on_phone ">Stock</th>
                                                        <th class="border-0 hide_on_phone ">MRP</th>
                                                        <th class="border-0 ">Selling Price</th>
                                                        @if (hasPermission('inventory_item', 'show_on_website'))
                                                            <th class="border-0 hide_on_phone ">Show on Website</th>
                                                        @endif
                                                        <th class="border-0 ">Action</th>
                                                    </tr>
                                                </thead>

                                                <tbody id="set-rows">
                                                    @foreach ($inventory_items as $key => $item)
                                                        <tr class="clickable-row"
                                                            data-href="{{ route('vendor.inventory.item.detail', [$item->id]) }}">
                                                          
                                                            <td>{{ $key + $inventory_items->firstItem() }}</td>
                                                             <td >
                                                                {{$item->id }}
                                                            </td>
                                                            <td>
                                                                <a class="media align-items-center">
                                                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($item['image'], asset('storage/app/public/inventory-item/') . '/' . $item['image'], asset('public/assets/admin/img/160x160/img2.jpg'), 'inventory-item/') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="{{ $item->item_name }} image">
                                                                    <div class="media-body">
                                                                        <h5 class="text-hover-primary mb-0">
                                                                            {{ Str::limit($item['item_name'], 20, '...') }}
                                                                        </h5>
                                                                    </div>
                                                                </a>
                                                            </td>
                                                            <td class="hide_on_phone">
                                                                <div
                                                                    class="badge badge-soft-{{ $item->item_type == 'service' ? 'warning' : 'success' }}">
                                                                    {{ ucfirst($item->item_type) }} </div>
                                                            </td>
                                                            <td class="hide_on_phone">{{ $item->brand }}</td>
                                                            <td class="hide_on_phone">{{ $item->model_number }}</td>
                                                            <td class="hide_on_phone">
                                                                <div
                                                                    class="badge badge-soft-{{ $item->stock <= 5 ? 'danger' : 'success' }}">
                                                                    {{ $item->stock }}
                                                                </div>
                                                            </td>
                                                            <td class="hide_on_phone">
                                                                {{ _price($item->mrp) }}
                                                            </td>
                                                            <td>
                                                                {{ _price($item->selling_price) }}
                                                            </td>
                                                            @if (hasPermission('inventory_item', 'show_on_website'))
                                                                <td class="hide_on_phone">
                                                                    <label class="toggle-switch toggle-switch-sm"
                                                                        for="featuredCheckbox{{ $item->id }}">
                                                                        <input type="checkbox"
                                                                            data-url="{{ route('vendor.inventory.item.show_on_website', [$item['id'], $item->show_on_store_page ? 0 : 1]) }}"
                                                                            class="toggle-switch-input redirect-url"
                                                                            id="featuredCheckbox{{ $item->id }}"
                                                                            {{ $item->show_on_store_page ? 'checked' : '' }}>
                                                                        <span class="toggle-switch-label mx-auto">
                                                                            <span class="toggle-switch-indicator"></span>
                                                                        </span>
                                                                    </label>
                                                                </td>
                                                            @endif
                                                            <td>
                                                                <div class="dropdown">
                                                                    <button class="btn p-1 dropdown-toggle" type="button"
                                                                        data-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fa-solid fa-bars"></i>
                                                                    </button>
                                                                    <div class="dropdown-menu">
                                                                        @if (hasPermission('inventory_item', 'view'))
                                                                            <a class="dropdown-item text-success"
                                                                                href="{{ route('vendor.inventory.item.detail', [$item->id]) }}"
                                                                                title="{{ translate('messages.details') }}"></i>
                                                                                <i class="tio-visible"></i> View
                                                                            </a>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'edit'))
                                                                            <a class="dropdown-item text-primary"
                                                                                href="{{ route('vendor.inventory.edit-item', [$item->id]) }}"
                                                                                title="{{ translate('messages.edit') }}"></i>
                                                                                <i class="tio-edit"></i> Edit
                                                                            </a>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'share'))
                                                                            <a class="dropdown-item text-warning share-btn"
                                                                                onclick="event.stopPropagation()"
                                                                                href="javascript:;"
                                                                                data-url="{{ route('vendor.inventory.item.detail', $item->id) }}"
                                                                                data-title="{{ $item->item_name }}"
                                                                                title="{{ translate('messages.share') }}"></i>
                                                                                <i class="tio-share"></i> Share
                                                                            </a>
                                                                        @endif
                                                                        @if (hasPermission('inventory_item', 'delete'))
                                                                            <a class="dropdown-item text-danger form-alert"
                                                                                onclick="event.stopPropagation()"
                                                                                href="javascript:;"
                                                                                data-id="item-{{ $item['id'] }}"
                                                                                data-message="{{ translate('messages.Want to delete this item') }}"
                                                                                data-title="{{ $item->item_name }}"
                                                                                title="{{ translate('messages.delete_item') }}"></i>
                                                                                <i class="tio-delete-outlined"></i> Delete
                                                                            </a>
                                                                            <form
                                                                                action="{{ route('vendor.inventory.item.delete', [$item['id']]) }}"
                                                                                method="post"
                                                                                id="item-{{ $item['id'] }}">
                                                                                @csrf @method('get')
                                                                            </form>
                                                                        @endif

                                                                    </div>

                                                                </div>
                                                                {{-- <div class="btn--container justify-content-warning">
                                                                    @if (hasPermission('inventory_item', 'view'))
                                                                        <a class="btn action-btn btn--warning btn-outline-warning"
                                                                            href="{{ route('vendor.inventory.item.detail', [$item->id]) }}"
                                                                            title="{{ translate('messages.detail') }}"><i
                                                                                class="tio-visible"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (hasPermission('inventory_item', 'edit'))
                                                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                                                            href="{{ route('vendor.inventory.edit-item', [$item->id]) }}"
                                                                            title="{{ translate('messages.edit') }}"><i
                                                                                class="tio-edit"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (hasPermission('inventory_item', 'share'))
                                                                        <a class="btn action-btn btn--warning btn-outline-warning share-btn"
                                                                            onclick="event.stopPropagation()"
                                                                            href="javascript:;"
                                                                            data-url="{{ route('vendor.inventory.item.detail', $item->id) }}"
                                                                            data-title="{{ $item->item_name }}"><i
                                                                                class="tio-share"></i>
                                                                        </a>
                                                                    @endif
                                                                    @if (hasPermission('inventory_item', 'delete'))
                                                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                                            href="javascript:;"
                                                                            data-id="item-{{ $item['id'] }}"
                                                                            onclick="event.stopPropagation()"
                                                                            data-message="{{ translate('messages.Want to delete this item') }}"
                                                                            title="{{ translate('messages.delete_item') }}"><i
                                                                                class="tio-delete-outlined"></i>
                                                                        </a>
                                                                        <form
                                                                            action="{{ route('vendor.inventory.item.delete', [$item['id']]) }}"
                                                                            method="post" id="item-{{ $item['id'] }}">
                                                                            @csrf @method('get')
                                                                        </form>
                                                                    @endif
                                                                </div> --}}
                                                            </td>

                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @if (count($inventory_items) !== 0)
                                                <hr>
                                            @endif
                                            <div class="page-area">
                                                {!! $inventory_items->links() !!}
                                            </div>

                                            @if (count($inventory_items) === 0)
                                                <div class="empty--data">
                                                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}"
                                                        alt="public">
                                                    <h5>
                                                        {{ translate('no_data_found') }}
                                                    </h5>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                        @endif