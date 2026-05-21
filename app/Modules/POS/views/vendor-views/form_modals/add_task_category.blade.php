  <div class="modal fade" id="taskCatAddModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content"> 
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Add Task Category</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <form method="POST" action="{{ route('vendor.task-salary-categories.store') }}">
                  @csrf
                  @if (isset($taskSalaryCategory))
                      @method('PUT')
                  @endif
                  {{-- <div class="modal-body">
                      <div class="row">
                          <label for="">Category Name</label>
                          <input type="text" class="form-control" name="name"
                              value="{{ old('name', $taskSalaryCategory->name ?? '') }}" placeholder="Category Name"
                              required>
                          <label for="">Amount (per task)</label>
                          <input type="number" class="form-control" step="0.001" name="amount"
                              value="{{ old('amount', $taskSalaryCategory->amount ?? '') }}" placeholder="Amount"
                              required>
                      </div>
                  </div> --}}
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                      <button type="submit"
                          class="btn btn-primary">{{ isset($taskSalaryCategory) ? 'Update' : 'Create' }}</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
