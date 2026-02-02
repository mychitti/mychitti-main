  <div class="tab-pane fade show active" id="v-pills-order" role="tabpanel" aria-labelledby="v-pills-order-tab">
                    <div class="container tab_inner">
                        <h3 class="text-primary  my-2">Orders</h3>
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="col-6 nav-link active" id="nav-Running-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-Running" type="button" role="tab"
                                    aria-controls="nav-Running" aria-selected="true">Running</button>
                                <button class="col-6 nav-link" id="nav-History-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-History" type="button" role="tab"
                                    aria-controls="nav-History" aria-selected="false">History</button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-Running" role="tabpanel"
                                aria-labelledby="nav-Running-tab">
                                <div class="accordion" id="accordionExample">
                                    @if (count($orders))
                                        @foreach ($orders as $key => $order)
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading{{ $key }}">
                                                    <button style="    padding: 5px;"
                                                        class="accordion-button {{ $key ? 'collapsed' : '' }}"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $key }}"
                                                        aria-expanded="true" aria-controls="collapse{{ $key }}">
                                                        <img class="rounded mx-2" style="width: 70px ; height:70px;"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($order->store?->logo, asset('storage/app/public/store/') . '/' . $order->store?->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                            alt="store">
                                                        <div>
                                                            Order Id : #{{ $order->id }} <br>
                                                            {{ date('d M Y  H:i', strtotime($order->created_at)) }}

                                                        </div>
                                                        <span
                                                            class="badge bg-success mx-2">{{ $order->order_status }}</span>

                                                    </button>
                                                </h2>
                                                <div id="collapse{{ $key }}"
                                                    class="accordion-collapse collapse {{ !$key ? 'show' : '' }} "
                                                    aria-labelledby="heading{{ $key }}"
                                                    data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <table class="table table-bordered">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Order Id</th>
                                                                    <td>{{ $order->id }}</td>
                                                                    <th>Order Date</th>
                                                                    <td>{{ date('d M Y  H:i', strtotime($order->created_at)) }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <table class="table">
                                                            <tbody>
                                                                <tr>
                                                                    <td>{{ ucfirst($order->order_type) }}</td>
                                                                    <td>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                                                    </td>
                                                                    <td><span
                                                                            class="text-primary">{{ ucfirst($order->order_status) }}</span>
                                                                    </td>
                                                                    <th>Items: {{ $order->details_count }}</th>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <h5>Item Info</h5>
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Products</th>
                                                                    <th scope="col">Quantity</th>
                                                                    <th scope="col">Total Price</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @php
                                                                    $total_price = 0;
                                                                @endphp

                                                                @if (count($orders))
                                                                    @foreach ($order['items'] as $key => $o_item)
                                                                        @php
                                                                            $total_price += $o_item->price;
                                                                            $ct = json_decode($o_item->item_details);
                                                                            $variation = json_decode(
                                                                                $o_item->variation,
                                                                            );
                                                                        @endphp

                                                                        <tr>
                                                                            <th scope="row">
                                                                                <div
                                                                                    class="d-flex align-items-center mt-2">
                                                                                    <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct?->image, asset('storage/app/public/product/') . '/' . $ct?->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                                                        class="img-fluid rounded-circle"
                                                                                        style="width: 50px; height: 50px;"
                                                                                        alt="{{ $ct?->name }}">
                                                                                    {{ $ct?->name }}
                                                                                    ({{ count($variation) ? $variation[0]->type : '' }})
                                                                                </div>
                                                                            </th>

                                                                            <td>{{ $o_item->quantity }}</td>
                                                                            <td class="">
                                                                                {{ \App\CentralLogics\Helpers::currency_symbol() . $o_item->price }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif

                                                            </tbody>
                                                        </table>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                @php $delDetails = json_decode($order->delivery_address ) @endphp
                                                                <h5>Deliver To </h5>
                                                                <div class="card p-2">
                                                                    <b>{{ $delDetails->contact_person_name }}</b>
                                                                    {{ $delDetails->address }}
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <h5 class="mt-3">Store Details</h5>
                                                                <div class="card d-flex flex-row align-items-center p-1">
                                                                    <img class="rounded mx-2"
                                                                        style="width: 56px ; height:56px;"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($order->store?->logo, asset('storage/app/public/store/') . '/' . $order->store?->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                                        alt="{{ $order->store?->name }}">
                                                                    {{ $order->store?->name }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <table class="table table-borderless w-50 mt-4">
                                                            <tbody>
                                                                <tr>
                                                                    <td>Item Price</td>
                                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $total_price }}
                                                                    </td>
                                                                </tr>
                                                                @if ($order->store_discount_amount)
                                                                    <tr>
                                                                        <td>Discount</td>
                                                                        <td>(-){{ \App\CentralLogics\Helpers::currency_symbol() . $order->store_discount_amount }}
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td>VAT/TAX</td>
                                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $order->total_tax_amount }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Delivery Fee</td>
                                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $order->original_delivery_charge }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Total Amount</th>
                                                                    <td> <span
                                                                            class="fs-5">{{ \App\CentralLogics\Helpers::currency_symbol() . $order->order_amount }}</span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>


                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-center my-5"> No running orders...</p>
                                    @endif
                                </div>
                            </div>
                            <div class="tab-pane fade " id="nav-History" role="tabpanel"
                                aria-labelledby="nav-History-tab">
                                <div class="accordion" id="accordionExample">
                                    @if (count($p_orders))
                                        @foreach ($p_orders as $key => $order)
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading{{ $key }}">
                                                    <button style="    padding: 5px;"
                                                        class="accordion-button {{ $key ? 'collapsed' : '' }}"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $key }}"
                                                        aria-expanded="true" aria-controls="collapse{{ $key }}">
                                                        <img class="rounded mx-2" style="width: 70px ; height:70px;"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                            src="{{ \App\CentralLogics\Helpers::onerror_image_helper($order->store?->logo, asset('storage/app/public/store/') . '/' . $order->store?->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                            alt="{{ $order->store?->name }}">
                                                        <div>
                                                            Order Id : #{{ $order->id }} <br>
                                                            {{ date('d M Y  H:i', strtotime($order->created_at)) }}

                                                        </div>
                                                        <span
                                                            class="badge bg-success mx-2">{{ $order->order_status }}</span>


                                                    </button>
                                                </h2>
                                                <div id="collapse{{ $key }}"
                                                    class="accordion-collapse collapse {{ !$key ? 'show' : '' }} "
                                                    aria-labelledby="heading{{ $key }}"
                                                    data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <table class="table table-bordered">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Order Id</th>
                                                                    <td>{{ $order->id }}</td>
                                                                    <th>Order Date</th>
                                                                    <td>{{ date('d M Y  H:i', strtotime($order->created_at)) }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <table class="table">
                                                            <tbody>
                                                                <tr>
                                                                    <td>{{ ucfirst($order->order_type) }}</td>
                                                                    <td>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                                                    </td>
                                                                    <td><span
                                                                            class="text-primary">{{ ucfirst($order->order_status) }}</span>
                                                                    </td>
                                                                    <th>Items: {{ $order->details_count }}</th>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <h5>Item Info</h5>
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Products</th>
                                                                    <th scope="col">Quantity</th>
                                                                    <th scope="col">Total Price</th>
                                                                    <th scope="col">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @php
                                                                    $total_price = 0;
                                                                @endphp
                                                                @if ($order['items'])
                                                                    @foreach ($order['items'] as $key => $o_item)
                                                                        @php
                                                                            $total_price += $o_item->price;
                                                                            $ct = json_decode($o_item->item_details);
                                                                            $variation = json_decode(
                                                                                $o_item->variation,
                                                                            );
                                                                        @endphp

                                                                        <tr>
                                                                            <th scope="row"
                                                                                class="item_info_{{ $o_item->id }}">
                                                                                <div
                                                                                    class="d-flex align-items-center mt-2">
                                                                                    <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/product/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                                                        class="img-fluid rounded-circle"
                                                                                        style="width: 50px; height: 50px;"
                                                                                        alt="{{ $ct->name }}">
                                                                                    {{ $ct->name }}
                                                                                    ({{ count($variation) ? $variation[0]->type : '' }})
                                                                                </div>
                                                                            </th>

                                                                            <td>{{ $o_item->quantity }}</td>
                                                                            <td class="">
                                                                                {{ \App\CentralLogics\Helpers::currency_symbol() . $o_item->price }}
                                                                            </td>
                                                                            <td><button type="button"
                                                                                    class="btn btn-primary reviewModalBtn"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#reviewModal"
                                                                                    data-id="{{ $o_item->id }}">Leave a
                                                                                    Review</button></td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif

                                                            </tbody>
                                                        </table>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                @php $delDetails = json_decode($order->delivery_address ) @endphp
                                                                <h5>Deliver To </h5>
                                                                <div class="card p-2">
                                                                    <b>{{ $delDetails->contact_person_name }}</b>
                                                                    {{ $delDetails->address }}
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <h5>Store Details</h5>
                                                                <div class="card d-flex flex-row align-items-center p-1">
                                                                    <img class="rounded mx-2"
                                                                        style="width: 56px ; height:56px;"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($order->store?->logo, asset('storage/app/public/store/') . '/' . $order->store?->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                                        alt="{{ $order->store?->name }}">
                                                                    {{ $order->store?->name }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <table class="table table-borderless w-50 mt-4">
                                                            <tbody>
                                                                <tr>
                                                                    <td>Item Price</td>
                                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $total_price }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>VAT/TAX</td>
                                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $order->total_tax_amount }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Delivery Fee</td>
                                                                    <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $order->original_delivery_charge }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Total Amount</th>
                                                                    <td> <span
                                                                            class="fs-5">{{ \App\CentralLogics\Helpers::currency_symbol() . $order->order_amount }}</span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>


                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-center my-5"> No previous orders...</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>