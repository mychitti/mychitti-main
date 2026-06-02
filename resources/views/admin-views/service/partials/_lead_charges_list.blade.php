<div class="row">
    <div class="card col-12">
        <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between" style="gap:10px;">
            <h5 class="card-title mb-0"><i class="tio-filter-list mr-1"></i> Lead Charges
                <span class="badge badge-soft-dark ml-2">{{ $charges->total() }}</span>
            </h5>
            <div class="d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('admin.service.lead-charge-export') . (request('zone_id') ? '?zone_id=' . request('zone_id') : '') }}"
                    class="btn btn-sm btn-outline-primary"><i class="tio-download"></i> Export Template</a>
                <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#importModal">
                    <i class="tio-upload"></i> Import
                </button>
            </div>
        </div>

        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{ translate('sl') }}</th>
                        <th class="border-0">Category</th>
                        <th class="border-0">Service</th>
                        <th class="border-0">Zone</th>
                        <th class="border-0">Charges</th>
                        <th class="text-center border-0">{{ translate('messages.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($charges as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $lead->cat_name }}</td>
                            <td>{{ $lead->item_name ?? 'All Services' }}</td>
                            <td>{{ $lead->zone_name ?? 'All' }}</td>
                            <td>
                                <div class="info">
                                    <div class="text--title">
                                        <b>1st : </b>{{ \App\CentralLogics\Helpers::currency_symbol() . $lead->ven_1_charges }}, &nbsp;
                                        <b>2nd : </b>{{ \App\CentralLogics\Helpers::currency_symbol() . $lead->ven_2_charges }}, &nbsp;
                                        <b>3rd : </b>{{ \App\CentralLogics\Helpers::currency_symbol() . $lead->ven_3_charges }}, &nbsp;
                                        <b>Others :</b>{{ \App\CentralLogics\Helpers::currency_symbol() . $lead->ven_other_charges }}, &nbsp;
                                        <b>Dedicated :</b>{{ \App\CentralLogics\Helpers::currency_symbol() . ($lead->dedicated_lead_charge ?? 0) }}, &nbsp;
                                        <b>Confirmation :</b>{{ \App\CentralLogics\Helpers::currency_symbol() . ($lead->confirmation_charge ?? 0) }}, &nbsp;
                                        <b>Completion :</b>{{ \App\CentralLogics\Helpers::currency_symbol() . ($lead->completion_charge ?? 0) }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a style="min-width:50px;" class="btn btn--primary btn-outline-primary"
                                        href="{{ route('admin.service.edit-charges', [$lead->id]) }}" title="Edit Charges"><i class="tio-edit"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ translate('no_data_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($charges->count())
                <div class="px-3 py-2">{{ $charges->appends(['tab' => 'lead-charges'])->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="tio-upload mr-1"></i> Import Lead Charges</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('admin.service.lead-charge-import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size:13px;">
                        <strong>Steps:</strong>
                        <ol class="mb-0 pl-3">
                            <li>Click <strong>Export Template</strong> (select a zone first if needed) to download services.</li>
                            <li>Fill in the charge columns. Do <strong>not</strong> change <code>zone_id</code>, <code>category_id</code>, or <code>item_id</code>.</li>
                            <li>Leave rows blank to skip them.</li>
                            <li>Upload the file here — existing charges for the matching zone will be updated.</li>
                        </ol>
                    </div>
                    <div class="form-group">
                        <label>Excel / CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Accepted: .xlsx, .xls, .csv</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="tio-upload"></i> Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
