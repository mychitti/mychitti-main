@extends('layouts.admin.app')
@section('title', 'Lead Subscriptions')

@push('css_or_js')
<style>
.plan-card { cursor: default; transition: box-shadow .15s; }
.plan-card.selected-plan { border-color: #377dff !important; box-shadow: 0 0 0 2px rgba(55,125,255,.25); }
.grant-plan-preview { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px 16px; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-star-outlined"></i> Lead Subscriptions</h1>
    </div>

    <div class="row">
        {{-- Create Plan --}}
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Create Plan</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.service.lead-subscriptions.plan.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Plan Name</label>
                            <input type="text" name="name" class="form-control" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" class="form-control" required>
                                <option value="shared">Shared</option>
                                <option value="dedicated">Dedicated</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Duration (days)</label>
                            <input type="number" name="duration_days" class="form-control" min="1" value="30" required>
                        </div>
                        <div class="form-group">
                            <label>Zone <small class="text-muted">(optional)</small></label>
                            <select name="zone_id" class="form-control">
                                <option value="">All Zones</option>
                                @foreach ($zones as $z)
                                    <option value="{{ $z->id }}">{{ $z->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category <small class="text-muted">(optional)</small></label>
                            <select name="category_id" class="form-control">
                                <option value="">All Categories</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn--primary btn-block">Create Plan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            {{-- Plans as Cards --}}
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0">Plans</h5></div>
                <div class="card-body">
                    @if ($plans->isEmpty())
                        <p class="text-muted mb-0">No plans yet.</p>
                    @else
                        <div class="row">
                            @foreach ($plans as $plan)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border plan-card" id="plan-card-{{ $plan->id }}">
                                    <div class="card-body pb-2">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="badge badge-soft-{{ $plan->type === 'dedicated' ? 'primary' : 'success' }}">{{ ucfirst($plan->type) }}</span>
                                            @if ($plan->status)
                                                <span class="badge badge-soft-success">Active</span>
                                            @else
                                                <span class="badge badge-soft-secondary">Inactive</span>
                                            @endif
                                        </div>
                                        <h6 class="mb-1 mt-2">{{ $plan->name }}</h6>
                                        <p class="mb-0 text-muted small">{{ $plan->duration_days }} days</p>
                                        <h5 class="text-dark mb-3 mt-1">{{ _price($plan->price) }}</h5>
                                        <div class="d-flex" style="gap:6px">
                                            <button type="button" class="btn btn-xs btn--primary flex-fill"
                                                onclick="selectPlanForGrant({{ json_encode(['id'=>$plan->id,'name'=>$plan->name,'type'=>$plan->type,'price'=>$plan->price,'duration_days'=>$plan->duration_days]) }})">
                                                Grant to Vendor
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary"
                                                data-toggle="modal" data-target="#editPlan{{ $plan->id }}">
                                                <i class="tio-edit"></i>
                                            </button>
                                            <a href="{{ route('admin.service.lead-subscriptions.plan.destroy', $plan->id) }}"
                                               class="btn btn-xs btn-outline-danger"
                                               onclick="return confirm('Delete this plan?')">
                                                <i class="tio-delete"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Edit Modal --}}
                            <div class="modal fade" id="editPlan{{ $plan->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Plan</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form action="{{ route('admin.service.lead-subscriptions.plan.update', $plan->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Plan Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $plan->name }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Price</label>
                                                    <input type="number" name="price" class="form-control" value="{{ $plan->price }}" min="0" step="0.01" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Duration (days)</label>
                                                    <input type="number" name="duration_days" class="form-control" value="{{ $plan->duration_days }}" min="1" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="1" {{ $plan->status ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ !$plan->status ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn--primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Grant Subscription --}}
            <div class="card mb-4" id="grantSection">
                <div class="card-header"><h5 class="card-title mb-0">Grant Subscription to Vendor</h5></div>
                <div class="card-body">
                    {{-- Selected plan preview --}}
                    <div id="noPlanSelected" class="text-muted mb-3 small">
                        <i class="tio-info-outined"></i> Click <strong>Grant to Vendor</strong> on a plan card above to start.
                    </div>
                    <div id="selectedPlanPreview" class="grant-plan-preview mb-3" style="display:none">
                        <div class="d-flex align-items-center" style="gap:10px">
                            <span id="previewBadge" class="badge"></span>
                            <strong id="previewName"></strong>
                            <span class="text-muted small" id="previewDays"></span>
                            <span class="ml-auto font-weight-bold" id="previewPrice"></span>
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="clearPlanSelection()">Change</button>
                        </div>
                    </div>

                    <form action="{{ route('admin.service.lead-subscriptions.grant') }}" method="POST" id="grantForm">
                        @csrf
                        <input type="hidden" name="plan_id" id="grantPlanId">
                        <input type="hidden" name="type" id="grantType">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Store</label>
                                    <select name="store_id" class="form-control" required>
                                        <option value="">Select store…</option>
                                        @foreach ($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Zone <small class="text-muted">(optional)</small></label>
                                    <select name="zone_id" class="form-control">
                                        <option value="">All Zones</option>
                                        @foreach ($zones as $z)
                                            <option value="{{ $z->id }}">{{ $z->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Starts At</label>
                                    <input type="date" name="starts_at" id="grantStartsAt" class="form-control"
                                        value="{{ now()->toDateString() }}" required onchange="autoExpiry()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expires At</label>
                                    <input type="date" name="expires_at" id="grantExpiresAt" class="form-control"
                                        value="{{ now()->addDays(29)->toDateString() }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Billing Section --}}
                        <div class="border-top pt-3 mt-1">
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="generateInvoiceToggle"
                                    onchange="toggleBilling(this)">
                                <label class="custom-control-label" for="generateInvoiceToggle">Generate Invoice</label>
                            </div>

                            <div id="billingSection" style="display:none">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Invoice Date</label>
                                            <input type="date" name="invoice_date" class="form-control"
                                                value="{{ now()->toDateString() }}">
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label>Invoice Number</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-light text-muted" style="font-size:13px; letter-spacing:.5px">{{ $billNum['prefix'] }}</span>
                                                </div>
                                                <input type="number" name="invoice_serial" class="form-control"
                                                    value="{{ $billNum['serial'] }}" min="1" placeholder="Serial No.">
                                            </div>
                                            <small class="text-muted">Invoice ID will be: <strong>{{ $billNum['prefix'] }}<span id="serialPreview">{{ $billNum['serial'] }}</span></strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn--primary" id="grantBtn" disabled>Grant Subscription</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- All Subscriptions --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px">
            <h5 class="card-title mb-0">All Subscriptions</h5>
            <form action="" method="GET" class="d-flex" style="gap:8px">
                <select name="store_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="shared" {{ request('type') === 'shared' ? 'selected' : '' }}>Shared</option>
                    <option value="dedicated" {{ request('type') === 'dedicated' ? 'selected' : '' }}>Dedicated</option>
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            @if ($subscriptions->isEmpty())
                <p class="p-3 text-muted mb-0">No subscriptions found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Store</th>
                                <th>Type</th>
                                <th>Plan</th>
                                <th>Starts</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptions as $sub)
                            <tr>
                                <td>{{ $sub->id }}</td>
                                <td>{{ $sub->store->name ?? '—' }}</td>
                                <td><span class="badge badge-soft-{{ $sub->type === 'dedicated' ? 'primary' : 'success' }}">{{ ucfirst($sub->type) }}</span></td>
                                <td>{{ $sub->plan->name ?? 'Manual Grant' }}</td>
                                <td>{{ \Carbon\Carbon::parse($sub->starts_at)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($sub->expires_at)->format('d M Y') }}</td>
                                <td>
                                    @if ($sub->expires_at >= now()->toDateString())
                                        <span class="badge badge-soft-success">Active</span>
                                    @else
                                        <span class="badge badge-soft-danger">Expired</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.service.lead-subscriptions.revoke', $sub->id) }}"
                                       class="btn btn-xs btn-outline-danger"
                                       onclick="return confirm('Revoke this subscription?')">
                                        Revoke
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $subscriptions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('script')
<script>
var selectedPlan = null;

function selectPlanForGrant(plan) {
    selectedPlan = plan;

    // Highlight selected card
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected-plan'));
    document.getElementById('plan-card-' + plan.id).classList.add('selected-plan');

    // Fill hidden fields
    document.getElementById('grantPlanId').value = plan.id;
    document.getElementById('grantType').value = plan.type;

    // Show preview
    document.getElementById('noPlanSelected').style.display = 'none';
    var preview = document.getElementById('selectedPlanPreview');
    preview.style.display = 'block';

    var badge = document.getElementById('previewBadge');
    badge.textContent = plan.type.charAt(0).toUpperCase() + plan.type.slice(1);
    badge.className = 'badge badge-soft-' + (plan.type === 'dedicated' ? 'primary' : 'success');

    document.getElementById('previewName').textContent = plan.name;
    document.getElementById('previewDays').textContent = plan.duration_days + ' days';
    document.getElementById('previewPrice').textContent = plan.price;

    // Auto-calculate expiry
    autoExpiry();

    // Enable submit
    document.getElementById('grantBtn').disabled = false;

    // Scroll to grant section
    document.getElementById('grantSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function clearPlanSelection() {
    selectedPlan = null;
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected-plan'));
    document.getElementById('grantPlanId').value = '';
    document.getElementById('grantType').value = '';
    document.getElementById('noPlanSelected').style.display = '';
    document.getElementById('selectedPlanPreview').style.display = 'none';
    document.getElementById('grantBtn').disabled = true;
}

function autoExpiry() {
    if (!selectedPlan) return;
    var starts = document.getElementById('grantStartsAt').value;
    if (!starts) return;
    var d = new Date(starts);
    d.setDate(d.getDate() + selectedPlan.duration_days - 1);
    document.getElementById('grantExpiresAt').value = d.toISOString().slice(0, 10);
}

function toggleBilling(el) {
    document.getElementById('billingSection').style.display = el.checked ? 'block' : 'none';
}

// Live serial preview
document.addEventListener('DOMContentLoaded', function () {
    var serialInput = document.querySelector('input[name="invoice_serial"]');
    if (serialInput) {
        serialInput.addEventListener('input', function () {
            document.getElementById('serialPreview').textContent = this.value || '';
        });
    }
});
</script>
@endpush
@endsection
