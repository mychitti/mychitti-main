@extends('layouts.vendor.app')
@section('title', isset($member) ? 'Edit Staff Member' : 'Add Staff Member')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
            </span>
            <span>{{ isset($member) ? 'Edit Staff Member' : 'Add New Staff Member' }}</span>
        </h1>
        <a href="{{ route('vendor.basic-staff.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    <div class="row">
        {{-- Main form --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Basic Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ isset($member)
                        ? route('vendor.basic-staff.update', $member->id)
                        : route('vendor.basic-staff.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="f_name" class="form-control @error('f_name') is-invalid @enderror"
                                        value="{{ old('f_name', $member->f_name ?? '') }}"
                                        placeholder="First name" required>
                                    @error('f_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">Last Name</label>
                                    <input type="text" name="l_name" class="form-control"
                                        value="{{ old('l_name', $member->l_name ?? '') }}"
                                        placeholder="Last name (optional)">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $member->phone ?? '') }}"
                                        placeholder="Phone number" required>
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $member->email ?? '') }}"
                                        placeholder="Email address" required>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">Role</label>
                            <select name="employee_role_id" class="form-control">
                                <option value="">— No role —</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ old('employee_role_id', $member->employee_role_id ?? '') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                <a href="{{ route('vendor.basic-staff.roles') }}" target="_blank">Manage roles</a>
                            </small>
                        </div>

                        {{-- Photo --}}
                        <div class="form-group">
                            <label class="input-label">Profile Photo</label>
                            <div class="custom-file">
                                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror"
                                    id="photoInput" accept="image/*">
                                {{-- <label class="custom-file-label" for="photoInput">Choose photo (jpg/png, max 2MB)</label> --}}
                            </div>
                            @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @if (!empty($member->image))
                                <div class="mt-2">
                                    <img id="photoPreview"
                                        src="{{ asset('storage/vendor/' . $member->image) }}"
                                        class="rounded" style="height:80px;width:80px;object-fit:cover;" alt="">
                                </div>
                            @else
                                <div class="mt-2">
                                    <img id="photoPreview" src="#" class="rounded d-none"
                                        style="height:80px;width:80px;object-fit:cover;" alt="">
                                </div>
                            @endif
                        </div>

                        {{-- ID Proof --}}
                        <div class="form-group">
                            <label class="input-label">ID Proof <small class="text-muted">(jpg, png or PDF, max 4MB)</small></label>
                            <div class="custom-file">
                                <input type="file" name="id_proof" class="custom-file-input @error('id_proof') is-invalid @enderror"
                                    id="idProofInput" accept=".jpg,.jpeg,.png,.pdf">
                                <label class="custom-file-label" for="idProofInput">Choose file</label>
                            </div>
                            @error('id_proof')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @if (!empty($member->id_document))
                                @php $ext = strtolower(pathinfo($member->id_document, PATHINFO_EXTENSION)); @endphp
                                <div class="mt-2">
                                    @if ($ext === 'pdf')
                                        <a href="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}" target="_blank"
                                            class="btn btn-xs btn-outline-secondary">
                                            <i class="tio-file-text-outlined"></i> View existing PDF
                                        </a>
                                    @else
                                        <a href="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}" target="_blank">
                                            <img src="{{ asset('storage/app/public/employee/id-proof/' . $member->id_document) }}"
                                                class="rounded border" style="height:70px;width:auto;" alt="ID Proof">
                                        </a>
                                    @endif
                                    <small class="d-block text-muted mt-1">Upload a new file to replace the existing one.</small>
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">Password {!! isset($member) ? '' : '<span class="text-danger">*</span>' !!}</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                            placeholder="{{ isset($member) ? 'Leave blank to keep current' : 'Set login password' }}"
                                            {{ isset($member) ? '' : 'required' }}>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                                <i class="tio-visible-outlined"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">Confirm Password {!! isset($member) ? '' : '<span class="text-danger">*</span>' !!}</label>
                                    <div class="input-group">
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                                            placeholder="Re-enter password"
                                            {{ isset($member) ? '' : 'required' }}>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_confirmation">
                                                <i class="tio-visible-outlined"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('vendor.basic-staff.index') }}" class="btn btn-outline-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn--primary">
                                {{ isset($member) ? 'Update Staff Member' : 'Add Staff Member' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Side info --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Free Plan Features</h6></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0" style="font-size:0.875rem;">
                        <li class="mb-1"><i class="tio-checkmark-circle text-success mr-1"></i> Name &amp; contact info</li>
                        <li class="mb-1"><i class="tio-checkmark-circle text-success mr-1"></i> Profile photo</li>
                        <li class="mb-1"><i class="tio-checkmark-circle text-success mr-1"></i> ID proof upload</li>
                        <li class="mb-1"><i class="tio-checkmark-circle text-success mr-1"></i> Role assignment</li>
                        <li class="mb-1"><i class="tio-checkmark-circle text-success mr-1"></i> Up to 10 staff members</li>
                        <li class="mt-3 mb-1 text-muted"><i class="tio-lock-outlined mr-1"></i> Attendance &amp; leaves</li>
                        <li class="mb-1 text-muted"><i class="tio-lock-outlined mr-1"></i> Salary management</li>
                        <li class="mb-1 text-muted"><i class="tio-lock-outlined mr-1"></i> Shifts &amp; departments</li>
                    </ul>
                    <a href="{{ route('vendor.subscriptions') }}" class="btn btn-sm btn--primary w-100 mt-3">
                        Upgrade to HR Management
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    // Show filename in custom-file-label
    $('.custom-file-input').on('change', function () {
        var name = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').text(name || 'Choose file');
    });

    // Password toggle
    $(document).on('click', '.toggle-password', function () {
        var $input = $($(this).data('target'));
        var $icon  = $(this).find('i');
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('tio-visible-outlined').addClass('tio-visible-off-outlined');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('tio-visible-off-outlined').addClass('tio-visible-outlined');
        }
    });

    // Photo preview
    $('#photoInput').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#photoPreview').attr('src', e.target.result).removeClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush
