<style>
    .form-panel {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        height: fit-content;
    }

    .form-panel h2 {
        color: #2d3748;
        font-size: 20px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        color: #4a5568;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .accounts-panel {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .accounts-panel h2 {
        color: #2d3748;
        font-size: 20px;
        margin-bottom: 20px;
    }

    .account-category {
        margin-bottom: 25px;
    }
</style>

<div class="modal fade" id="subAccAddModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="form-panel">
                    <h2>Add New Account <span class="parent_account_heading"></span></h2>
                    <form id="accountForm" enctype="multipart/form-data"
                        action="{{ route('vendor.account.setting.chart-of-account.account-store') }}" method="post">
                        @csrf
                        <h4 class="parent_account_show"></h4>
                        <span class="badge badge-soft-primary">
                            <i class="tio-info-outined"></i> First create the cost centers in the <a href="{{route('vendor.account.setting.chart-of-account.index')}}" class="text-underline">Ledger Accounts</a>
                        </span>

                        @if (Route::currentRouteName() != 'vendor.account.setting.chart-of-account.detail')
                            @php $cost_centers = _costCenters()@endphp
                            <div class=" form-group mb-0">
                                <label class="form-label">Ledger Account <span class="text-danger">*</span></label>
                                <select data-placeholder="Select Category" required name="parent_id" id="parent_id"
                                    class="form-control js-select2-custom">
                                    <option value=""></option>
                                    @foreach ($cost_centers as $cc)
                                        <option value="{{ $cc['id'] }}">
                                            {{ $cc->ledgerAccountType?->name . '/' . $cc->full_hierarchy }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="parent_id" class="parent_id">
                        @endif


                        <div class="account_type py-3">
                            <div class="size-selector ">
                                <div class="pos--payment-options ">
                                    <ul style="flex-wrap: nowrap;">
                                        <li>
                                            <label>
                                                <input type="radio" name="account_type" class="account_type_inp"
                                                    value="common" hidden checked>
                                                <span class="size_span">Common</span>
                                            </label>
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="account_type" class="account_type_inp"
                                                    value="cost_center" hidden>
                                                <span class="size_span">Cost Center</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="border w-100 cost_center_info" style="display:none">
                            <i class="tio-info-outined"></i> A cost center is a part of your organization—like a
                            department, team, or unit—where costs are tracked separately. It incurs expenses but
                            does not directly generate revenue.
                        </div>


                        <div class=" form-group mb-0">
                            <label>Account Name</label>
                            <input type="text" name="name" placeholder="Name"placeholder="e.g., Cash in Bank"
                                required>
                        </div>
                        <div class=" pb-3">
                            <label>Type</label>

                            <div class="size-selector ">
                                <div class="pos--payment-options ">
                                    <ul style="flex-wrap: nowrap;">
                                        <li>
                                            <label>
                                                <input type="radio" name="acc_type" class="acc_type" value="debit"
                                                    hidden checked>
                                                <span class="acc_type">Debit</span>
                                            </label>
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="acc_type" class="acc_type" value="credit"
                                                    hidden>
                                                <span class="acc_type">Credit</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description (Optional)</label>
                            <textarea id="description" name="description" placeholder="Brief description of the account"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
