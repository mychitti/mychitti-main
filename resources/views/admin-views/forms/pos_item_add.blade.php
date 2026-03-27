<form action="{{ route('vendor.pos.items.save') }}" method="post" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="input-label"
                                        for="exampleFormControlInput1">{{ translate('messages.branches') }}</label>
                                 
                                    <select id="branch" required name="branch" class=" form-control ">
                                        <option value=""></option>
                                        @php $branches = \App\Models\Branch::where('store_id', \App\CentralLogics\Helpers::get_store_id())->get() @endphp
                                        @foreach ($branches as $key => $value)
                                            <option value="{{ $value->id }}">{{ $value->name }} ({{ucfirst($value->type)}})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <input type="hidden" value="inv_item" name="item_type">
                                
                                <label for="">Inventory Items</label>
                                <div id="itemDiv">
                                    <select name="items[]" multiple id="items" data-placeholder="Select Inventory Item"
                                        class="form-control">
                                        <option value=""></option>
                                        @php $inventory_items = _inventoryItems() @endphp

                                        @foreach ($inventory_items as $key => $value)
                                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="items_header" style="display:none;">
                                    <div class="item-row d-grid align-items-center p-2 mb-2 rounded shadow-sm item_r2">
                                        <div class="fw-bold text-truncate">Item Name</div>
                                        <span>Stock</span>
                                        <span>Price</span>
                                        <span class="gst_header" style="display:none;">GST</span>
                                    </div>
                                </div>
                                <div id="branchFieldsContainer"></div>
                            </div>
                        </div>
                        <div class="btn--container justify-content-end mt-3">
                            <button type="submit" class="btn btn--primary">Add</button>
                        </div>

                    </form>