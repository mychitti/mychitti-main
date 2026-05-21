@extends('layouts.vendor.app')
@section('title', 'Teams List')
@push('css_or_js')
    <style>
        .members-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .members-icon {
            width: 16px;
            height: 16px;
            color: #666;
        }

        .members-text {
            font-size: 14px;
            color: #666;
            margin-right: 4px;
        }

        .avatars-container {
            display: flex;
            gap: -2px;
        }

        .avatar {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid white;
            margin-left: -2px;
            position: relative;
            overflow: hidden;
        }

        .avatar:first-child {
            margin-left: 0;
        }

        .avatar-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 10px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .select2-selection.select2-selection--multiple {
            height: fit-content !important;
        }

        .color-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }

        .color-swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .color-swatch.selected {
            border-color: #555;
            box-shadow: 0 0 0 3px white, 0 0 0 5px #555;
        }

        .custom-color-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .color-label {
            font-size: 14px;
            margin-right: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h1 class="page-header-title mb-2">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/role.png') }}" class="w--26" alt="">
                    </span>
                    <span>
                        <div class="d-flex" style="align-items:center; gap:5px;">

                            <div style="width:12px; height:12px; background-color:{{ $team->color }};border-radius:50%;">
                            </div> {{ $team->name }}
                        </div>

                    </span>
                </h1>
            </div>
        </div>
        <!-- Page Heading -->
        <div class="card">
            <div class="card-header" style="color:white;background-color:{{ $team->color }}">{{ count($team_members) }}
                Members</div>
            <div class="card-body  row">
                @if (Route::currentRouteName() == 'vendor.staff.team.edit')
                    <div class="col-md-6">
                        <form action="{{ route('vendor.staff.team.update') }}" method="post">
                            @csrf
                            <input type="hidden" name="team_id" value="{{ $team->id }}">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="exampleInputEmail1">Team Leader</label>
                                    <select name="team_leader" id="team_leader">
                                        <option value=""></option>
                                        @foreach ($staff as $key => $st)
                                            <option {{ $st->id == $team->team_lead ? 'selected' : '' }}
                                                value="{{ $st->id }}"
                                                data-image="{{ asset('storage/app/public/vendor') . '/' . $st->image }}">
                                                {{ $st->f_name . ' ' . $st->l_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="exampleInputEmail1">Name</label>
                                    <input type="text" name="team_name" value="{{ $team->name }}"
                                        placeholder="Ex: Designers" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Appearence</label>
                                <div class="color-picker-wrapper">
                                    <div class="color-swatch" data-color="#0f0f23"
                                        {{ $team->color == '#0f0f23' ? 'selected' : '' }} style="background: #0f0f23;">
                                    </div>
                                    <div class="color-swatch" data-color="#ff95e8"
                                        {{ $team->color == '#ff95e8' ? 'selected' : '' }} style="background: #ff95e8;">
                                    </div>
                                    <div class="color-swatch" data-color="#4f46e5"
                                        {{ $team->color == '#4f46e5' ? 'selected' : '' }} style="background: #4f46e5;">
                                    </div>
                                    <div class="color-swatch" data-color="#eb2525"
                                        {{ $team->color == '#eb2525' ? 'selected' : '' }} style="background: #eb2525;">
                                    </div>
                                    <div class="color-swatch" data-color="#96c702"
                                        {{ $team->color == '#96c702' ? 'selected' : '' }} style="background: #96c702;">
                                    </div>
                                    <div class="color-swatch" data-color="#fffc55"
                                        {{ $team->color == '#fffc55' ? 'selected' : '' }} style="background: #fffc55;">
                                    </div>
                                    <div class="color-swatch" data-color="#10b981"
                                        {{ $team->color == '#10b981' ? 'selected' : '' }} style="background: #10b981;">
                                    </div>

                                    <div class="custom-color-wrapper">
                                        <label>
                                            Custom color
                                            <input type="color" value="{{ $team->color }}" id="customColor"
                                                style="vertical-align: middle;" />
                                        </label>
                                    </div>

                                    <input type="text" name="team_color" id="colorHex" value="{{ $team->color }}"
                                        style="width: 80px;" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Add more Members</label><br>
                                <select name="members[]" id="user-select" multiple>
                                    @foreach ($staff as $key => $st)
                                        <option value="{{ $st->id }}"
                                            data-image="{{ asset('storage/app/public/vendor') . '/' . $st->image }}">
                                            {{ $st->f_name . ' ' . $st->l_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                @endif
                <div class="{{ Route::currentRouteName() == 'vendor.staff.team.edit' ? 'col-md-6' : 'col-md-12' }}">
                    <h3>Team Members</h3>

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">sl</th>
                                <th scope="col">Name</th>
                                <th scope="col">Joined on</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($team_members as $key => $emp)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        @if ($emp->employee?->image)
                                            <img style="width:40px; height:40px;"
                                                src="{{ asset('storage/app/public/vendor') . '/' . $emp->employee?->image }}"
                                                alt="">
                                        @endif
                                        @if ($emp->is_team_lead)
                                            <b>{{ $emp->employee?->f_name . ' ' . $emp->employee?->l_name }}</b> 👑
                                        @else
                                            {{ $emp->employee?->f_name . ' ' . $emp->employee?->l_name }}
                                        @endif

                                    </td>
                                    <td>
                                        {{ $emp->joined_at }}
                                    </td>
                                    <td>
                                        @if ($emp->is_team_lead)
                                            Team Lead
                                        @elseif(hasPermission('staff_team', 'edit'))
                                            <a class="text-danger form-alert" href="javascript:"
                                                data-id="employee-{{ $emp['id'] }}"
                                                data-message="{{ translate('messages.Want_to_remove_this_member_from_team') }}"
                                                title="{{ translate('messages.remove_from_team') }}">Remove
                                            </a>
                                            <form action="{{ route('vendor.staff.team.member.delete', [$emp['id']]) }}"
                                                method="get" id="employee-{{ $emp['id'] }}">
                                                @csrf @method('get')
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection


@push('script_2')
    <script>
        const swatches = document.querySelectorAll('.color-swatch');
        const customColor = document.getElementById('customColor');
        const colorHex = document.getElementById('colorHex');

        swatches.forEach(swatch => {
            swatch.addEventListener('click', () => {
                swatches.forEach(s => s.classList.remove('selected'));
                swatch.classList.add('selected');
                const color = swatch.dataset.color;
                colorHex.value = color;
                customColor.value = color;
            });
        });

        customColor.addEventListener('input', () => {
            swatches.forEach(s => s.classList.remove('selected'));
            colorHex.value = customColor.value;
        });

        colorHex.addEventListener('input', () => {
            swatches.forEach(s => s.classList.remove('selected'));
            customColor.value = colorHex.value;
        });
    </script>
    <script>
        function formatOption(option) {
            if (!option.id) return option.text;

            const image = $(option.element).data('image');
            if (!image) return option.text;

            return $(`
      <span>
        <img src="${image}" style="width: 24px; height: 24px; border-radius: 5px; margin-right: 8px;" />
        ${option.text}
      </span>
    `);
        }

        $(document).ready(function() {
            $('#user-select').select2({
                templateResult: formatOption,
                templateSelection: formatOption,
                placeholder: "Select staff",
                multiple: true,
                allowClear: true
            });
        });
        $(document).ready(function() {
            $('#team_leader').select2({
                templateResult: formatOption,
                templateSelection: formatOption,
                placeholder: "Select team leader",
                allowClear: true
            });
        });
    </script>
@endpush
