@extends('layouts.admin.app')

@section('title', translate('Client Details'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center"> 
            <div class="col-6">
                <h1 class="page-header-title text-break mb-2">{{translate('client_details')}}</h1>
            </div>
            <div class="col-6">
                <a href="{{route('admin.client.list')}}" class="btn btn-primary float-right"><i class="tio-back"></i> {{translate('clients_list')}}</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-4 border rounded">
                        <img class="avatar avatar-xl avatar-circle avatar-border-lg mx-auto mb-3" onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                            src="{{ asset('storage/profile/') }}/{{ $client->profile_pic ?? 'default-image.png' }}" alt="Client Image">
                        <h5 class="mb-0">{{$client->f_name ?? ''}} {{$client->l_name ?? ''}}</h5>
                        <small class="text-capitalize badge badge-soft-info">{{$client->user_type ?? ''}}</small>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-sm-6">
                            <dl class="row">
                                <dt class="col-sm-4 text-muted">Phone:</dt>
                                <dd class="col-sm-8">{{$client->phone}}</dd>
                                @if($client->email)
                                <dt class="col-sm-4 text-muted">Email:</dt>
                                <dd class="col-sm-8">{{$client->email}}</dd>
                                @endif
                                @if($client->gst)
                                <dt class="col-sm-4 text-muted">GST:</dt>
                                <dd class="col-sm-8">{{$client->gst}}</dd>
                                @endif
                                @if($client->id_number)
                                <dt class="col-sm-4 text-muted">ID Number:</dt>
                                <dd class="col-sm-8">{{$client->id_number}}</dd>
                                @endif
                            </dl>
                        </div>
                        <div class="col-sm-6">
                            <dl class="row">
                                @if($client->billing_address)
                                <dt class="col-sm-3 text-muted">Billing Address:</dt>
                                <dd class="col-sm-9">
                                    {{$client->billing_address->address1 ?? ''}}<br>
                                    {{$client->billing_address->city ?? ''}}, {{$client->billing_address->pincode ?? ''}}<br>
                                    {{_stateName($client->billing_address->state ?? '')}}
                                </dd>
                                @endif
                                @if($client->shipping_address)
                                <dt class="col-sm-3 text-muted">Shipping Address:</dt>
                                <dd class="col-sm-9">
                                    {{$client->shipping_address->address1 ?? ''}}<br>
                                    {{$client->shipping_address->city ?? ''}}, {{$client->shipping_address->pincode ?? ''}}<br>
                                    {{_stateName($client->shipping_address->state ?? '')}}
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for Services, Projects, Tasks, Invoices -->
    <div class="row mt-3">
        <div class="col-12">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#services" data-toggle="tab">{{translate('Services')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#projects" data-toggle="tab">{{translate('Projects')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#tasks" data-toggle="tab">{{translate('Tasks')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#invoices" data-toggle="tab">{{translate('Invoices')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#comments" data-toggle="tab">{{translate('Comments')}}</a>
                </li>
            </ul>

            <div class="tab-content mt-3">
                <div class="tab-pane fade show active" id="services">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($services as $service)
                                    <tr>
                                        <td>{{ $service->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $service->item_name }}</td>
                                        <td>{{ ucfirst($service->current_status ?? 'pending') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">No services found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="projects">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $project->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $project->project_title }}</td>
                                        <td>{{ $project->prog_percent }}%</td>
                                        <td>{{ ucfirst($project->progress_status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">No projects found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tasks">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Assignee</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                    <tr>
                                        <td>{{ $task->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $task->title }}</td>
                                        <td>{{ $task->employee->f_name ?? 'N/A' }}</td>
                                        <td>{{ $task->progress }}%</td>
                                        <td>{{ ucfirst($task->payment_status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center">No tasks found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="invoices">
                    <div class="row mb-2">
                        <div class="col-12">
                            <h6>Totals ({{ $preset }}):</h6>
                            <div class="row">
                                <div class="col-md-3"><strong>Total Invoices:</strong> {{ $invoices->count() }}</div>
                                <div class="col-md-3"><strong>Paid: </strong> {{ _price($data['paidAmount']) }}</div>
                                <div class="col-md-3"><strong>Unpaid: </strong> {{ _price($data['unpaidAmount']) }}</div>
                                <div class="col-md-3"><strong>Total: </strong> {{ _price($data['totalAmount']) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice ID</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Tax</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $invoice->invoice_id }}</td>
                                        <td>{{ ucfirst($invoice->inv_type ?? 'manual') }}</td>
                                        <td>{{ _price($invoice->total_amount) }}</td>
                                        <td>{{ _price($invoice->final_tax ?? 0) }}</td>
                                        <td><span class="badge badge-{{ $invoice->payment_status == 'Paid' ? 'success' : 'danger' }}">{{ $invoice->payment_status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center">No invoices found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="comments">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Comment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($client->comments as $comment)
                                    <tr>
                                        <td>{{ $comment->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $comment->comment }}</td>
                                        <td>Edit/Delete</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">No comments</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

