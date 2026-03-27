 <style>
     @media (max-width: 700px) {
         .rr_table th {
             min-width: 200px !important;
         }
     }
 </style>
 <div class=" col-12 p-3" style="margin: 3px;">
     <div class="row">
         <div class=" col-md-3">
             <label for="exampleInputEmail1">Received By</label>
             <select name="received_by" id="received_by" required
                 data-placeholder="{{ translate('messages.select received by') }}"
                 class="form-control js-select2-custom ">
                 <option value="0" selected>---{{ translate('messages.select') }}---</option>
                 {{-- <option value="0">Self</option> --}}
                 @php $staff = _storeEmployees(true); @endphp
                 @foreach ($staff as $key => $s)
                     <option value="{{ $s->id }}">
                         {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}</option>
                 @endforeach
             </select>
         </div>
         <div class=" col-md-2">
             <label for="exampleInputEmail1">Receipt Type</label>
             <div class="">
                 <input type="radio" name="rr_type" id="type1" value="returnable" checked>
                 <label for="type1">Returnable</label><br>
                 <input type="radio" name="rr_type" id="type2" value="non_returnable">
                 <label for="type2">Non Returnable</label>
             </div>
         </div>
         <div class=" col-md-3">
             <label for="exampleInputEmail1">Receipt Number</label>
             <div class="d-flex  ">
                 <input style="border-radius: 8px 0 0 8px; border-right:0px;" value="{{ _receipt_prefix() }}"
                     type="text" name="receipt_prefix" id="receipt_prefix" class="form-control" placeholder="Prefix"
                     required>
                 <input style="border-radius: 0 8px 8px 0" type="text" value="{{ _receipt_serial_number() }}"
                     name="receipt_serial" id="receipt_serial" class="form-control" placeholder="Number" required>
             </div>
         </div>


     </div>
 </div>
 <div class=" col-12 p-3" style="background: #f6f6f680; margin: 3px;">

     <div class="d-flex w-100 justify-content-end p-2">
         <button style="width:fit-content; padding: 5px 10px !important" type="button"
             class="btn btn-dark action-btn  add_more_btn" onclick="addMoreRRRow()">+
             Add More</button>
     </div>

     <table class="table table-responsive rr_table">

         <thead class="" style=" background: #fae0ff; ">
             <tr>
                 <th scope="col" class="p-1">Image (Optional)</th>
                 <th scope="col" class="p-1">Webcam</th>
                 <th scope="col" class="p-1">Product Name</th>
                 <th scope="col" class="p-1">Brand / Model</th>
                 <th scope="col" class="p-1">Value ₹ (optional)</th>
                 <th scope="col" class="p-1">Serial No</th>
                 <th scope="col" class="p-1">Received For (Issue)</th>
                 <th scope="col" class="p-1">Accessories Given</th>
                 <th style="    width: 20px !important;
    min-width: 20px !important;"></th>
             </tr>
         </thead>
         <tbody class="rrrows_parent">
             <tr class="item_row row_1" data-id="1">
                 <td class="p-1"><input type="file" name="image[0]" accept="image/*" class="form-control">
                 </td>
                 <td>
                     <div class="webcam_wrapper">
                         <div class="">
                             <button type="button" class="btn btn-primary openWebcam">Open Webcam</button>
                             <button type="button" class="btn btn-primary capture" style="display:none;">Capture
                                 Photo</button>
                             <button type="button" class="btn btn-primary takePhoto" style="display:none;">Take
                                 Photo
                                 (Mobile)</button>
                         </div>

                         <div class="form-row my-2 webcam_section">
                             <input type="file" name="webcam_file[0][]" class="hiddenFile" multiple hidden>
                             <video class="webcam" autoplay playsinline style="display:none; width:300px;"></video>
                             <canvas class="snapshot" style="display:none;"></canvas>
                             <div class="previewContainer" style="margin-top:10px;"></div>
                         </div>
                     </div>
                 </td>

                 <td class="p-1"><input type="text" name="pr_name[]" placeholder="Product Name"
                         class="form-control">
                 </td>
                 <td class="p-1"><input type="text" name="brand[]" placeholder="Brand / Model"
                         class="form-control">
                 </td>
                 <td class="p-1"><input type="number" step="0.001" name="value[]" placeholder="Value   (₹)"
                         class="form-control"></td>

                 <td class="p-1"><input type="text" name="serial_no[]" placeholder="Serial No"
                         class="form-control">
                 </td>
                 <td class="p-1">
                     <textarea name="issue_for[]" class="form-control" id="" placeholder="Received For (Issue)"></textarea>
                 </td>
                 <td class="p-1">
                     <textarea name="accessories_given[]" id="" placeholder="Accessories Given" class="form-control"></textarea>
                 </td>
                 <td class="p-1"><button type="button" onclick="deleteRow(1)"
                         class="btn action-btn btn--danger btn-outline-danger"><i
                             class="tio-delete-outlined"></i></button></td>
             </tr>
         </tbody>
     </table>

 </div>
