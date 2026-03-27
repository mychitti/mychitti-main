<a href="{{route('admin.item.list')}}" class="col-sm-6 col-lg-2">
    <div class="__dashboard-card-2">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="dashboard/grocery">
        <h6 class="name">Services</h6>
        <h3 class="count">{{ $data['total_items'] }}</h3>
        <div class="subtxt">{{ $data['new_items'] }} {{ translate('newly added') }}</div>
    </div>
</a>
<a href="{{route('admin.service.lead-list')}}" class="col-sm-6 col-lg-2">
    <div class="__dashboard-card-2">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="dashboard/grocery">
        <h6 class="name">Service Leads</h6>
        <h3 class="count">{{ $data['service_leads'] }}</h3>
        <div class="subtxt">{{ $data['service_leads'] }} {{ translate('newly added') }}</div>
    </div>
</a>
<a href="{{route('admin.store.list')}}"  class="col-sm-6 col-lg-2">
    <div class="__dashboard-card-2"> 
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/stores.svg')}}" alt="dashboard/grocery">
        <h6 class="name">{{ translate('Service Stores') }}</h6>
        <h3 class="count">{{ $data['total_stores'] }}</h3>
        <div class="subtxt">{{ $data['new_stores'] }} {{ translate('newly added') }}</div>
    </div>
</a>
<a href="{{route('admin.users.customer.list')}}?zone_id=all" class="col-sm-6 col-lg-2">
    <div class="__dashboard-card-2">
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/customers.svg')}}" alt="dashboard/grocery">
        <h6 class="name">{{ translate('messages.customers') }}</h6>
        <h3 class="count">{{ $data['total_customers'] }}</h3>
        <div class="subtxt">{{ $data['new_customers'] }} {{ translate('newly added') }}</div>
    </div>
</a>
<a href="{{route('admin.ticket.index', ['status' => 'open'])}}" class="col-sm-6 col-lg-2">
    <div class="__dashboard-card-2" >
        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="dashboard/grocery">
        <h6 class="name">Open Tickets</h6>
        <h3 class="count">{{ $data['open_tickets'] ?? 0 }}</h3>
        <div class="subtxt">{{ translate('tickets need attention') }}</div>
    </div>
</a>
<div class="col-12 d-none">
    <div class="row g-2">
        <div class="col-sm-6 col-lg-2 d-none">
            <a class="order--card h-100" href="{{route('admin.order.list',['searching_for_deliverymen'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/unassigned.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.unassigned_orders')}}</span>
                    </h6>
                    <span class="card-title text-3F8CE8">
                        {{$data['searching_for_dm']}}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-2 d-none">
            <a class="order--card h-100" href="{{route('admin.order.list',['accepted'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/accepted.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('Accepted by Delivery Man')}}</span>
                    </h6>
                    <span class="card-title text-success">
                        {{$data['accepted_by_dm']}}
                    </span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-2 d-none">
            <a class="order--card h-100" href="{{route('admin.order.list',['processing'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/packaging.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('Packaging')}}</span>
                    </h6>
                    <span class="card-title text-FFA800">
                        {{$data['preparing_in_rs']}}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-2 d-none">
            <a class="order--card h-100" href="{{route('admin.order.list',['item_on_the_way'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/out-for.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('Out for Delivery')}}</span>
                    </h6>
                    <span class="card-title text-success">
                        {{$data['picked_up']}}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-2 d-none">
            <a class="order--card h-100" href="{{route('admin.order.list',['delivered'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/delivered.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.delivered')}}</span>
                    </h6>
                    <span class="card-title text-success">
                        {{$data['delivered']}}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-2 d-none">
            <a class="order--card h-100" href="{{route('admin.order.list',['canceled'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/order-status/canceled.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.canceled')}}</span>
                    </h6>
                    <span class="card-title text-danger">
                        {{$data['canceled']}}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-2">
            <a class="order--card h-100" href="{{route('admin.order.list',['refunded'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/order-status/refunded.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.refunded')}}</span>
                    </h6>
                    <span class="card-title text-danger">
                        {{$data['refunded']}}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-2">
            <a class="order--card h-100" href="{{route('admin.order.list',['failed'])}}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                        <img src="{{asset('/public/assets/admin/img/order-status/payment-failed.svg')}}" alt="dashboard" class="oder--card-icon">
                        <span>{{translate('messages.payment_failed')}}</span>
                    </h6>
                    <span class="card-title text-danger">
                        {{$data['refund_requested']}}
                    </span>
                </div>
            </a>
        </div>
    </div>
</div>
