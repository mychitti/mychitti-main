@extends('layouts.vendor.app')
@section('title', 'Edit Restaurant Table')

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header" style="display: flex;justify-content: space-between;">
            <h1 class="page-header-title"><i class="tio-filter-list"></i>Edit Restaurant Tables <span
                    class="badge badge-soft-dark ml-2" id="itemCount"></span></h1>
            <div class="page-header-select-wrapper">

            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">Restaurant Tables</h5>

                </div>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('vendor.pos.restaurant-tables.update', $table->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Table Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $table->name }}" required>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Capacity</label>
                            <input type="number" name="capacity" class="form-control" value="{{ $table->capacity }}">
                        </div>

                        {{-- <div class="form-group col-md-3">
                            <label>Room Type</label>
                            <select name="room_type" class="form-control">
                                <option value="non_ac" {{ $table->room_type == 'non_ac' ? 'selected' : '' }}>Non AC</option>
                                <option value="ac" {{ $table->room_type == 'ac' ? 'selected' : '' }}>AC</option>
                            </select>
                        </div> --}}

                        <div class="form-group col-md-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="free" {{ $table->status == 'free' ? 'selected' : '' }}>Free</option>
                                <option value="occupied" {{ $table->status == 'occupied' ? 'selected' : '' }}>Occupied
                                </option>
                                {{-- <option value="reserved" {{ $table->status == 'reserved' ? 'selected' : '' }}>Reserved
                                </option> --}}
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end w-100">
                        <button class="btn btn-primary">Update</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection
