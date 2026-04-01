   <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
       <div class="modal-dialog">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title" id="exampleModalLabel">Reassign To </h5>
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                       <span aria-hidden="true">&times;</span>
                   </button>
               </div>
               <form method="post" action="{{ route('vendor.task.reassign') }}">
                   @csrf
                   <div class="modal-body">
                       <label for="">Reassign to</label>
                       <input type="hidden" name="task_id" class="task_id">
                       <select name="employee_id" id="employee_id2ew" required
                           data-placeholder="{{ translate('messages.select staff') }}"
                           class="form-control js-select2-custom "> 
                           <option value=""></option>
                           <option value="0">Self</option>
                           @foreach ($staff as $key => $s)
                               <option value="{{ $s->id }}" >
                                   {{ $s->f_name . ' ' . $s->l_name . ' | ' . $s->role?->name }}</option>
                           @endforeach
                       </select>
                   </div>
                   <div class="modal-footer">
                       <button type="submit" class="btn btn-primary">Save</button>
                   </div>
               </form>
           </div>
       </div>
   </div>
