<form class="customer_add_form" enctype="multipart/form-data" class="w-100"
    action="{{ isset($customer) ? route('vendor.customer.update') : route('vendor.customer.save') }}" method="post">
    @csrf
    <style>
        .addr_btn:hover {
            scale: 1.1;
            transition: all 0.2s;
        }

        .custom-header-btn {
            margin: 5px 5px 5px 0;
            font-size: 10px;
            padding: 2px;
            font-weight: 500;
            border-radius: 20px;
        }

        @media (max-width: 992px) {


            .custom-header-btn {
                margin: 3px 0 !important;
            }
        }
    </style>
    <input type="hidden" id="" name="customer_id" value="{{ isset($customer) ? $customer->id : '' }}">
    {{-- ?user_type=vendor lets a menu open this form straight on the supplier side — the hospital
         sidebar does that, since its customer side is the patient list. --}}
    @if( request('user_type') === 'vendor' || Route::currentRouteName() == "vendor.invoice.my-bills" || Route::currentRouteName() == "vendor.inventory.purchase.orders" || Route::currentRouteName() == "vendor.laundry.challans.create")
    <input type="hidden" id="add_user_type" name="user_type" value="vendor">
    @else
    <input type="hidden" id="add_user_type" name="user_type" value="customer">
    @endif
    @php($custIsSupplier = request('user_type') === 'vendor' || Route::currentRouteName() == "vendor.invoice.my-bills" || Route::currentRouteName() == "vendor.inventory.purchase.orders" || Route::currentRouteName() == "vendor.laundry.challans.create" || (isset($customer) && $customer->user_type == 'vendor'))
    {{-- A lab IS a supplier record, so the kind of lab it is belongs here — answered once when the
         lab is added rather than retyped on every job it is sent. Only shown where lab work is
         actually run; every other store's supplier list has no use for it. --}}
    @php($custHospital = function_exists('_isHospital') && _isHospital())
    @php($custLabWork = $custHospital)
    @php($custLabTypes = $custLabWork ? \App\Models\OpdLabWork::labTypesFor(\App\CentralLogics\Helpers::get_store_id()) : [])
    @php($custLabTypeValue = isset($customer) ? (string) ($customer->lab_type ?? '') : '')
    {{-- A hospital keeps patients on the patient list, so what gets added here is either a client
         or somebody it BUYS from — a lab most of all. One switch instead of two near-identical
         pages, because the only difference between them is this line and the lab type. --}}
    @if ($custHospital && !isset($customer))
        <div class="mb-2">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary custom-header-btn {{ $custIsSupplier ? '' : 'active' }}"
                    data-cust-type="customer" onclick="custSetType('customer')">Client</button>
                <button type="button" class="btn btn-outline-primary custom-header-btn {{ $custIsSupplier ? 'active' : '' }}"
                    data-cust-type="vendor" onclick="custSetType('vendor')">Supplier / Lab</button>
            </div>
        </div>
    @endif

    <div class=" h-100 ">
        <h5>Basic Details</h5>

        <div class="g-0 row">
            <div class="col-md-6  p-1 ">
                <div class="form-group mb-0">
                    <label class="fomr-label" for="exampleFormControlInput1">Name<span
                            class="text-danger">*</span></label>
                    <input type="text" name="f_name" class="form-control __form-control"
                        placeholder="{{ translate('messages.name') }}" required
                        value="{{ isset($customer) ? $customer->f_name . ' ' . $customer->l_name : '' }}">
                </div>
            </div>

            <div class="col-md-6  p-1  mb-0">
                <label for="phoneInp" class="form-label">Phone Number<span class="text-danger">*</span></label>
                <input type="number" class="form-control iti__tel-input" style="width: 100%;" required name="phone"
                    id="phoneInp" value="{{ isset($customer) ? $customer->phone : '' }}" placeholder="Ex: 9988776655">
            </div>
            @if ($custLabWork)
                <div class="col-md-6 p-1 mb-0" id="labTypeGroup" style="{{ $custIsSupplier ? '' : 'display:none;' }}">
                    <label for="labTypeInp" class="form-label">Lab Type</label>
                    <input type="text" class="form-control" style="width: 100%;" name="lab_type" id="labTypeInp"
                        list="custLabTypeList" maxlength="120" value="{{ $custLabTypeValue }}"
                        placeholder="Ex: Ceramic Lab">
                    <datalist id="custLabTypeList">
                        @foreach ($custLabTypes as $custLabTypeOption)
                            <option value="{{ $custLabTypeOption }}"></option>
                        @endforeach
                    </datalist>
                    <small class="text-muted" style="font-size:10.5px;">Prefills the lab type on jobs sent to this lab.</small>
                </div>
            @endif
            @if ($custHospital)
                <script>
                    // The switch owns the hidden user_type the form posts, and the lab type only
                    // means anything on the supplier side — so it goes with it.
                    function custSetType(type) {
                        const hidden = document.getElementById('add_user_type');
                        if (hidden) hidden.value = type;

                        document.querySelectorAll('[data-cust-type]').forEach(btn => {
                            btn.classList.toggle('active', btn.dataset.custType === type);
                        });

                        const labGroup = document.getElementById('labTypeGroup');
                        if (labGroup) labGroup.style.display = type === 'vendor' ? '' : 'none';
                    }
                </script>
            @endif
            <div class="bg-light col-12 mt-3 p-2">
                <h5>Optional</h5>
                <div class="col-12 g-0 row p-0">
                    <div class="col-12 p-1" id="custom-buttons2">

                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                            style="{{ !isset($customer) || !$customer->email ? '' : 'display:none;' }}"
                            data-label="email_inp_grp">+ Email</button>

                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                            style="{{ !isset($customer) || !$customer->gst ? '' : 'display:none;' }}"
                            data-label="gst_inp_grp">+ GST No.</button>

                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                            style="{{ !isset($customer) || !$customer->id_number ? '' : 'display:none;' }}"
                            data-label="id_inp_grp">+ ID No.</button>

                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                            style="{{ !isset($customer) || !$customer->id_proof ? '' : 'display:none;' }}"
                            data-label="id_doc_inp_grp">+ ID Proof</button>

                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                            style="{{ !isset($customer) || !$customer->profile_pic ? '' : 'display:none;' }}"
                            data-label="pp_inp_grp">+ Profile Photo</button>

                    </div>

                    <div class="col-md-6  p-1  mb-0 hidden_inp pp_inp_grp"
                        style="{{ isset($customer) && $customer->profile_pic ? '' : 'display: none;' }}">
                        <label for="profile_pic" class="form-label">Profile Upload</label> <i
                            class="rm_btn cursor-pointer tio-remove-circle-outlined text-danger "
                            data-id="rm_pp_inp_grp"></i>
                        <input accept="image/*" type="file" class="form-control inp_pp_inp_grp " style="width: 100%;"
                            name="profile_pic" id="profile_pic">
                    </div>
                    <div class="col-md-6 p-1 hidden_inp email_inp_grp"
                        style="{{ isset($customer) && $customer->email ? '' : 'display: none;' }}">
                        <div class="form-group mb-0">
                            <label class="form-label" for="exampleFormControlInput1">Email</label> <i
                                class="rm_btn cursor-pointer tio-remove-circle-outlined text-danger "
                                data-id="rm_email_inp_grp"></i>
                            <input type="email" name="email" class="form-control __form-control inp_email_inp_grp"
                                placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                value="{{ isset($customer) ? $customer->email : '' }}">
                        </div>
                    </div>
                    <div class="col-md-6  p-1  mb-0 hidden_inp gst_inp_grp"
                        style="{{ isset($customer) && $customer->gst ? '' : 'display: none;' }}">
                        <label for="phoneInp" class="form-label">GST</label> <i
                            class="rm_btn cursor-pointer tio-remove-circle-outlined text-danger "
                            data-id="rm_gst_inp_grp"></i>
                        <div class="d-flex">
                            <input type="text" class="form-control inp_gst_inp_grp"
                                value="{{ isset($customer) ? $customer->gst : '' }}" style="width: 100%;"
                                name="gst" id="gstInp" placeholder="GST">

                        </div>
                    </div>
                    <div class="col-md-6  p-1  mb-0 hidden_inp id_inp_grp"
                        style="{{ isset($customer) && $customer->id_number ? '' : 'display: none;' }}">
                        <label for="id_number" class="form-label">ID Number</label> <i
                            class="rm_btn cursor-pointer tio-remove-circle-outlined text-danger "
                            data-id="rm_id_inp_grp"></i>
                        <input type="text" class="form-control inp_id_inp_grp"
                            value="{{ isset($customer) ? $customer->id_number : '' }}" style="width: 100%;"
                            name="id_number" id="id_number" placeholder="ID Number">
                    </div>
                    <div class="col-md-6  p-1  mb-0 hidden_inp id_doc_inp_grp"
                        style="{{ isset($customer) && $customer->id_proof ? '' : 'display: none;' }}">
                        <label for="id_proof" class="form-label">ID Proof Upload </label><i
                            class="rm_btn cursor-pointer tio-remove-circle-outlined text-danger "
                            data-id="rm_id_doc_inp_grp"></i>
                        <input accept=".jpeg,.png,.jpg,.pdf,.docx" type="file"
                            class="form-control inp_id_doc_inp_grp" style="width: 100%;" name="id_proof"
                            id="id_proof">
                    </div>
                </div>
            </div>
            <div class="bg-light col-12 mt-3 p-2">
                <h5>Address Details (Optional)</h5>
                <div class="col-12 row">
                    <div class="col-md-12 p-1 mb-0">
                        <div id="billing_address_remove" class=" bg-white" style="display:none;">
                            <button onclick="removeAddress('billing')" type="button"
                                class="btn btn-outline-white btn-sm text-danger fw-bold px-0 addr_btn">
                                <i class="tio-remove-circle-outlined"></i> <span class="text-danger">Remove Billing
                                    Address </span>
                            </button>
                            <div id="billing_address_show">
                                @if (isset($customer) && $customer->billing_address)
                                    <b> Address Line 1 </b> {{ $customer->billing_address->address1 }} <br> <b> Address
                                        Line 2 </b>{{ $customer->billing_address->address2 }}<br> <b> State
                                    </b>{{ $customer->billing_address->state }}<br> <b> City
                                    </b>{{ $customer->billing_address->city }}<br> <b> Pin Code
                                    </b>{{ $customer->billing_address->pincode }}
                                @endif
                            </div>
                        </div>

                        <div id="billing_address_add">
                            <a class="btn btn-outline-white text-danger fw-bold bg-white addr_btn"
                                style="{{ isset($customer) && $customer->billing_address ? 'display:none;' : '' }}"
                                data-toggle="collapse" href="#collapseBillingAddr" role="button"
                                aria-expanded="false" aria-controls="collapseBillingAddr">
                                <i class="tio-add-circle-outlined"></i> <span class="text-danger">Add Billing
                                    Address</span>
                            </a>
                            <div class="collapse {{isset($customer) ? 'show' : ''}}" id="collapseBillingAddr">
                                <div class="card card-body">
                                    <h6>Billing Address</h6>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="ba_address1"><span class="text-danger">*</span>Address Line
                                                1</label>
                                            <input type="text" class="form-control" id="ba_address1"
                                                value="{{ isset($customer) && $customer->billing_address ? $customer->billing_address->address1 : '' }}"
                                                name="ba_address1" placeholder="Address Line 1">
                                            <small class="ba_address1_error text-danger"></small>

                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="ba_address2">Address Line 2</label>
                                            <input type="text" class="form-control" id="ba_address2"
                                                value="{{ isset($customer) && $customer->billing_address ? $customer->billing_address->address2 : '' }}"
                                                name="ba_address2" placeholder="Address Line 2">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="ba_pincode">Pincode</label>
                                            <input type="text" class="form-control" id="ba_pincode"
                                                value="{{ isset($customer) && $customer->billing_address ? $customer->billing_address->pincode : '' }}"
                                                name="ba_pincode" placeholder="Pincode">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="ba_city">City</label>
                                            <input type="text" class="form-control" id="ba_city" name="ba_city"
                                                value="{{ isset($customer) && $customer->billing_address ? $customer->billing_address->city : '' }}"
                                                placeholder="City">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="ba_state"><span class="text-danger">*</span>State</label>
                                            <select class="form-control  js-select2-custom" id="ba_state"
                                                name="ba_state">
                                                <option selected disabled>Select State</option>
                                                @foreach (_states() as $key => $state)
                                                    <option value="{{ $state->id }}"
                                                        {{ isset($customer) && $customer->billing_address && $customer->billing_address->state == $state->id ? 'selected' : '' }}>
                                                        {{ $state->state_name . ' (' . $state->state_abbr . ')' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="ba_state_error text-danger"></small>
                                        </div>
                                        @if (!isset($customer))
                                            <div class="form-group d-flex justify-content-end w-100 col-md-12">
                                                <button type="button" onclick="saveAddress('billing')"
                                                    class="btn btn-outline-primary btn-sm">Save Address</button>
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 p-1 mb-0">
                        <div id="shipping_address_remove" class="bg-white" style="display:none;">
                            <button onclick="removeAddress('shipping')" type="button"
                                class="btn btn-outline-white btn-sm text-danger fw-bold px-0 addr_btn">
                                <i class="tio-remove-circle-outlined"></i> <span class="text-danger">Remove Shipping
                                    Address</span>
                            </button>
                            <div id="shipping_address_show">
                                @if (isset($customer) && $customer->shipping_address)
                                    <b> Address Line 1 </b> {{ $customer->shipping_address->address1 }} <br> <b>
                                        Address Line 2 </b>{{ $customer->shipping_address->address2 }}<br> <b> State
                                    </b>{{ $customer->shipping_address->state }}<br> <b> City
                                    </b>{{ $customer->shipping_address->city }}<br> <b> Pin Code
                                    </b>{{ $customer->shipping_address->pincode }}
                                @endif
                            </div>
                        </div>
                        <div id="shipping_address_add">
                            <a class="btn btn-outline-white text-danger fw-bold bg-white addr_btn"
                                style="{{ isset($customer) && $customer->shipping_address ? 'display:none;' : '' }}"
                                data-toggle="collapse" href="#collapseShippingAddr" role="button"
                                aria-expanded="false" aria-controls="collapseShippingAddr">
                                <i class="tio-add-circle-outlined"></i> <span class="text-danger">Add Shipping
                                    Address</span>
                            </a>
                            <div class="collapse {{isset($customer) ? 'show' : ''}}" id="collapseShippingAddr">
                                <div class="card card-body">
                                    <h6>Shipping Address</h6>
                                    <div class="form-row">

                                        <div class="form-group col-md-6">
                                            <label for="sa_address1"><span class="text-danger">*</span>Address Line
                                                1</label>
                                            <input type="text" class="form-control" id="sa_address1"
                                                value="{{ isset($customer) && $customer->shipping_address ? $customer->shipping_address->address1 : '' }}"
                                                name="sa_address1" placeholder="Address Line 1">
                                            <small class="sa_address1_error text-danger"></small>

                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="sa_address2">Address Line 2</label>
                                            <input type="text" class="form-control" id="sa_address2"
                                                value="{{ isset($customer) && $customer->shipping_address ? $customer->shipping_address->address2 : '' }}"
                                                name="sa_address2" placeholder="Address Line 2">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="sa_pincode">Pincode</label>
                                            <input type="text" class="form-control" id="sa_pincode"
                                                value="{{ isset($customer) && $customer->shipping_address ? $customer->shipping_address->pincode : '' }}"
                                                name="sa_pincode" placeholder="Pincode">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="sa_city">City</label>
                                            <input type="text" class="form-control" id="sa_city" name="sa_city"
                                                value="{{ isset($customer) && $customer->shipping_address ? $customer->shipping_address->city : '' }}"
                                                placeholder="City">
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="state"><span class="text-danger">*</span>State</label>
                                            <select class="form-control  js-select2-custom" id="sa_state"
                                                name="sa_state">
                                                <option selected disabled>Select State</option>
                                                @foreach (_states() as $key => $state)
                                                    <option value="{{ $state->id }}"
                                                        {{ isset($customer) && $customer->shipping_address && $customer->shipping_address->state == $state->id ? 'selected' : '' }}>
                                                        {{ $state->state_name . ' (' . $state->state_abbr . ')' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="sa_state_error text-danger"></small>

                                        </div>
                                        @if (!isset($customer))
                                            <div class="form-group d-flex justify-content-end w-100 col-md-12">
                                                <button type="button" onclick="saveAddress('shipping')"
                                                    class="btn btn-outline-primary btn-sm">Save Address</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row col-md-12  p-1 ">
                <div class="col d-flex w-100 justify-content-end my-2">
                    <button class="btn  btn--primary btn-outline-primary">{{isset($customer) ? 'Update' : 'Save'}}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="billingAddressModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add New Customer</h5>
                <button type="button" class="close customer_close_btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

<script></script>
