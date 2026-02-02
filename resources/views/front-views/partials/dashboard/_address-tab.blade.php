    <div class="tab-pane fade show active" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                    <div class="container tab_inner">
                        <h3 class="text-primary  my-2">Address <a href="{{ route('add-address') }}"
                                class="btn btn-primary text-light">+ Add New Address</a></h3>
                        <div class="row">
                            @foreach ($user_addresses as $addr)
                                <div class="col-sm-6 my-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary">{{ ucfirst($addr->address_type) }}</h5>
                                            <p class="card-text">
                                                {{ $addr->house . ' ' . $addr->road . ' ' . ucfirst($addr->address) }}</p>
                                            <a href="{{ route('delete-address', [$addr->id]) }}"
                                                class="text-danger mx-2"><i class="fa fa-trash"></i></a>
                                            <a href="{{ route('edit-address', [$addr->id]) }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>