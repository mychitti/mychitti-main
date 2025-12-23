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
                
                      @if (Illuminate\Support\Str::endsWith(request()->url(), 'invoice-settings') ||
                              Illuminate\Support\Str::endsWith(request()->url(), 'create-invoice') || 
                                                        Route::currentRouteName() == 'vendor.invoice.settings'
                              )
                                <form class="customer_add_form" enctype="multipart/form-data" method="post"
                      action="{{ route('vendor.business-settings.new-bank-account') }}">
                          <input type="hidden" name="type" value="invoice">
                      @else
                        <form class="customer_add_form" enctype="multipart/form-data" method="post"
                      action="{{ route('vendor.quotation.new-bank-account') }}">
                          <input type="hidden" name="type" value="quotation">
                      @endif
                      @csrf
                      <div class="col-md-12 p-2 mb-3 row">
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Bank Name</span>
                                  </label>
                                  <input required name="bank_name" type="text" placeholder="Ex: ICICI"
                                      class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Account Holder Name</span>
                                  </label>
                                  <input required name="account_holder_name" type="text"
                                      placeholder="Ex: Meenu Rathore" class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Bank Account Number</span>
                                  </label>
                                  <input required name="account_number" type="text"
                                      placeholder="Ex: 9999444433337777" class="form-control">
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>Bank IFSC Code</span>
                                  </label>
                                  <input required name="ifsc_code" type="text" placeholder="Ex: ICICI0001234"
                                      class="form-control">
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
                          <div class="col-md-6 p-2 " style="display: flex;align-items: end;justify-content: end;">
                              <button type="button" class="btn btn-secondary mx-2" data-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                      </div>
                  </form>
              </div>
          </div>
      </div>
  </div>
