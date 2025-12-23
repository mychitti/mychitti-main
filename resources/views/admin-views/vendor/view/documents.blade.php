@extends('layouts.admin.app')

@section('title', $store->name)

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin/css/croppie.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/2.7.2/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-thumbnail.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery/css/lg-video.css">
    <!-- LightGallery JS -->
    <style>
        .revw_thumbnail {
            width: 41px !important;
            border: 1px solid #dedede;
            height: 38px !important;
            margin: 4px;
            border-radius: 5px;
            cursor: zoom-in;
        }

        .vdp-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .vdp-status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .vdp-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .vdp-status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">

        @include('admin-views.vendor.view.partials._header', ['store' => $store])

        <!-- Page Heading -->

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title m-0 d-flex align-items-center">
                    <span class="card-header-icon mr-2">
                        <i class="tio-shop-outlined"></i>
                    </span>
                    <span class="ml-1">{{ translate('messages.documents') }}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 ">
                    <div class="col-12">
                        <div class="resturant--info-address">

                            <div class="table-responsive datatable-custom">
                                <table id="columnSearchDatatable"
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                    data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true,
                                 "paging":false
                               }'>
                                    <thead class="thead-light">
                                        <tr class="text-center">
                                            <th class="border-0">{{ translate('sl') }}</th>
                                            <th class="border-0">{{ translate('messages.document_name') }}</th>
                                            <th class="border-0">{{ translate('messages.file') }}</th>
                                            <th class="border-0">{{ translate('messages.type') }}</th>
                                            <th class="border-0">{{ translate('messages.uploaded on') }}</th>
                                            <th class="border-0">{{ translate('messages.status') }}</th>
                                            <th class="border-0">{{ translate('messages.version') }}</th>
                                            <th class="border-0">{{ translate('messages.action') }}</th>
                                        </tr>

                                    </thead>

                                    <tbody id="set-rows" class="text-center">
                                        @if (!count($store_documents->where('doc_type', 'id_doc')))
                                            @if ($store->id_doc)
                                                <tr>
                                                    <td>1</td>
                                                    <td><b>ID Proof</b></td>
                                                    <td>
                                                        <a href="{{ asset('storage/app/public/store/docs/' . $store->id_doc) }}"
                                                            target="_blank">
                                                            {{ $store->id_doc }}
                                                        </a>
                                                    </td>
                                                    <td>

                                                        {{ _getFileTypeLabel($store->id_doc) }}

                                                    </td>
                                                    <td>
                                                        {{ $store->created_at }}
                                                    </td>
                                                    <td>
                                                        <span class="vdp-status-badge vdp-status-approved">Verified</span>
                                                    </td>
                                                    <td>
                                                        <span class="vdp-status-badge vdp-status-approved">Current</span>
                                                        <span class="vdp-status-badge vdp-status-approved">Initial</span>

                                                    </td>
                                                    <td>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endif
                                        @if (!count($store_documents->where('doc_type', 'gst_doc')))

                                            @if ($store->gst_doc)
                                                <tr>
                                                    <td>2</td>
                                                    <td><b>GST Document</b></td>
                                                    <td>
                                                        <a href="{{ asset('storage/app/public/store/docs/' . $store->gst_doc) }}"
                                                            target="_blank">
                                                            {{ $store->gst_doc }}
                                                        </a>
                                                    </td>
                                                    <td>

                                                        {{ _getFileTypeLabel($store->gst_doc) }}

                                                    </td>
                                                    <td>
                                                        {{ $store->created_at }}
                                                    </td>
                                                    <td>
                                                        <span class="vdp-status-badge vdp-status-approved">Verified</span>
                                                    </td>
                                                    <td>
                                                        <span class="vdp-status-badge vdp-status-approved">Initial</span>

                                                    </td>
                                                    <td>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endif
                                        @foreach ($store_documents as $key => $doc)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td><b>{{ $doc->doc_type == 'gst_doc' ? 'GST Document' : 'ID Proof Document' }}</b>
                                                </td>
                                                <td>
                                                   @if($doc->back_side) <b>Front: </b> @endif<a href="{{ asset('storage/app/public/store/docs/' . $doc->file_path) }}"
                                                        target="_blank">
                                                        {{ $doc->file_path }}
                                                    </a>
                                                    @if ($doc->back_side)
                                                   <br>   <b>Back: </b>  <a href="{{ asset('storage/app/public/store/docs/' . $doc->back_side) }}"
                                                            target="_blank">
                                                            {{ $doc->file_path }}
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>

                                                    {{ _getFileTypeLabel($doc->file_path) }}

                                                </td>
                                                <td>
                                                    {{ $doc->created_at }}
                                                </td>
                                                <td>
                                                    @if ($doc->verified == 1)
                                                        <span class="vdp-status-badge vdp-status-approved">Verified</span>
                                                        @if ($doc->verified_by && $doc->verified_at)
                                                            <i class="tio-info-outined" data-toggle="tooltip"
                                                                data-placement="right" data-html="true"
                                                                title="by {{ $doc->verified_by ? \App\Models\Admin::find($doc->verified_by)->f_name . ' ' . \App\Models\Admin::find($doc->verified_by)->l_name : '' }} <br> at {{ $doc->verified_at }}">
                                                            </i>
                                                        @endif
                                                    @else
                                                        <span class="vdp-status-badge vdp-status-pending">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($doc->status == 1)
                                                        <span class="vdp-status-badge vdp-status-approved">Current</span>
                                                    @endif
                                                    @if ($doc->version_type == 'Initial')
                                                        <span class="vdp-status-badge vdp-status-approved">Initial</span>
                                                    @else
                                                        <span class="vdp-status-badge vdp-status-pending">Updated</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn--container justify-content-center">
                                                        @if ($doc->verified == 0 && $doc->status == 1)
                                                            <a style="width:fit-content; padding: 0 8px !important;"
                                                                class="btn action-btn  btn-outline-success form-alert"
                                                                href="javascript:" data-id="verify-{{ $doc['id'] }}"
                                                                data-message="{{ translate('Want to verify this document?') }}"
                                                                title="{{ translate('messages.verify') }}"><i
                                                                    class="tio-checkmark-circle-outlined"></i> Verify
                                                            </a>
                                                            <form
                                                                action="{{ route('admin.store.verify-doc', [$doc['id']]) }}"
                                                                method="get" id="verify-{{ $doc['id'] }}">
                                                                @csrf
                                                            </form>
                                                        @endif
                                                        {{-- @if ($doc->version_type != 'Initial')
                                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                                                href="javascript:" data-id="unit-{{ $doc['id'] }}"
                                                                data-message="{{ translate('Want to delete this unit ?') }}"
                                                                title="{{ translate('messages.delete') }}"><i
                                                                    class="tio-delete-outlined"></i>
                                                            </a>
                                                            <form action="{{ route('admin.unit.destroy', [$doc['id']]) }}"
                                                                method="post" id="unit-{{ $doc['id'] }}">
                                                                @csrf @method('delete')
                                                            </form>
                                                        @endif --}}
                                                    </div>
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

    </div>

@endsection

@push('script_2')
    <!-- Page level plugins -->

    <script>
        "use strict";
        // Call the dataTables jQuery plugin
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });

        $(function() {
            $('[data-toggle="tooltip"]').tooltip({
                html: true
            });
        });


        function request_alert(url, message) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }

        $(document).ready(function() {
            $(".copy-btn").on("click", function() {
                // Get the previous <p> or span element text
                var text = $(this).prev(".textToCopy").text().trim();
                console.log(text); // Debugging

                if (navigator.clipboard && window.isSecureContext) {
                    // Modern way to copy
                    navigator.clipboard.writeText(text).then(() => {
                        console.log("Copied successfully!");
                    }).catch(err => {
                        console.error("Clipboard copy failed", err);
                    });
                } else {
                    // Fallback for older browsers
                    var tempInput = $("<textarea>"); // Use textarea instead of input
                    $("body").append(tempInput);
                    tempInput.val(text).css({
                        position: "absolute",
                        left: "-9999px", // Hide offscreen
                    }).select();
                    document.execCommand("copy");
                    tempInput.remove();
                }
                $(this).html("Copied!");
                setTimeout(() => $(this).html('<i class="tio-copy"></i>'), 1000);
            });
        });
    </script>
@endpush
