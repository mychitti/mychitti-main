  <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Add New Bank Account</h5>
                  <button type="button" class="close account_modal_close_btn" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>  
              <div class="modal-body">  
                  @php
                      $currentRoute = Route::currentRouteName();
                      $formAction = route('admin.business-settings.new-bank-account');
                      $accountType = 'invoice';
                      $isPosContext = false;

                      if (
                          Illuminate\Support\Str::endsWith(request()->url(), 'quotation-settings') ||
                          $currentRoute === 'admin.quotation.settings'
                      ) {
                          $formAction = route('admin.quotation.new-bank-account');
                          $accountType = 'quotation';
                      }
                  @endphp 
                  <form class="customer_add_form" enctype="multipart/form-data" method="post"
                      action="{{ $formAction }}">
                      <input type="hidden" name="type" value="{{ $accountType }}">
                      @csrf
                      <div class="col-md-12 p-2 mb-3 row">
                          @if ($isPosContext)
                              <div class="col-md-12 p-2 mb-3">
                                  <div class="form-group mb-0">
                                      <label class="d-flex justify-content-between switch toggle-switch-sm text-dark">
                                          <span>Payment Type</span>
                                      </label>
                                      <select name="payment_type" class="form-control js-payment-type">
                                          <option value="bank">Bank Account</option>
                                          <option value="upi">UPI ID</option>
                                      </select>
                                  </div>
                              </div>
                          @endif
                          <div class="row w-100 m-0 js-bank-fields">
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Bank Name</span>
                                  </label>
                                  <input {{ $isPosContext ? '' : 'required' }} name="bank_name" type="text"
                                      placeholder="Ex: ICICI"
                                      class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Account Holder Name</span>
                                  </label>
                                  <input {{ $isPosContext ? '' : 'required' }} name="account_holder_name" type="text"
                                      placeholder="Ex: Meenu Rathore" class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Bank Account Number</span>
                                  </label>
                                  <input {{ $isPosContext ? '' : 'required' }} name="account_number" type="text"
                                      placeholder="Ex: 9999444433337777" class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Bank IFSC Code</span>
                                  </label>
                                  <input {{ $isPosContext ? '' : 'required' }} name="ifsc_code" type="text"
                                      placeholder="Ex: ICICI0001234"
                                      class="form-control">
                              </div>
                          </div>
                          </div>
                          <div class="row w-100 m-0 js-upi-fields" style="{{ $isPosContext ? 'display:none;' : '' }}">
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>UPI ID</span>
                                  </label>
                                  <input name="upi_id" type="text" placeholder="Ex: myname@upi" class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 flex-grow-1 mx-auto">
                              <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                  for="u">
                                  <span>UPI QR Code</span>

                              </label>
                              <label class="d-inline-block m-0 position-relative">
                                  <img class="img--136 border onerror-image" id="coverImageViewer"
                                      src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                      data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                      alt="thumbnail" />
                                  <div class="icon-file-group">
                                      <div class="icon-file">
                                          <input type="file" name="upi_qr_code" id="coverImageUpload"
                                              class="custom-file-input read-url"
                                              accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                          <i class="tio-edit"></i>
                                      </div>
                                  </div>
                              </label>

                          </div>
                          </div>
                          <div class="col-12 p-1 " style="display: flex;align-items: end;justify-content: end;">
                              <button type="button" class="btn btn-secondary mx-2" data-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                      </div>
                  </form>
              </div>
          </div>
      </div>
  </div>

  @if ($isPosContext)
      @push('script_2')
          <script>
              (function() {
                  const form = document.querySelector('#accountModal form.customer_add_form');
                  if (!form) return;

                  const paymentType = form.querySelector('.js-payment-type');
                  const bankFields = form.querySelector('.js-bank-fields');
                  const upiFields = form.querySelector('.js-upi-fields');
                  const bankInputs = form.querySelectorAll('[name="bank_name"],[name="account_holder_name"],[name="account_number"],[name="ifsc_code"]');
                  const upiInput = form.querySelector('[name="upi_id"]');

                  const toggleFields = () => {
                      const isUpi = paymentType && paymentType.value === 'upi';
                      bankFields.style.display = isUpi ? 'none' : 'flex';
                      upiFields.style.display = isUpi ? 'flex' : 'none';
                      bankInputs.forEach(input => input.required = !isUpi);
                      if (upiInput) upiInput.required = isUpi;
                  };

                  if (paymentType) {
                      paymentType.addEventListener('change', toggleFields);
                      toggleFields();
                  }
              })();
          </script>
      @endpush
  @endif
