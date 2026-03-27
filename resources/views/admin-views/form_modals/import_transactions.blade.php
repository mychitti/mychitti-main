  <div class="modal fade" id="importTxnModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Import Transactions</h5>
                  <button type="button" class="close account_modal_close_btn" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <a href="{{ asset('storage/app/public/util') }}/account_transactions.xlsx"
                      class="btn btn-primary btn-outline-primary mb-2">Download Example Excel</a>

                  <form class="customer_add_form" enctype="multipart/form-data" method="post"
                      action="{{ route('admin.account.banking.bank-account.transaction-import') }}">
                      @csrf

                      <div class="col-md-12 p-2 mb-3 row">

                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 position-relative border p-2">
                                  <label class="" for="">
                                      <span>Bank Account</span>
                                  </label>
                                  <div class="bank_info">
                                  </div>
                                  <input type="hidden" name="bank_account" class="bank_account_inp">
                                  {{-- <select required name="bank_account" id="bank_account" class="form-control js-select2-custom"
                                      data-placeholder="Select Bank Account">
                                      <option value=""></option>
                                      @foreach ($accounts as $account)
                                          <option value="{{ $account['id'] }}">
                                              {{ $account['bank_name'] }}</option>
                                      @endforeach
                                  </select> --}}
                              </div>
                          </div>
                          <div class="col-md-6 p-2 mb-3">
                              <div class="form-group mb-0 ">
                                  <label class="d-flex justify-content-between switch toggle-switch-sm text-dark"
                                      for="">
                                      <span>File Upload</span>
                                  </label>
                                  <input required name="file" type="file" class="form-control">
                              </div>
                          </div>

                          <div class="col-12 " style="display: flex;align-items: end;justify-content: end;">
                              <button type="button" class="btn btn-secondary mx-2" data-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                      </div>
                  </form>
              </div>
          </div>
      </div>
  </div>
