    <div class="modal fade" id="financialYearModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Financial Year</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @php
                        $financialYears = \App\CentralLogics\Helpers::_financialYears();
                        $storeId = \App\CentralLogics\Helpers::get_store_id();
                    @endphp

                    <div class="row">
                        @foreach ($financialYears as $fyear)
                            @php
                                $txnCount = \App\Models\StoreBankTransaction::where('store_id', $storeId)
                                    ->whereBetween('txn_date', [$fyear->start_date, $fyear->end_date])
                                    ->count();
                            @endphp

                            @if ($txnCount > 0)
                                <div class="m-2">
                                    <a
                                        href="?year={{ $fyear->label }}">
                                        <div
                                            class="card text-center shadow-sm year-card {{ $year == $fyear->label ? 'active' : '' }}">
                                            <div class="card-body" style="padding: 10px !important">
                                                <h5 class="card-title">{{ $fyear->label }}</h5>
                                                <p class="card-text">
                                                    {{ \Carbon\Carbon::parse($fyear->start_date)->format('M Y') }}
                                                    –
                                                    {{ \Carbon\Carbon::parse($fyear->end_date)->format('M Y') }}
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>


                </div>
            </div>
        </div>
    </div>