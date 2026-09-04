@extends('layouts.vendor.app')

@section('title', 'Chart Of Account')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            color: #718096;
            font-size: 14px;
        }

        .main-content {
            {{-- display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px; --}}
        }

        .form-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .form-panel h2 {
            color: #2d3748;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #c8d2e0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #c8d2e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .accounts-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .accounts-panel h2 {
            color: #2d3748;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .account-category {
            margin-bottom: 25px;
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background: #d6e5ff;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .category-header:hover {
            transform: translateX(5px);
        }

        .category-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .category-badge {
            background: rgba(255, 255, 255, 1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: black;
        }

        .account-list {
            padding-left: 15px;
        }

        .account-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f7fafc;
            border-left: 4px solid #2563eb;
            margin-bottom: 8px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .account-item:hover {
            background: #edf2f7;
            transform: translateX(5px);
        }

        .account-info {
            flex: 1;
        }

        .account-name {
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .account-code {
            color: #718096;
            font-size: 12px;
        }

        .account-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #4299e1;
            color: white;
        }

        .btn-delete {
            background: #f56565;
            color: white;
        }

        .btn-icon:hover {
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 4px;
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        .cost_center_info {
            border: 1px solid #00a7cd;
            border-radius: 10px;
            padding: 10px;
            background: #eaf2ff;
        }
    </style>

    <style>
        .year-card {
            cursor: pointer;
            transition: 0.2s;
            border: 1px solid #e2e2e2 !important;
            width: 168px !important;
        }

        .year-card:hover {
            transform: scale(1.05);
            border: 2px solid #007bff;
        }

        .year-card.active {
            border: 2px solid #28a745 !important;
            background: #e6ffea;
        }

        .form-row {
            margin-top: 6px;
        }

        .folder_img {
            width: 100%;
            filter: brightness(1.1);
        }


        /* Content overlay inside folder */
        .folder-content {
            top: 22px;
            left: -4px;
            right: 8px;
            bottom: 8px;
            border-radius: 3px;
            padding: 13px;
            z-index: 1;
            width: 100%;
        }

        /* Bank name styling */
        .bank-title {
            font-size: 18px;
            font-weight: bold;
            {{-- color: #dc3545; --}} text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info lines */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 12px;
            line-height: 1.3;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #000;
            font-family: monospace;
        }

        .amount-green {
            color: #27ae60;
            font-weight: bold;
        }

        .amount-red {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Upload section */
        .upload-section {
            width: fit-content;
            margin: 0 auto;
            margin-top: 45px;
            text-align: center;
            padding: 6px;
            {{-- background: rgba(52, 152, 219, 0.1);
            border: 1px dashed #3498db; --}} border-radius: 3px;
        }

        .upload-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
        }

        .upload-btn:hover {
            background: #2980b9;
        }

        /* Folder reflection effect */
        .folder-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 50%;
            bottom: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .folder-shape {
                max-width: 250px;
            }

            .folder-main {
                height: 180px;
            }

            .bank-title {
                font-size: 14px;
            }

            .info-row {
                font-size: 11px;
            }
        }
    </style>
    <style>
        .folder {
            margin: 16px;
            position: relative;
            width: 309px;
            height: fit-content;
        }

        .folder-back {
            position: absolute;
            top: -26px;
            left: 0px;
            z-index: -1;
            width: 52%;
            height: 34px;
            border-radius: 15px 15px 0 0;
        }

        .folder-back-extension {
            position: absolute;
            top: -16px;
            z-index: -1;
            left: 159px;
            width: 40px;
            height: 40px;
            border-radius: 0 28px 0 0;
        }

        .folder-cutout {
            position: absolute;
            top: -14px;
            left: 51%;
            width: 10px;
            height: 10px;
        }

        .folder-cutout::before {
            content: '';
            position: absolute;
            top: -4px;
            left: 3px;
            width: 41px;
            height: 18px;
            background: white;
            border-radius: 0 0 0 61px;
        }

        .folder-main {
            /* position: absolute; */
            top: 110px;
            left: 14px;
            width: 100%;
            min-height: 219px;
            height: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 14px rgb(0 0 0 / 12%);
            display: flex;
            align-items: center;

        }

        /* LEVEL 1 FOLDER COLOR */
        .folder-main-level-0 {
            background: linear-gradient(180deg, #a6f0ff 0%, #41d6e7 100%);
        }

        .folder-back-level-0 {
            background: linear-gradient(180deg, #47e0ffff 0%, #00c0d6ff 100%);
        }

        .folder-back-extension-level-0 {
            background: linear-gradient(180deg, #3bcdea 0%, #00c0d6ff 100%);
        }

        .folder-back {
            position: absolute;
            top: -26px;
            left: 0px;
            z-index: -1;
            width: 52%;
            height: 34px;
            border-radius: 15px 15px 0 0;
        }


        .folder-back-extension {
            position: absolute;
            top: -16px;
            z-index: -1;
            left: 159px;
            width: 40px;
            height: 40px;
            border-radius: 0 28px 0 0;
        }
    </style>
    <style>
        .dropdown-menu.show {
            top: -15px !important;
            left: -97px !important;

        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center w-100">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i>Chart Of Account</h1>
            </div>
            <div class="page-header-select-wrapper">
                <div class="d-flex gap-1">
                </div>
            </div>
        </div>

        <div class="main-content">

@if(hasPermission('assets_chart_of_accounts', 'view'))
            <div class="">
                {{-- <h2>Existing Accounts</h2> --}}
                <div id="accountsContainer" class="row my-3 ">
                    @foreach ($ledger_account_types as $acc_type)
                        <div class="folder">
                            <div class="folder-back folder-back-level-0"></div>
                            <div class="folder-back-extension folder-back-extension-level-0"></div>
                            <div class="folder-cutout"></div>
                            <div class="folder-main folder-main-level-0">
                                <div class="folder-content ">
                                    {{-- <input type="hidden" class="bank_id_inp" name="bank_id" value=""> --}}
                                    {{-- <div class="dropdown position-absolute" style="    right: 10px;top: 8px; z-index: 10;">
                                        <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                                            aria-expanded="false">
                                            <img style="width: 24px; filter: contrast(0)"
                                                src="{{ asset('storage/app/public/util/10025520.png') }}" alt="action" />
                                        </button>

                                        <div class="dropdown-menu">
                                            <a class=" dropdown-item text-warning  edit-btn edit_btn"
                                                data-target="#editAccountModal"><i class="tio-edit"></i> Edit</a>
                                        </div>
                                    </div> --}}

                                    <a href="{{ hasPermission('assets_chart_of_accounts', 'view') ?  route('vendor.account.setting.chart-of-account.detail', ['parent' => 0, 'ledger_type_id' => $acc_type->id]) : '#' }}"
                                        class="folder-content_d">
                                        <div class="bank-title">
                                            {{ $acc_type->name }}
                                        </div>

                                        <div class="text-center">
                                            <span class="info-value"> <span
                                                    class="category-badge">{{ $acc_type->account->count() }} accounts</span>
                                            </span>
                                        </div>

                                        <div class="upload-section">
                                           @if( hasPermission('assets_chart_of_accounts', 'view') ) <button class="upload-btn">
                                                <i class="tio-visible"></i> View
                                            </button>
                                            @endif
                                            @if( hasPermission('assets_chart_of_accounts', 'add') )
                                            <button class="upload-btn edit_btn" data-ledger_name="{{ $acc_type->name }}"
                                                data-id="{{ $acc_type->id }}" type="button" data-target="#accAddModal"
                                                data-toggle="modal">
                                                + Add
                                            </button>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
            @endif
        </div>
        <div class="modal fade" id="accAddModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="form-panel">
                            <h2>Add Child Account in <span class="ledger_name"></span></h2>
                            <form id="accountForm" enctype="multipart/form-data"
                                action="{{ route('vendor.account.setting.chart-of-account.account-store') }}"
                                method="post">
                                @csrf
                                <input type="hidden" id="category" name="ledger_account_type_id">


                                <div class="form-group">
                                    <label>Account Name</label>
                                    <input type="text" name="name" placeholder="Name"placeholder="e.g., Cash in Bank"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label>Short Description (Optional)</label>
                                    <textarea id="description" name="description" placeholder="Brief description of the account"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="accEditModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="form-panel">
                            <h2 class="edit_account_heading">Edit Account</h2>
                            <form id="accountForm" enctype="multipart/form-data"
                                action="{{ route('vendor.account.setting.chart-of-account.account-update') }}"
                                method="post">
                                @csrf
                                <input type="hidden" name="id" class="acc_id">

                                <div class="form-group">
                                    <label>Account Name</label>
                                    <input type="text" name="name" class="edit_name"
                                        placeholder="Name"placeholder="e.g., Cash in Bank" required>
                                </div>

                                <div class="form-group">
                                    <label>Description (Optional)</label>
                                    <textarea id="description" class="edit_desc" name="description" placeholder="Brief description of the account"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(".account_type_inp").on('change', function() {
            if ($(this).val() === 'cost_center') {
                $(".cost_center_info").show();
            } else {
                $(".cost_center_info").hide();
            }
        });

        document.querySelectorAll('.edit_btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // stop <a> navigation
                e.stopPropagation(); // stop bubbling

                // Open modal with Bootstrap

                $('#accAddModal').modal('show');

                var id = $(this).attr('data-id')
                var ledger_name = $(this).attr('data-ledger_name')
                $("#category").val(id)
                $(".ledger_name").text(ledger_name)
            })

        })
        $(document).on('click', ".add_acc_btn", function() {
            var name = $(this).attr('data-name')
            var id = $(this).attr('data-id')
            var level = $(this).attr('data-level')
            console.log('fd')
            if (level == 1) {
                $(".account_type").show()
            } else {
                $(".account_type").hide()
            }
            $(".parent_id").val(id)
            $(".parent_account_heading").text(name)
            $(".parent_account_show").text(name)
        })
        $(document).on('click', ".edit_acc_btn", function() {
            var name = $(this).attr('data-name')
            var id = $(this).attr('data-id')
            var desc = $(this).attr('data-desc')
            $(".acc_id").val(id)
            $(".edit_name").val(name)
            {{-- $(".edit_account_heading").text(name) --}}
            $(".edit_desc").val(desc)
        })
    </script>
@endpush
