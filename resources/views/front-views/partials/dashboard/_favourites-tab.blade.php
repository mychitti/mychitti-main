  <div class="tab-pane fade show active" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab">
                    <div class="container tab_inner">
                        <h3 class="text-primary  my-2">Favourites</h3>
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <button class="col-6 nav-link active" id="nav-items-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-items" type="button" role="tab" aria-controls="nav-items"
                                    aria-selected="true">Items</button>
                                <button class=" col-6 nav-link" id="nav-stores-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-stores" type="button" role="tab"
                                    aria-controls="nav-stores" aria-selected="false">Stores</button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-items" role="tabpanel"
                                aria-labelledby="nav-items-tab">

                                @if (count($wishlists['items']))
                                    @foreach ($wishlists['items'] as $ct)
                                        <div class="card my-3">
                                            <div class="row g-0">
                                                <div class="col-md-3 p-3">
                                                    <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->image, asset('storage/app/public/product/') . '/' . $ct->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                                        class="img-fluid rounded" alt="{{ $ct->name }}">
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="card-body">
                                                        <h5 class="card-title">{{ $ct->name }}</h5>
                                                        <p class="card-text">{{ _limitDesc($ct->description, 150) }}</p>
                                                        @if (Config::get('module.current_module_id') == 5)
                                                            <p class="card-text"><small class="text-muted">
                                                                    {{ \App\CentralLogics\Helpers::currency_symbol() . _discountedPrice($ct->price, $ct->discount, $ct->discount_type) }}
                                                                    @if ($ct->discount)
                                                                        <span
                                                                            class="text-danger text-decoration-line-through mb-0"
                                                                            style="white-space: nowrap;">{{ \App\CentralLogics\Helpers::currency_symbol() }}
                                                                            {{ floor($ct->price) }}</span>
                                                                    @endif
                                                                </small></p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="container d-flex flex-column align-items-center justify-content-center"
                                        style="height:200px">
                                        <p>No items in wishlist...</p>
                                        <a href="{{ route('home') }}#products" class="btn btn-danger"><i
                                                class="fa fa-heart text-white"></i> Wish Now</a>
                                    </div>
                                @endif

                            </div>
                            <div class="tab-pane fade" id="nav-stores" role="tabpanel" aria-labelledby="nav-stores-tab">
                                <div id="wishTabInner">
                                    @if (count($wishlists['stores']))
                                        <div class="row">

                                            @foreach ($wishlists['stores'] as $ct)
                                                <div class="card my-3 col-md-5 col-sm-12 m-1 position-relative">
                                                    <a onclick="removeWishlist('store', '{{ $ct->id }}')"
                                                        class="position-absolute"
                                                        style="right:10px; top:10px;cursor:pointer;"><i
                                                            class="fa fa-heart text-danger fs-4"></i></a>
                                                    <div class="row">
                                                        <div class="col-md-4 p-3">
                                                            <img data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                                src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct->logo, asset('storage/app/public/store/') . '/' . $ct->logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'store/') }}"
                                                                class="img-fluid rounded" alt="{{ $ct->name }}">
                                                        </div>
                                                        <div class="col-md-7">
                                                            <div class="card-body">
                                                                <h5 class="card-title">{{ $ct->name }}</h5>
                                                                <p class="card-text">{{ $ct->address }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="container d-flex flex-column align-items-center justify-content-center"
                                            style="height:200px">
                                            <p>No favourite stores ...</p>
                                            <a href="{{ route('home') }}#stores" class="btn btn-danger"><i
                                                    class="fa fa-heart text-white"></i> Explore stores</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>