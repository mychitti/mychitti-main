  <div class="modal fade" id="milestoneModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Add Project Milestone</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <div class="card-header py-2 d-flex justify-content-between align-items-center">
                      <h5 class="card-title mb-0">Milestones</h5>
                      <button type="button" class="btn btn-sm btn--primary" id="addMilestone">
                          + Add Milestone
                      </button>
                  </div>
                  <div class="card-body">
                      <form action="{{ route('admin.project.milestone.store') }}" method="post">
                          @csrf
                          <input type="hidden" name="project_id" value="{{ $project->id }}">
                          <!-- Default milestone row -->
                          <div id="milestoneContainer">
                              <div class="row milestone-row  p-2 mb-2">
                                  <div class="col-md-4">
                                      <label>Milestone Title <span class="text-danger">*</span></label>
                                      <input type="text" name="milestones[0][title]" class="form-control"
                                          placeholder="Ex: Design Phase">
                                  </div>

                                  <div class="col-md-3">
                                      <label>Due Date <span class="text-danger">*</span></label>
                                      <input type="date" name="milestones[0][due_date]" class="form-control">
                                  </div>

                                  <div class="col-md-3">
                                      <label>Status</label>
                                      <select name="milestones[0][status]" class="form-control">
                                          <option value="Pending">Pending</option>
                                          <option value="In Progress">In Progress</option>
                                          <option value="Completed">Completed</option>
                                      </select>
                                  </div>

                                  <div class="col-md-1 d-flex align-items-end">
                                      <button type="button" class="btn btn-outline-danger removeMilestone "><i
                                              class="tio-delete"></i></button>
                                  </div>
                              </div>
                          </div>

                          <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                              <button type="submit" class="btn btn-primary">Save</button>
                          </div>
                      </form>
                  </div>

              </div>

          </div>
      </div>
  </div>
