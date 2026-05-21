   <form class="custom_ajax_form w-100" action="{{ route('vendor.staff-department.save') }}" method="post">
                @csrf
                <div class="col-md-12 p-0">
                    <div class="card h-100">
                        <div class="card-body row">
                            <div class="form-row col-12">
                                <label for="exampleInputEmail1">Department Title</label>
                                <input type="text" name="title" placeholder="Department Title" class="form-control">
                            </div>

                            <div class="form-row col-12">
                                <label for="inputState">Status</label>
                                <select name="status" id="inputState" class="form-control">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="form-row d-flex align-items-end col-12 mt-2">
                                <button class="btn btn--primary ">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>