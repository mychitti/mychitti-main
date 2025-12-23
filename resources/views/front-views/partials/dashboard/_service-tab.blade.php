<div class="tab-pane fade" id="v-pills-service" role="tabpanel" aria-labelledby="v-pills-service-tab">
    <div class="container tab_inner">
        <h3 class="text-primary  my-2">Services</h3>
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="col-6 nav-link active" id="nav-Running-service-tab" data-bs-toggle="tab"
                    data-bs-target="#nav-Running-service" type="button" role="tab"
                    aria-controls="nav-Running-service" aria-selected="true">Running Services</button>
                <button class="col-6 nav-link" id="nav-History-service-tab" data-bs-toggle="tab"
                    data-bs-target="#nav-History-service" type="button" role="tab"
                    aria-controls="nav-History-service" aria-selected="false">Service History</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active " id="nav-Running-service" role="tabpanel"
                aria-labelledby="nav-Running-service-tab">
                <div class="accordion" id="accordionExample">
                    @if (count($services['running']))
                        @foreach ($services['running'] as $key => $serRun)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $key }}">
                                    <button style="    padding: 5px;"
                                        class="accordion-button {{ $key ? 'collapsed' : '' }}  item_info_{{ $serRun->id }}"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ $key }}" aria-expanded="true"
                                        aria-controls="collapse{{ $key }}">
                                        <img class="rounded mx-2" style="width: 56px ; height:56px;"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ strpos($serRun->item_image, 'http') !== 0 ? asset('storage/app/public/product') . '/' : '' }}{{ $serRun->item_image }}"
                                            alt="{{ $serRun->item_image }}">
                                        <div> {{ $serRun->item_name }} <br>
                                            <small
                                                class="text-muted">{{ date('d M Y  H:i', strtotime($serRun->created_at)) }}</small>
                                        </div>
                                        <span class="badge bg-success mx-2">{{ $serRun->current_status }}</span>

                                    </button>
                                </h2>
                                <div id="collapse{{ $key }}"
                                    class="accordion-collapse collapse {{ !$key ? 'show' : '' }} "
                                    aria-labelledby="heading{{ $key }}" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">


                                        <div class="row">
                                            <div class="col-6">
                                                <div>
                                                    <h5 class="mt-3">Details</h5>
                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th>Service Id</th>
                                                                <td>{{ $serRun->service_request_id }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Date</th>
                                                                <td>{{ date('d M Y  H:i', strtotime($serRun->created_at)) }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if ($serRun->assigned_to)
                                                    <div>
                                                        <h5 class="mt-3">Staff Details</h5>
                                                        <div class="card d-flex flex-row align-items-center p-1">
                                                            <img class="rounded mx-2" style="width: 56px ; height:56px;"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                src="{{ $serRun->staff_image }}"
                                                                alt="{{ $serRun->staff_name }}">
                                                            <div>
                                                                <h6 class="mb-2">
                                                                    {{ $serRun->staff_name }}
                                                                </h6>
                                                                <p class="address_elem">
                                                                    <i class="fas fa-map-marker-alt"></i>
                                                                    {{ $serRun->staff_contact }}<br>
                                                                    {{ $serRun->staff_role }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                            @if ($serRun->store_id)
                                                <div class="col-6">
                                                    <h5 class="mt-3">Store Details</h5>
                                                    <div class="card d-flex flex-row align-items-center p-1">
                                                        <img class="rounded mx-2" style="width: 56px ; height:56px;"
                                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                            src="{{ strpos($serRun->store_logo, 'http') !== 0 ? asset('storage/app/public/store') . '/' : '' }}{{ $serRun->store_logo }}"
                                                            alt="{{ $serRun->store_name }}">
                                                        <div>
                                                            <h6 class="mb-2"><a
                                                                    href="{{ route('store.details', [$serRun->store_slug]) }}">{{ ucfirst($serRun->store_name) }}</a>
                                                            </h6>
                                                            <p class="address_elem">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                                {{ $serRun->store_address }}<br>
                                                                <i class="fas fa-phone"></i>
                                                                {{ $serRun->store_phone }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                        </div>
                                        @if ($serRun->store_id)
                                            <table class="table table-borderless w-50 mt-4">
                                                <tbody>
                                                    <tr>
                                                        <td>Visiting Charges</td>
                                                        <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $serRun->quoted_price }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        @endif


                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-center my-5"> No active services...</p>
                    @endif
                </div>
            </div>
            <div class="tab-pane fade " id="nav-History-service" role="tabpanel"
                aria-labelledby="nav-History-service-tab">
                <div class="accordion" id="accordionExample">
                    @if (count($services['history']))
                        @foreach ($services['history'] as $key => $serRun)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $key }}">
                                    <button style="    padding: 5px;"
                                        class="accordion-button {{ $key ? 'collapsed' : '' }} service_info_{{ $serRun->id }}"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ $key }}" aria-expanded="true"
                                        aria-controls="collapse{{ $key }}">
                                        @if (isset($serRun->item_image))
                                            <img class="rounded mx-2" style="width: 70px ; height:70px;"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{ strpos($serRun->item_image, 'http') !== 0 ? asset('storage/app/public/product') . '/' : '' }}{{ $serRun->item_image }}"
                                                alt="store">
                                        @endif
                                        <div> {{ $serRun->item_name }} <br>
                                            <small
                                                class="text-muted">{{ date('d M Y  H:i', strtotime($serRun->created_at)) }}</small>
                                        </div>
                                        <span class="badge bg-success mx-2">{{ $serRun->current_status }}</span>
                                    </button>
                                </h2>
                                <div id="collapse{{ $key }}"
                                    class="accordion-collapse collapse {{ !$key ? 'show' : '' }} "
                                    aria-labelledby="heading{{ $key }}" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">

                                        <div class="row">
                                            <div class="col-6">
                                                <div>
                                                    <h5> Details</h5>

                                                    <table class="table table-bordered">
                                                        <tbody>
                                                            <tr>
                                                                <th>Service Id</th>
                                                                <td>{{ $serRun->service_request_id }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Date</th>
                                                                <td>{{ date('d M Y  H:i', strtotime($serRun->created_at)) }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if ($serRun->assigned_to)
                                                    <div>
                                                        <h5 class="mt-3">Staff Details</h5>
                                                        <div class="card d-flex flex-row align-items-center p-1">
                                                            <img class="rounded mx-2"
                                                                style="width: 56px ; height:56px;"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                src="{{ $serRun->staff_image }}"
                                                                alt="{{ $serRun->staff_name }}">
                                                            <div>
                                                                <h6 class="mb-2">
                                                                    {{ $serRun->staff_name }}
                                                                </h6>
                                                                <p class="address_elem">
                                                                    <i class="fas fa-map-marker-alt"></i>
                                                                    {{ $serRun->staff_contact }}<br>
                                                                    {{ $serRun->staff_role }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="col-6">
                                                @if ($serRun->store_id)
                                                    <h5>Store Details</h5>
                                                    <div class="card d-flex flex-row align-items-center p-1">
                                                        @if (isset($serRun->store_logo))
                                                            <img class="rounded mx-2"
                                                                style="width: 56px ; height:56px;"
                                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                src="{{ strpos($serRun->store_logo, 'http') !== 0 ? asset('storage/app/public/store') . '/' : '' }}{{ $serRun->store_logo }}"
                                                                alt="{{ $serRun->store_name }}">
                                                        @endif
                                                        <div>
                                                            <h6 class="mb-2"><a
                                                                    href="{{ route('store.details', [$serRun->store_slug]) }}">{{ ucfirst($serRun->store_name) }}</a>
                                                            </h6>
                                                            <p class="address_elem">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                                {{ $serRun->store_address }}<br>
                                                                <i class="fas fa-phone"></i>
                                                                {{ $serRun->store_phone }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (_reviewStatus($serRun->id))
                                                    <button type="button"
                                                        class="btn btn-primary mt-4 serviceReviewModalBtn"
                                                        data-id="{{ $serRun->id }}" data-bs-toggle="modal"
                                                        data-bs-target="#serviceReviewModal">
                                                        Leave a Review
                                                    </button>
                                                @endif
                                                <!-- Modal -->
                                                <div class="modal fade" id="serviceReviewModal" tabindex="-1"
                                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel">Service
                                                                    Review</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div id="service_det"
                                                                    style="display: flex;align-items: start;">
                                                                </div>
                                                                <form action="{{ route('submit-service-review') }}"
                                                                    class="reviewformSubmit">
                                                                    <div class="star-rating">
                                                                        <input type="hidden" name="service_id"
                                                                            id="service_id">

                                                                        <input type="radio" id="star5"
                                                                            name="rating" value="5" />
                                                                        <label for="star5"
                                                                            title="5 stars">★</label>

                                                                        <input type="radio" id="star4"
                                                                            name="rating" value="4" />
                                                                        <label for="star4"
                                                                            title="4 stars">★</label>

                                                                        <input type="radio" id="star3"
                                                                            name="rating" value="3" />
                                                                        <label for="star3"
                                                                            title="3 stars">★</label>

                                                                        <input type="radio" id="star2"
                                                                            name="rating" value="2" />
                                                                        <label for="star2"
                                                                            title="2 stars">★</label>

                                                                        <input type="radio" id="star1"
                                                                            name="rating" value="1" />
                                                                        <label for="star1" title="1 star">★</label>

                                                                        <p style="font-size: 18px;line-height: 33px">:
                                                                            *Rating </p>
                                                                    </div>

                                                                    <label for="revie"
                                                                        title="">Review*</label>
                                                                    <textarea name="review" class="form-control mb-2" id="revie"placeholder="Start typing..."></textarea>

                                                                    <label for="revie" title="">Image
                                                                        (optionl)
                                                                    </label>
                                                                    <input type="file" name="attachment[]"
                                                                        class="form-control mb-2">

                                                                    <button type="submit"
                                                                        class="btn btn-primary">Submit</button>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                        @if (isset($serRun->store_id))
                                            <table class="table table-borderless w-50 mt-4">
                                                <tbody>
                                                    <tr>
                                                        <td>Visiting Charges</td>
                                                        <td>{{ \App\CentralLogics\Helpers::currency_symbol() . $serRun->quoted_price }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            @if ($serRun->gatepass_exists)
                                                <button class="btn btn-primary text-light gatepass-modal"
                                                    data-id="{{ $serRun->service_request_id }}">Gatepass</button>

                                                <!-- Modal -->
                                                <div class="modal fade" id="gatepassModal" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Gatepass</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body" id="gatepass-modal-content">
                                                                Loading...
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <button class="btn btn-grey text-light" disabled>Gatepass</button>
                                            @endif
                                            @if ($serRun->quotation_exists)
                                                <button class="btn btn-primary text-light quotation-modal"
                                                    data-id="{{ $serRun->service_request_id }}">Quotation</button>

                                                <div class="modal fade" id="quotationModal" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Quotation</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body" id="quotation-modal-content">
                                                                Loading...
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <button class="btn btn-grey text-light" disabled>Quotation</button>
                                            @endif
                                            @if (_getServiceInvoice($serRun->service_request_id))
                                                <a href="{{ asset('storage/app/public/invoice') . '/' . _getServiceInvoice($serRun->service_request_id) }}"
                                                    target="_blank" class="btn btn-primary text-light">Invoice</a>
                                            @else
                                                <button class="btn btn-grey text-light" disabled>Invoice</button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-center my-5"> No active services...</p>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
