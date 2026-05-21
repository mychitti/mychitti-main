 <script>
     let milestoneIndex = 1;
 
     $('#addMilestone').click(function() {

         let html = `
        <div class="row milestone-row p-2 mb-2">
            <div class="col-md-4">
                <label>Milestone Title <span class="text-danger">*</span></label>
                <input type="text" name="milestones[${milestoneIndex}][title]" class="form-control" placeholder="Ex: Design Phase">
            </div>

            <div class="col-md-3">
                <label>Due Date <span class="text-danger">*</span></label>
                <input type="date" name="milestones[${milestoneIndex}][due_date]" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Status</label>
                <select name="milestones[${milestoneIndex}][status]" class="form-control">
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger removeMilestone "><i class="tio-delete"></i></button>
            </div>
        </div>
        `;

         $('#milestoneContainer').append(html);
         milestoneIndex++;
     });

     // Remove Milestone Row
     $(document).on('click', '.removeMilestone', function() {
         $(this).closest('.milestone-row').remove();
     });
 </script>
