<div class="account-item" style="margin-left: {{ $level * 20 }}px">
    <div class="account-info">
        <div class="d-flex gap-2">
            <div class="account-name">{{ $account->name }}</div>
            @if ($account->acc_type == 'debit')
                <span class="badge badge-soft-warning">Debit</span>
            @else
                <span class="badge badge-soft-success">Credit</span>
                @endif @if ($account->account_type == 'cost_center')
                    <span class="badge badge-soft-success">Cost Center</span>
                @endif
        </div>
        <p>{{ $account->description }}</p>
        <div class="account-code">Folder Id: {{ $account->code }}</div>
    </div>
    <div class="account-actions">

        <span style="width: fit-content;padding: 0 10px !important;"
            class="btn action-btn btn-outline-success add_acc_btn" type="button" data-toggle="modal"
            data-target="#subAccAddModal" data-id="{{ $account->id }}" data-name="{{ $account->name }}"
            data-level="{{ $account->level }}">+ Add </span>

        <div class="dropdown">
            {{-- <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                <img style="width: 24px; filter: contrast(0)" src="{{ asset('storage/app/public/util/10025520.png') }}"
                    alt="action" />
            </button> --}}
            <div class="dropdown-menu">

                <a type="button" data-toggle="modal" data-target="#accEditModal" data-id="{{ $account->id }}"
                    data-desc="{{ $account->description }}" data-name="{{ $account->name }}"
                    class="dropdown-item text-primary">
                    <i class="tio-edit"></i>
                    Edit
                </a>
                <a type="button" data-id="category-{{ $account['id'] }}"
                    data-message="{{ translate('Want to delete this account and its subaccounts') }}"
                    title="{{ translate('messages.delete_account') }}" class="dropdown-item text-danger form-alert">
                    <i class="tio-delete"></i>
                    Delete
                </a>
                <form action="{{ route('vendor.account.setting.chart-of-account.account-delete', [$account['id']]) }}"
                    method="get" id="category-{{ $account['id'] }}">
                    @csrf @method('get')
                </form>
            </div>
        </div>

    </div>
</div>

@if ($account->children && $account->children->count())
    @foreach ($account->children as $child)
        @include('vendor-views.account.setting.partials._account_item', [
            'account' => $child,
            'level' => $level + 1,
        ])
    @endforeach
@endif
