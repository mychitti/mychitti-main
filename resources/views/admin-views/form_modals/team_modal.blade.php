  <div class="modal fade" id="teamModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog ">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Edit Team</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <form action="{{route('admin.project.team.update')}}" method="post">
                  @csrf
                          <input type="hidden" name="project_id" value="{{ $project->id }}">

                      <label>Team Members </label>
                      <select name="team_members[]" multiple class="form-control js-select2-custom">
                                    @php $assigned_employee_ids = $project->teamMembers->pluck('employee_id')->toArray();; @endphp

                          @foreach ($employees as $emp)
                              <option   {{ in_array($emp->id, $assigned_employee_ids) ? 'selected' : '' }} value="{{ $emp->id }}">{{ $emp->f_name }} {{ $emp->l_name }}
                              </option>
                          @endforeach
                      </select>
                      <button class="btn btn--primary">Save</button>
                  </form>
              </div>
          </div>
      </div>
  </div>
