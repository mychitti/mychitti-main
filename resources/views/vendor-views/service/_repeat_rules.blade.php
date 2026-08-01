{{-- Repeat purchase cycles for work billed by name.

     A service line on an invoice carries no item id — just the name it was billed under — so its
     cycle has to hang off that name. Stocked items are set on the item form instead, which is why
     this list is only about services.

     The name has to match what actually gets billed, so the picker is filled from this store's
     real invoice lines rather than left to the vendor's memory. It still accepts a typed name
     (select2 `tags`), because a service the store has not billed yet has no line to offer. --}}
<div class="card mt-3">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
            <div>
                <h5 class="mb-1">Repeat Purchase Reminders</h5>
                <small class="text-muted d-block">
                    WhatsApp a customer when a service they buy regularly is due again — an AC service,
                    a deep clean, a refill. One message per customer listing everything due, at most once
                    a fortnight.
                </small>
            </div>
        </div>

        <div class="alert alert-soft-info py-2 px-3" style="font-size:12px;">
            This list is for <b>services billed by name</b>. Stocked items have their own setting on the
            item form — Inventory → edit the item → <i>“Remind the customer to buy this again”</i>.
            The whole feature also has to be switched on under
            <a href="{{ route('vendor.notification-settings', ['direction' => 'send', 'tab' => 'whatsapp']) }}"
                target="_blank" rel="noopener">Notification Settings</a>.
        </div>

        <form action="{{ route('vendor.service.repeat-rules.save') }}" method="POST">
            @csrf

            <div class="table-responsive">
                <table class="table table-sm table-align-middle mb-2">
                    <thead>
                        <tr>
                            <th style="min-width:260px;">Service Name (As Billed)</th>
                            <th style="width:170px;">Remind After</th>
                            <th style="width:80px;" class="text-center">On</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($repeatRules as $rule)
                            <tr>
                                <td>
                                    <select name="rules[{{ $rule->id }}][label]" class="form-control js-repeat-service">
                                        <option value="{{ $rule->label }}" selected>{{ $rule->label }}</option>
                                        @foreach ($billedServiceNames as $name)
                                            @if ($name !== $rule->label)
                                                <option value="{{ $name }}">{{ $name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="rules[{{ $rule->id }}][repeat_days]"
                                            class="form-control" min="1" max="730" step="1"
                                            value="{{ (int) $rule->repeat_days }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">days</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <label class="toggle-switch toggle-switch-sm mb-0">
                                        <input type="checkbox" name="rules[{{ $rule->id }}][active]" value="1"
                                            class="toggle-switch-input" {{ $rule->active ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('vendor.service.repeat-rules.delete', $rule->id) }}"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Remove the reminder for “{{ $rule->label }}”?')">
                                        <i class="tio-delete-outlined"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center py-3" style="font-size:13px;">
                                    No services set up yet — add one below and it starts on the next daily run.
                                </td>
                            </tr>
                        @endforelse

                        <tr class="bg-light">
                            <td>
                                <select name="new_label" class="form-control js-repeat-service"
                                    data-placeholder="Search a billed service, or type a new one">
                                    <option value=""></option>
                                    @foreach ($billedServiceNames as $name)
                                        <option value="{{ $name }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="new_repeat_days" class="form-control" min="1"
                                        max="730" step="1" placeholder="90">
                                    <div class="input-group-append">
                                        <span class="input-group-text">days</span>
                                    </div>
                                </div>
                            </td>
                            <td colspan="2" class="text-muted" style="font-size:12px;">Add</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">
                    @if (count($billedServiceNames))
                        Search the list — it is what you have actually billed. A name that is not there yet
                        can be typed in.
                    @else
                        Type the service name exactly as it appears on your invoices.
                    @endif
                </small>
                <button type="submit" class="btn btn--primary btn-sm">Save Reminders</button>
            </div>
        </form>
    </div>
</div>

@push('script_2')
    <script>
        "use strict";
        $(document).ready(function () {
            // tags:true so a service the store has not billed yet can still be typed in — the
            // suggestion list can only ever offer names that already appear on an invoice.
            $('.js-repeat-service').each(function () {
                $(this).select2({
                    tags: true,
                    width: '100%',
                    // Left off the saved rows on purpose: clearing one there looks like it removes
                    // the rule, and it does not — the delete button does.
                    placeholder: $(this).data('placeholder') || 'Service name (as billed)',
                    dropdownParent: $(this).closest('.card'),
                });
            });
        });
    </script>
@endpush
