@extends('layouts.admin.app')

@section('title', translate('Keywords'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"></script>
<script>
    $(document).on('click', '.lead_approval', function() {
        console.log('fds');
        var status = $(this).attr('data-id');
        $.ajax({
            url: '{{ url('
            admin / lead / lead_approval ') }}',
            type: "POST",
            data: {
                _token: $('[name="_token"]').val(),
                lead_id: $('#lead_id').val(),
                approval: status
            },
            success: function(resp) {
                if (resp.status) {
                    if (status == 'accept') {
                        $('.approval-status').html('<h3 class="text-success p-3">Accepted</h3>')
                    } else {
                        $('.approval-status').html('<h3 class="text-danger p-3">Rejected</h3>')
                    }
                }

            },
        });
    })
</script>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-filter-list"></i> Add Keyword</h1>
        <div class="page-header-select-wrapper">

        </div>
    </div>
    <!-- End Page Header -->

    <div class="row g-2">
        <form class="w-100" action="{{ route('admin.lead.save-info') }}" method="post">
            @csrf
            <input type="hidden" id="lead_id" name="lead_id" value="">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-body row">
                        <div class="form-row col-6">
                            <label for="exampleInputEmail1">Keyword</label>
                            <input type="text" name="client_name" placeholder="Client Name" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
         
        </form>
        @endsection

        @push('script_2')
        @endpush