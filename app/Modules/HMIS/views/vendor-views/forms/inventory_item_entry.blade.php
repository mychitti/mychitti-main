  <style>
      .manual_form {
          background: #fdfdfd;
          border: 2px dashed #ebebeb;
          padding: 10px;
          border-radius: 10px;
      }

      .pdf_form {
          background: #f1ffffff;
          border: 2px dashed #ebebeb;
          padding: 25px;
          border-radius: 10px;
      }

      @media (max-width: 1000px) {
          .inv_entry_item_row {
              display: flex;
              flex-wrap: wrap;
              box-shadow: 0px 0px 14px #e2e2e2;
              padding: 10px;
              border: none;
              border-radius: 6px;
              align-items: end;
          }

          .hide_on_phone {
              display: none;
          }

          .entry_tab {
              padding: 0;
          }
      }
  </style>

  <div class="row col-12 g-0 align-items-start entry_tab">
      {{-- <div class="col-12 pdf_form">
          <form enctype="multipart/form-data" id="bill_form" class="w-100" action="{{ route('vendor.inventory.entry.save-pdf') }}"
              method="post">
              @csrf
              <div class="d-flex gap-2 align-items-center justify-content-center">
                  <label for="">Upload Bill</label>
                  <input type="file" accept=".pdf,image/*" style="max-width: 241px;" class="form-control"
                      name="file">
                  <button class="btn  btn--primary btn-outline-primary">Extract</button>
              </div>
          </form>
      </div>
      <div class="col-12 p-3 text-center">
          <h2>--- OR ---</h2>
      </div> --}}
      Marked fields are required
      <form enctype="multipart/form-data" class="w-100" action="{{ route('vendor.inventory.entry.save') }}" method="post">
          @csrf

          <div class="col-12 row g-0 manual_form">
              <div class="form-row col-md-3">
                  <label for="exampleInputEmail1">Date </label>
                  <input type="date" name="date" value="{{ date('Y-m-d') }}" class="form-control">
              </div>
              <div class="form-row col-md-3">
                  <label for="exampleInputEmail1">Bill No.</label>
                  <input type="text" name="bill_number" placeholder="Bill No." class="form-control">
              </div>
              <table class="table mt-2">
                  <thead style=" background: ##f1ffff;">
                      <tr>
                          <th class="hide_on_phone" scope="col">Item Name / SKU / Model</th>
                          <th class="hide_on_phone" scope="col">Variation</th>
                          <th class="hide_on_phone" scope="col">Add Stock</th>
                          <th class="hide_on_phone" scope="col">MRP</th>
                          <th class="hide_on_phone" scope="col">Storage Unit</th>
                          <th class="hide_on_phone" scope="col">Product Condition</th>
                          <th class="hide_on_phone" scope="col"> Purchase Price</th>
                          <th class="hide_on_phone" class="tax_inp_data " scope="col">
                              Selling Price</th>
                          <th><button type="button" class="btn btn-dark btn-sm" onclick="addMoreRowInvEntry()">Add
                                  More</button></th>
                      </tr>
                  </thead>
                  <tbody class="rows_parent_inv_item">
                      <tr class="inv_entry_item_row" data-id="1">
                          <td> <select name="item_id[]" onchange="fetch_item_details(this.value, 1)"
                                  class="form-control item_select item_select_1 js-select2-custom-class">
                                  <option value="">---{{ translate('messages.select') }}---
                                  </option>
                                  @foreach ($items as $item)
                                      <option value="{{ $item['id'] }}">
                                          {{ $item['item_name'] . ' | ' . ($item['company_sku_id'] ?? $item['sku_id']) . ' | ' . $item['model_number'] }}
                                      </option>
                                  @endforeach
                              </select> </td>
                          <td>
                              <div class="form-row var_inp_1 " style="display: none;">

                                  <select name="variation_type[]" onchange="fetch_variation_dets(this.value, 1)"
                                      data-placeholder="Select Variation"
                                      class="variation_select_1 variation_select js-select2-custom">
                                      <option></option>
                                  </select>
                              </div>
                          </td>

                          {{-- <td> <input type="number" name="secondary_quantity[]" placeholder="Stock" class="secondary_quantity_1 form-control"></td> --}}
                          <td> <input type="number" name="quantity[]" placeholder="Add Stock"
                                  class="quantity_1 form-control"></td>
                          <td>
                              <input type="text" name=""  placeholder="MRP"
                                  class="variation_mrp_1 form-control" step="0.001" >
                          </td>
                          <td>
                              <select name="storage_unit_id[]" 
                                  class="form-control js-select2-custom storage_unit_1">
                                  <option value="">---{{ translate('messages.select') }}---
                                  </option>
                                  @php $storage_units = \App\Models\StorageUnit::with('parent')->where('store_id', \App\CentralLogics\Helpers::get_store_id())->get(); @endphp
                                  @foreach ($storage_units as $unit)
                                      <option value="{{ $unit['id'] }}">
                                          {{ $unit->parent ? $unit->parent->name . ' > ' : '' }}{{ $unit['name'] . ' (' . $unit->type . ')' }}
                                      </option>
                                  @endforeach
                              </select>
                          </td>
                          <td>
                              <select class="custom-select" name="product_conditon[]"
                                  class="form-control js-select2-custom">
                                  <option value="new">New Product</option>
                                  <option value="used">Used Product</option>
                              </select>
                          </td>
                          <td>
                              <div class="input-group ">
                                  <input type="number" required name="landing_price[]" placeholder="Purchase price"
                                      onkeyup="checkPricing(this)" class="variation_purchase_1 form-control" step="0.001">
                                  <select class="custom-select" onchange="checkPricing(this)" name="price_gst_status[]"
                                      class="form-control js-select2-custom">
                                      <option value="including_gst">Incl. GST</option>
                                      <option value="excluding_gst">Excl. GST</option>
                                  </select>
                                  <small class="text-danger landing_price_error"></small>
                              </div>
                          </td>
                          <td>
                              <input type="number" name="selling_price[]" placeholder="Selling Price" step="0.001"
                                  class="variation_sell_1 variation_sell form-control">
                              <small class="text-danger selling_error"></small>
                          </td>
                          <td><button type="button" onclick="deleteInvEntRow(1)"
                                  class="btn action-btn btn--danger btn-outline-danger"><i
                                      class="tio-delete-outlined"></i></button></td>
                      </tr>
                  </tbody>
              </table>
              <div class="col-12 w-100 d-flex justify-content-end">
                  <button class="btn btn-primary">Save</button>
              </div>

          </div>
      </form>
  </div>
