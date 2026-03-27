 <div class="modal fade" id="taskStatusModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Available Statuses</h5>
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
             <div class="modal-body">
                 <b>Default Statuses : </b>
                 <a href="javascript:;" class="badge badge-light">New</a>
                 <a href="javascript:;" class="badge badge-light">Completed</a>
                 <a href="javascript:;" class="badge badge-light">Cancelled</a>


                 <form action="{{ route('admin.task.status.save-new') }}" method="post">
                     @csrf
                     <div class="form-group subcategory_1">
                         <div class="form-group mb-4">
                             <label class="input-label" id="" for="other_verification">Selected</label>
                             <select name="statuses[]" multiple="multiple"
                                 class="  js-select2-custom-tags js-example-basic-multiple" data-placeholder="Statuses"
                                 id="statusSelect">
                                 <option value=""></option>
                                 @if ($store_data->task_statuses)
                                     @foreach (explode(',', $store_data->task_statuses) as $sc)
                                         <option
                                             {{ $store_data->task_statuses && in_array($sc, explode(',', $store_data->task_statuses)) ? 'selected' : '' }}
                                             value="{{ $sc }}">{{ $sc }}</option>
                                     @endforeach
                                 @endif
                             </select>
                         </div>
                     </div>
                     <button class="btn btn-primary">Update</button>
                 </form>
             </div>
         </div>
     </div>
 </div>
