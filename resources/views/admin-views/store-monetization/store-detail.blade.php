@extends('layouts.admin.app')

@section('title', 'Store Analytics - ' . $store->name)

@push('css_or_js')
    <link href="{{ asset('public/assets/admin/css/date_range.css') }}" rel="stylesheet">

<style>
    .store-detail-header {
        background: #dedeff;
        border-radius: 10px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .store-detail-header h2 {
        margin-bottom: 4px;
    }
    .store-detail-header p {
        margin: 0;
    }
    .overview-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
        text-align: center; 
    }
    .overview-card h4 {
        font-size: 22px; 
        font-weight: 700;
        color: #334257;
    }
    .overview-card p {
        color: #737883;
        font-size: 13px;
        margin: 0;
    }
    .nav-tabs .nav-link {
        font-weight: 500;
        padding: 10px 16px;
        font-size: 13px;
    }
    .nav-tabs .nav-link.active {
        color: #006161;
        border-color: #006161;
        border-bottom: 2px solid #006161;
    }
    .module-count-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }
    .module-count-card h5 {
        font-size: 20px;
        font-weight: 700;
        color: #334257;
        margin-bottom: 2px;
    }
    .module-count-card small {
        color: #737883;
        font-size: 12px;
    }
    .enabled-module-badge {
        display: inline-block;
        padding: 4px 12px;
        margin: 3px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Back Button -->
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.store-monetization.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward mr-1"></i> Back to Dashboard
        </a>
         <!-- Date Range Filter -->
    <form method="GET" action="{{ route('admin.store-monetization.store-detail', $store->id) }}" class="date-range-form mb-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <button style="width:fit-content; white-space:nowrap" class="btn btn-outline-warning"
            type="button" data-toggle="modal" data-target="#dateRangeModal">{{ translate($preset) }}</button>
        @include('vendor-views/form_modals/date_range')
    </form>

    </div>

    <!-- Store Header -->
    <div class="store-detail-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>{{ $store->name }}</h2>
                <p>{{ $store->phone }} | {{ $store->email ?? 'No email' }}</p>
                <p>{{ $store->address ?? '' }}</p>
            </div>
            <div class="col-md-4 text-md-right">
                <span class="badge badge-{{ $store->status ? 'success' : 'danger' }} py-1 px-3" style="font-size: 14px;">
                    {{ $store->status ? 'Active' : 'Inactive' }}
                </span>
                @if($subscriptions && $subscriptions->count() > 0)
                <div class="mt-2">
                    <small>Subscription Modules: <strong>{{ count($subscriptions) }}</strong></small><br>
                </div>
                @endif
            </div>
        </div>
    </div>

   
    <!-- Core Stats -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card">
                <h4>{{ number_format($total_pos) }}</h4>
                <p>POS Tokens</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card">
                <h4>{{ \App\CentralLogics\Helpers::format_currency($total_pos_revenue) }}</h4>
                <p>POS Revenue</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card">
                <h4>{{ number_format($total_leads) }}</h4>
                <p>Leads ({{ $completed_leads }} completed)</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card">
                <h4>{{ number_format($staff_count) }}</h4>
                <p>Staff Members</p>
            </div>
        </div>
    </div>
<style>
.card_red { background-color: #ffebee; }
.card_blue { background-color: #e3f2fd; }
.card_green { background-color: #e8f5e9; }
.card_yellow { background-color: #fffde7; }
.card_purple { background-color: #f3e5f5; }
.card_pink { background-color: #fce4ec; }
.card_orange { background-color: #fff3e0; }

</style>
    <!-- Module Counts -->
    <div class="row g-2 mb-3">
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_red">
                <h5>{{ number_format($customer_count) }} </h5>
                <small>Customers</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_blue">
                <h5>{{ number_format($vendor_count) }} </h5>
                <small>Vendors</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_green">
                <h5>{{ number_format($project_count) }}</h5>
                <small>Projects</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_yellow">
                <h5>{{ number_format($task_count) }}</h5>
                <small>Tasks</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_purple">
                <h5>{{ number_format($quotation_count) }}</h5>
                <small>Quotations</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_pink">
                <h5>{{ number_format($inventory_count) }}</h5>
                <small>Inventory Items</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_orange">
                <h5>{{ number_format($service_count) }}</h5>
                <small>Mychitti Services</small>
            </div>
        </div>
      
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_red">
                <h5>{{ number_format($template_count) }}</h5>
                <small>Purchased Templates</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_blue">
                <h5>{{ number_format($ads_count) }}</h5>
                <small>Ads Posted</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_green">
                <h5>{{ number_format($store_voucher_count) }}</h5>
                <small>Store Vouchers</small> <br>
                ({{ \App\CentralLogics\Helpers::format_currency($store_voucher_amount ?? 0) }})
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="module-count-card card_yellow">
                <h5>{{ number_format($bills_count) }}</h5>
                <small>Bills</small> <br>
                ({{ \App\CentralLogics\Helpers::format_currency($bills_amount ?? 0) }})
            </div>
        </div>
    </div>

    <!-- Wallet Info -->
    @if($wallet)
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card" style="border-left: 4px solid #006161;">
                <h4>{{ \App\CentralLogics\Helpers::format_currency($wallet->total_earning ?? 0) }}</h4>
                <p>Wallet Recharge</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card" style="border-left: 4px solid #2e7d32;">
                <h4>{{ \App\CentralLogics\Helpers::format_currency($wallet->balance ?? 0) }}</h4>
                <p>Wallet Balance</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="overview-card" style="border-left: 4px solid #e65100;">
                <h4>{{ \App\CentralLogics\Helpers::format_currency($wallet->total_withdrawn ?? 0) }}</h4>
                <p>Total Spent</p>
            </div>
        </div>
     
    </div>
    @endif

    <!-- Enabled Modules -->
    @if($subscriptions && $subscriptions->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Subscriptions</h5>
        </div>
        <div class="card-body">
            @foreach($subscriptions as $subscription)
                <span class="subscription-badge {{ $subscription->plan->is_active ? 'bg-success text-white' : 'bg-light text-muted' }}">
                    {{ $subscription->plan->name }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3 flex-nowrap" style="overflow-x: auto;">
      
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'pos' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'pos', 'date_range' => $preset]) }}">
                POS
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'leads' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'leads', 'date_range' => $preset]) }}">
                Leads
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'hr' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'hr', 'date_range' => $preset]) }}">
                HR Management
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'clients' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'clients', 'date_range' => $preset]) }}">
                Clients
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'projects' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'projects', 'date_range' => $preset]) }}">
                Projects
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'tasks' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'tasks', 'date_range' => $preset]) }}">
                Tasks
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'inventory' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'inventory', 'date_range' => $preset]) }}">
                Inventory 
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab == 'services' ? 'active' : '' }}"
                href="{{ route('admin.store-monetization.store-detail', [$store->id, 'tab' => 'services', 'date_range' => $preset]) }}">
                Services
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
    

        @if($tab == 'leads')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Leads History</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Service Request</th>
                            <th>Status</th>
                            <th>Assigned At</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $key => $lead)
                        <tr>
                            <td>{{ $leads->firstItem() + $key }}</td>
                            <td>{{ $lead->serviceRequest->id ?? 'N/A' }}</td>
                            <td>
                                @if($lead->completed_at)
                                <span class="badge badge-success">Completed</span>
                                @else
                                <span class="badge badge-warning">In Progress</span>
                                @endif
                            </td>
                            <td>{{ $lead->created_at ? \Carbon\Carbon::parse($lead->created_at)->format('d M Y H:i') : '-' }}</td>
                            <td>{{ $lead->completed_at ? \Carbon\Carbon::parse($lead->completed_at)->format('d M Y H:i') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4">No leads found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leads->hasPages())
            <div class="card-footer">
                {{ $leads->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif

        @if($tab == 'pos')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">POS Tokens</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Token ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pos_tokens as $key => $token)
                        <tr>
                            <td>{{ $pos_tokens->firstItem() + $key }}</td>
                            <td>#{{ $token->id }}</td>
                            <td>{{ $token->client->name ?? 'Walk-in' }}</td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($token->total ?? 0) }}</td>
                            <td>
                                <span class="badge badge-{{ $token->payment_status == 'paid' ? 'success' : ($token->payment_status == 'unpaid' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($token->payment_status ?? 'N/A') }}
                                </span>
                            </td>
                            <td>{{ $token->created_at ? \Carbon\Carbon::parse($token->created_at)->format('d M Y H:i') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4">No POS tokens found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pos_tokens->hasPages())
            <div class="card-footer">
                {{ $pos_tokens->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif

        @if($tab == 'hr')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Staff / Employees</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $key => $emp)
                        <tr>
                            <td>{{ $employees->firstItem() + $key }}</td>
                            <td>{{ $emp->f_name }} {{ $emp->l_name }}</td>
                            <td>{{ $emp->phone ?? '-' }}</td>
                            <td>{{ $emp->employee_role_id ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $emp->status ? 'success' : 'danger' }}">
                                    {{ $emp->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $emp->created_at ? \Carbon\Carbon::parse($emp->created_at)->format('d M Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4">No employees found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($employees->hasPages())
            <div class="card-footer">
                {{ $employees->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif

        @if($tab == 'clients')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Client Management</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $key => $client)
                        <tr>
                            <td>{{ $clients->firstItem() + $key }}</td>
                            <td>{{ $client->f_name }}</td>
                            <td>{{ $client->phone ?? '-' }}</td>
                            <td>{{ $client->email ?? '-' }}</td>
                            <td>{{ $client->created_at ? \Carbon\Carbon::parse($client->created_at)->format('d M Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4">No clients found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($clients->hasPages())
            <div class="card-footer">
                {{ $clients->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif

        @if($tab == 'projects')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Project Management</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Cost</th>
                            <th>Start</th>
                            <th>End</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $key => $project)
                        <tr>
                            <td>{{ $projects->firstItem() + $key }}</td>
                            <td>{{ Str::limit($project->project_title, 30) }}</td>
                            <td>{{ $project->client->f_name ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $project->status == 1 ? 'success' : 'secondary' }}">
                                    {{ $project->progress_status ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $project->prog_percent ?? 0 }}%</td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($project->cost ?? 0) }}</td>
                            <td>{{ $project->start_date ?? '-' }}</td>
                            <td>{{ $project->end_date ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">No projects found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($projects->hasPages())
            <div class="card-footer">
                {{ $projects->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif
        @if($tab == 'tasks')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Task Management</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Cost</th>
                            <th>duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $key => $project) 
                        <tr> 
                            <td>{{ $tasks->firstItem() + $key }}</td>
                            <td>{{ Str::limit($project->title, 30) }}</td>
                            <td>{{ $project->user->f_name ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $project->status == 1 ? 'success' : 'secondary' }}">
                                    {{ $project->status ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $project->percent ?? 0 }}%</td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($project->task_amount ?? 0) }}</td>
                            <td>{{ $project->time_count . ' ' . $project->time_unit ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4">No projects found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tasks->hasPages())
            <div class="card-footer">
                {{ $tasks->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif

        @if($tab == 'inventory')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Inventory Management</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th>MRP</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventory_items as $key => $item)
                        <tr>
                            <td>{{ $inventory_items->firstItem() + $key }}</td>
                            <td>{{ Str::limit($item->item_name, 30) }}</td>
                            <td>{{ $item->sku_id ?? '-' }}</td>
                            <td>{{ $item->brand ?? '-' }}</td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($item->mrp ?? 0) }}</td>
                            <td>{{ $item->unit ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4">No inventory items found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($inventory_items->hasPages())
            <div class="card-footer">
                {{ $inventory_items->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif

        @if($tab == 'services')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">MyChitti Services Offered</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Service Name</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $key => $service)
                        <tr>
                            <td>{{ $services->firstItem() + $key }}</td>
                            <td>{{ Str::limit($service->name, 35) }}</td>
                            <td>
                                <span class="badge badge-{{ $service->status ? 'success' : 'danger' }}">
                                    {{ $service->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $service->created_at ? \Carbon\Carbon::parse($service->created_at)->format('d M Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4">No services found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($services->hasPages())
            <div class="card-footer">
                {{ $services->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection

@push('script_2')
    @include('vendor-views/js/date_range')
@endpush
