@extends('layouts.admin.app')

@section('title', translate('App Settings'))

@push('css_or_js')
    <style>
        /* otp element styling  */
        .otp-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }

        .otp-container h2 {
            margin-bottom: 20px;
        }

        .otp-container p {
            margin-bottom: 20px;
            color: #666;
        }

        .otp-form {
            display: flex;
            justify-content: space-between;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            margin: 3px;
            text-align: center;
            font-size: 26px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/firebase.png') }}" class="w--26" alt="">
                </span>
                <span>{{ translate('messages.app_setup') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-shadow pb-0">
                        <div class="d-flex flex-wrap justify-content-between w-100 row-gap-1">
                            <h5>Upload JSON File</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.pr-file.upload') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex gap-2">
                                <input type="file" name="file" accept=".json" id="" class="form-control">
                                <button class="btn btn-outline-primary my-2" type="submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header card-header-shadow pb-0">
                        <div class="d-flex flex-wrap justify-content-between w-100 row-gap-1">
                            <h5>Uploaded Files</h5>
                        </div>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable" class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                                <tr>
                                    <th>sno.</th>
                                    <th>File Name</th>
                                    <th>Uploaded at</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sFiles as $key => $value)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $value->name }} ({{ $value->file_type }})</td>
                                        <td>{{$value->created_at}}</td>
                                        <td>
                                            <button class="btn btn-primary file_download" data-id="{{$value->id}}" type="button" data-toggle="modal"
                                                data-target="#authForFileModal">Download file</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="modal fade" id="authForFileModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container py-5">
                        <h2 class="text-center">Enter OTP</h2>
                        <div class="p-5 bg-light rounded" style="max-width: 550px; margin: 0 auto;">
                            <div class="row ">
                                <form class="otpForm" style="margin: 0 auto;" action="{{ route('admin.verify-otp') }}"
                                    method="post">
                                    <p>OTP has been sent to master admin. Please verify.</p>
                                    @csrf
                                    <input type="hidden" class="file_id" name="file" value="">
                                    <div class="d-flex justify-content-center w-100">
                                        <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                        <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                        <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                        <input type="text" maxlength="1" class="otp-input" name="otp[]" />
                                    </div>
                                    <button type="submit" class="btn btn-lg btn-block btn--primary mt-3">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    @include('admin-views/js/otp_js')
    <script>
        $(document).on('input', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const index = $inputs.index(this);

            if (this.value.length === this.maxLength && index < $inputs.length - 1) {
                $inputs.eq(index + 1).focus();
            }
        });
    </script>
@endpush
