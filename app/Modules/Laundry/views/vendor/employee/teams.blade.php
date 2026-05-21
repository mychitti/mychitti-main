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
                        Teams List
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $teams->total() }}</span>
                    </span>

                </h1>
                @if (hasPermission('staff_team', 'add'))
                    <a data-toggle="modal" data-target="#createTeamModal" class="btn btn_sm btn--primary mb-2">
                        <span class="text">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="white" width="24" height="24"
                                viewBox="0 0 24 24">
                                <path
                                    d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5zM24 4h-2V2h-2v2h-2v2h2v2h2V6h2z" />
                            </svg>
                            Create New Team</span>
                    </a>
                    <div class="modal fade" id="createTeamModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Create New Team</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('vendor.staff.team.save') }}" method="post">
                                        @csrf
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label for="exampleInputEmail1">Team Leader</label>
                                                <select name="team_leader" id="team_leader">
                                                    <option value=""></option>
                                                    @foreach ($staff as $key => $st)
                                                        <option value="{{ $st->id }}"
                                                            data-image="{{ asset('storage/app/public/vendor') . '/' . $st->image }}">
                                                            {{ $st->f_name . ' ' . $st->l_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="exampleInputEmail1">Team Name</label>
                                                <input type="text" name="team_name" placeholder="Ex: Designers"
                                                    class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Appearence</label>
                                            <div class="color-picker-wrapper">
                                                <div class="color-swatch" data-color="#0f0f23" style="background: #0f0f23;">
                                                </div>
                                                <div class="color-swatch" data-color="#ff95e8" style="background: #ff95e8;">
                                                </div>
                                                <div class="color-swatch" data-color="#4f46e5" style="background: #4f46e5;">
                                                </div>
                                                <div class="color-swatch" data-color="#eb2525" style="background: #eb2525;">
                                                </div>
                                                <div class="color-swatch" data-color="#96c702" style="background: #96c702;">
                                                </div>
                                                <div class="color-swatch" data-color="#fffc55" style="background: #fffc55;">
                                                </div>
                                                <div class="color-swatch" data-color="#10b981" style="background: #10b981;">
                                                </div>


                                                <div class="custom-color-wrapper">
                                                    <label>
                                                        Custom color
                                                        <input type="color" id="customColor"
                                                            style="vertical-align: middle;" />
                                                    </label>
                                                </div>

                                                <input type="text" name="team_color" id="colorHex" value="#F5F5F5"
                                                    style="width: 80px;" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Team Members</label><br>
                                            <select name="members[]" id="user-select" multiple>
                                                @foreach ($staff as $key => $st)
                                                    <option value="{{ $st->id }}"
                                                        data-image="{{ asset('storage/app/public/vendor') . '/' . $st->image }}">
                                                        {{ $st->f_name . ' ' . $st->l_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Create Team</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
        <!-- Page Heading -->
    @if(hasPermission('staff_team', 'list'))

        <div class="card">
            <div class="card-header py-2 justify-content-end border-0">

            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.#') }}</th>
                                <th class="border-0">{{ translate('messages.name') }}</th>
                                <th class="border-0">{{ translate('messages.team_leader') }}</th>
                                <th class="border-0">{{ translate('messages.members') }}</th>
                                <th class="border-0">{{ translate('messages.created_at') }}</th>
                                <th class="border-0 text-center">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($teams as $k => $e)
                                <tr>
                                    <td scope="row">{{ $k + $teams->firstItem() }}</td>
                                    <td scope="row">
                                        <div class="d-flex" style="align-items:center; gap:5px;">
                                            <div
                                                style="width:12px; height:12px; background-color:{{ $e->color }};border-radius:50%;">
                                            </div> {{ $e->name }}
                                    </td>
                                    <td><a
                                            href="{{ hasPermission('staff_manage', 'view') && $e->team_lead ? route('vendor.employee.view', [$e->team_lead]) : '#' }}">{{ $e->team_leader?->f_name . ' ' . $e->team_leader?->l_name }}</a>
                                    </td>
                                    <td class="text-capitalize text-break">
                                        <div class="members-container">
                                            <svg class="members-icon" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                                <circle cx="9" cy="7" r="4" />
                                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                            </svg>

                                            <span class="members-text "
                                                style="color:{{ $e->color }};">{{ count($e->members) }}
                                                Members</span>

                                            <div class="avatars-container">
                                                @foreach ($e->members as $key => $emp)
                                                    @if ($key < 6)
                                                        <div class="avatar avatar-1">
                                                            <div class="avatar-initial">
                                                                <img style="width:100%; height:100%;"
                                                                    src="{{ asset('storage/app/public/vendor') . '/' . $emp->image }}"
                                                                    alt="">
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if (count($e->members) > 6)
                                                    +{{ count($e->members) - 6 }}
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $e->created_at }}</td>
                                    <td>
                                        @if (auth('vendor_employee')->id() != $e['id'])
                                            <div class="btn--container justify-content-center">
                                                @if (hasPermission('staff_team', 'edit'))
                                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                                        href="{{ route('vendor.staff.team.edit', [$e['id']]) }}"
                                                        title="Edit team"><i class="tio-edit"></i>
                                                    </a>
                                                @endif
                                                @if (hasPermission('staff_team', 'view'))
                                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                                        href="{{ route('vendor.staff.team.view', [$e['id']]) }}"
                                                        title="View team"><i class="tio-visible"></i>
                                                    </a>
                                                @endif
                                                @if (hasPermission('staff_team', 'delete'))
                                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                        href="javascript:" data-id="employee-{{ $e['id'] }}"
                                                        data-message="{{ translate('messages.Want_to_delete_this_team') }}"
                                                        title="{{ translate('messages.delete_team') }}"><i
                                                            class="tio-delete-outlined"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            @if (hasPermission('staff_team', 'delete'))
                                                <form action="{{ route('vendor.staff.team.delete', [$e['id']]) }}"
                                                    method="get" id="employee-{{ $e['id'] }}">
                                                    @csrf @method('get')
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if (count($teams) !== 0)
                <div class="card-footer">
                    <div class="page-area">
                        <table>
                            <tfoot>
                                {{-- {!! $teams->links() !!} --}}
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
            @if (count($teams) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
        @endif
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
