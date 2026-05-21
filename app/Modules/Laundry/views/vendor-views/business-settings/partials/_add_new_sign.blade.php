  <div class="modal fade" id="addSignModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Add New Signature</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                
                        @if (Illuminate\Support\Str::endsWith(request()->url(), 'invoice-settings') ||
                              Illuminate\Support\Str::endsWith(request()->url(), 'create-invoice') || 
                                                     Route::currentRouteName() == 'vendor.invoice.settings'
)  <form class="customer_add_form" enctype="multipart/form-data" action="{{ route('vendor.business-settings.signature.save') }}"
                      method="post">
                      @csrf
                          <input type="hidden" name="type" value="invoice">
                      @else
                        <form class="customer_add_form" enctype="multipart/form-data" action="{{ route('vendor.quotation.signature.save') }}"
                      method="post">
                      @csrf
                          <input type="hidden" name="type" value="quotation">
                      @endif

                      <div class="d-flex">
                          <div class=" ">
                              <div class="flex-grow-1 mx-auto">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="invoice_sign_status">
                                      <span>Invoice Sign<span class="form-label-secondary" data-toggle="tooltip"
                                              data-placement="right"
                                              data-original-title="{{ translate('messages.transparent or white backgroud recommended') }}"><img
                                                  src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"></span></span>

                                  </label>
                                  <label class="d-inline-block m-0 position-relative">
                                      <img class="img--136 border onerror-image" id="viewer"
                                          src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                              $store['signature'] ?? '',
                                              asset('storage/app/public/store/signature') . '/' . $store['signature'] ?? '',
                                              asset('public/assets/admin/img/upload-img.png'),
                                              'store/signature/',
                                          ) }}"
                                          data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                          alt="thumbnail" />
                                      <div class="icon-file-group">
                                          <div class="icon-file">
                                              <input type="file" name="image" id="customFileEg1"
                                                  class="custom-file-input read-url"
                                                  accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                              <i class="tio-edit"></i>
                                          </div>
                                      </div>
                                  </label>

                              </div>
                          </div>
                          <div class="mx-3 w-50">
                              <label for="inputGroupFile04">Signature By</label>
                              <select name="staff" id="staff" class="form-control js-select2-custom">
                                  <option value="0">Self</option>
                                  @foreach ($staffs as $key => $st)
                                      <option value="{{ $st->id }}">{{ $st->f_name . ' ' . $st->l_name }}
                                      </option>
                                  @endforeach
                              </select>
                          </div>
                      </div>
                      <button class="btn btn--primary mt-2">Save</button>
                  </form>
              </div>
          </div>
      </div>
  </div>
