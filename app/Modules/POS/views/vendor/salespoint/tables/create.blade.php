@extends('layouts.vendor.app')
@section('title', 'Add Restaurant Table')

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header" style="display: flex;justify-content: space-between;">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Add Restaurant Table <span
                    class="badge badge-soft-dark ml-2" id="itemCount"></span></h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
 

            <div class="card-body">

                <form method="POST" action="{{ route('vendor.pos.restaurant-tables.store') }}">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Table Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Capacity</label>
                            <input type="number" name="capacity" class="form-control">
                        </div>

                        {{-- <div class="form-group col-md-3">
                            <label>Room Type</label>
                            <select name="room_type" class="form-control" required>
                                <option value="non_ac">Non AC</option>
                                <option value="ac">AC</option>
                            </select>
                        </div> --}}
                    </div>
                    <div class="d-flex justify-content-end w-100">
                        <button class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
