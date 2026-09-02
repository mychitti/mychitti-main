@extends('layouts.vendor.app')
@section('title', 'Generate Hospital Bill')

@section('content')
<style>
    /* The action row belongs to the totals card it now sits in, separated by a rule rather than
       by the page background. */
    /* Both of these sit in the card but OUTSIDE .card-body, so that the rules above them run the
       full width of the card. That also means they inherit none of its padding and have to carry
       their own — 1.25rem, matching Bootstrap's .card-body, so everything lines up under the
       Grand Total. */
    .hb-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        padding: 14px 1.25rem;
        border-top: 1px solid #e9eef5;
    }

    /* How the bill is being settled, sitting between the total and the button. A section of the
       totals card rather than a card of its own — it belongs to this bill, not beside it. */
    .hb-pay {
        padding: 14px 1.25rem 16px;
        border-top: 1px solid #e9eef5;
    }
    .hb-pay-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 8px;
    }
    /* Three groups across a wide card. The grid holds the GROUPS, not the individual controls, so
       revealing Amount Paid or Transaction ID grows its own column instead of inserting a cell and
       shunting everything after it along. */
    .hb-pay-body {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px 26px;
        align-items: start;
    }
    .hb-pay-lbl {
        display: block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 5px;
    }
    .hb-pay-note {
        font-size: 11px;
        margin: 6px 0 0;
        line-height: 1.35;
    }

    /* Tax Type and Status as segmented pills rather than loose radios. Three radios on one line
       wrapped the moment the column narrowed, dropping "Paid" onto a line of its own; a segment
       group keeps them one control and makes the chosen one obvious at a glance. */
    .hb-seg {
        display: inline-flex;
        border: 1px solid #dfe3ec;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        max-width: 100%;
    }
    .hb-seg label {
        margin: 0;
        border-right: 1px solid #eef1f6;
        cursor: pointer;
    }
    .hb-seg label:last-child { border-right: 0; }
    /* Kept focusable and reachable by keyboard — moved out of sight, not removed. */
    .hb-seg input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    .hb-seg span {
        display: block;
        padding: 6px 14px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
        transition: background .12s, color .12s;
    }
    .hb-seg label:hover span { background: #f5f7fb; }
    .hb-seg input:checked + span {
        background: #2563eb;
        color: #fff;
    }
    .hb-seg input:focus-visible + span { box-shadow: inset 0 0 0 2px rgba(37, 99, 235, .45); }
    .hb-seg input:disabled + span {
        opacity: .45;
        cursor: not-allowed;
        background: #f8fafc;
    }
    .hb-seg label:has(input:disabled) { cursor: not-allowed; }

    /* Raising the bill is the one committing action on this screen, so it is stated outright.
       btn--primary renders pale here and an enabled button that looks greyed out reads as one
       waiting on something that has not been filled in. */
    .hb-submit {
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #fff;
        font-weight: 600;
        padding: 7px 18px;
        border-radius: 7px;
        box-shadow: 0 1px 2px rgba(37, 99, 235, .35);
    }
    .hb-submit:hover,
    .hb-submit:focus {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
        box-shadow: 0 2px 6px rgba(37, 99, 235, .4);
    }
    /* The submit handler locks the button for the length of the save. Only then should it look
       unavailable — which is the whole reason it must not look that way at rest. */
    .hb-submit:disabled,
    .hb-submit[disabled] {
        background: #93b4f4;
        border-color: #93b4f4;
        box-shadow: none;
        cursor: not-allowed;
    }

    /* Receipts & payment history. The card sits in the third-width sidebar column, where an
       eleven-column table had nowhere to go but sideways — the row was cut off after Particulars
       on a phone and after Date on a desktop. One block per receipt instead, so it wraps rather
       than scrolls at every width. */
    .rcpt-item {
        padding: 10px 12px;
        border-bottom: 1px solid #e7eaf3;
        font-size: 12.5px;
    }
    .rcpt-item:last-child { border-bottom: 0; }
    .rcpt-ref {
        font-weight: 700;
        font-size: 13px;
        color: #1e2022;
        word-break: break-all;
    }
    .rcpt-sub {
        color: #8c98a4;
        font-size: 11.5px;
        margin-bottom: 6px;
    }
    .rcpt-figures {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 14px;
        font-weight: 600;
    }
    .rcpt-lbl {
        color: #8c98a4;
        font-weight: 400;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .rcpt-foot {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        justify-content: space-between;
        margin-top: 7px;
        font-size: 11.5px;
    }
</style>
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-receipt" style="font-size:22px;"></i></span>
            Generate Hospital Bill
        </h1>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
            <i class="tio-arrow-backward"></i> Back
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- This visit has been billed before. Said loudly and before anything else, because the
         screen below has already rebuilt the WHOLE visit at full price — it composes from the
         visit's chargeable items and cannot know they were billed an hour ago. Saving now raises a
         second bill for the same work.

         Not blocked, because a supplementary bill for something added later is legitimate. The
         common case is not that: it is a save that appeared to fail, so somebody opened the screen
         again. The amount still due is right here so that can be settled on the original bill. --}}
    @if(($existingBills ?? collect())->isNotEmpty())
        <div class="alert alert-warning border-warning mb-3" style="border-left-width:4px;">
            <h6 class="mb-2" style="font-weight:700; font-size:13.5px;">
                <i class="tio-warning mr-1"></i>
                {{ ($existingBills->count() === 1 ? 'A bill has' : $existingBills->count() . ' bills have') }}
                already been raised for this {{ $context === 'ipd' ? 'admission' : 'visit' }}
            </h6>

            <div class="table-responsive">
                <table class="table table-sm mb-2" style="font-size:12.5px;">
                    <thead>
                        <tr class="text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.03em;">
                            <th>Bill</th><th>Date</th><th class="text-right">Total</th>
                            <th class="text-right">Paid</th><th class="text-right">Still due</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($existingBills as $eb)
                            <tr>
                                <td style="font-weight:600;">{{ $eb->invoice_id }}</td>
                                <td>{{ $eb->date ? \Carbon\Carbon::parse($eb->date)->format('d M Y') : '—' }}</td>
                                <td class="text-right">₹{{ number_format($eb->total, 2) }}</td>
                                <td class="text-right text-success">₹{{ number_format($eb->paid, 2) }}</td>
                                <td class="text-right {{ $eb->due > 0 ? 'text-danger' : 'text-muted' }}" style="font-weight:600;">
                                    ₹{{ number_format($eb->due, 2) }}
                                </td>
                                <td class="text-right">
                                    {{-- Whichever bill screen this role may open; nothing when neither. --}}
                                    @php $billUrl = \App\Modules\HMIS\Controllers\Vendor\HospitalBillController::billUrl($eb->id); @endphp
                                    @if ($billUrl)
                                        <a href="{{ $billUrl }}"
                                           class="btn btn-sm btn--primary" style="font-size:11.5px;">Open bill</a>
                                    @else
                                        <span class="text-muted" style="font-size:11.5px;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mb-0 small">
                To collect what is still owed, open the bill above and record a payment against it —
                a part payment belongs to the bill it was made against, so raising a new bill here
                will not reduce it. Only continue below if you are billing something
                <strong>additional</strong>.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('vendor.hospital-bill.store') }}" id="billForm">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="context"    value="{{ $context }}">
        <input type="hidden" name="context_id" value="{{ $contextId }}">

        <div class="row">

            {{-- ── Patient + context info + payment. Second on wide screens, so the bill itself
                   sits on the left where reading starts and this stays beside it for reference.
                   Ordered rather than moved: the two columns hold a few hundred lines of nested
                   markup between them, and swapping the source is the version of this change most
                   likely to break something. Below md they stack, and the patient still comes
                   first there — on a phone the identity is the context you need before the bill,
                   and nothing is side by side to swap anyway. ── --}}
            <div class="col-md-4 order-md-2">

                {{-- Patient card --}}
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-user mr-1"></i>Patient</h6></div>
                    <div class="card-body py-2">
                        <p class="mb-1 font-weight-bold" style="font-size:15px;">{{ $patient->name }}</p>
                        <p class="mb-1 text-muted" style="font-size:12px;">MUID: {{ $patient->patient_uid }}</p>
                        @if($patient->phone)
                            <p class="mb-1 text-muted" style="font-size:12px;"><i class="tio-call"></i> {{ $patient->phone }}</p>
                        @endif
                        @if($patient->address || $patient->city)
                            <p class="mb-0 text-muted" style="font-size:12px;">
                                {{ implode(', ', array_filter([$patient->address, $patient->city, $patient->state])) }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- IPD info --}}
                @if($context === 'ipd' && isset($admission))
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-hospital mr-1"></i>IPD Admission</h6></div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <tr><td class="text-muted" style="width:45%;">Admission #</td><td>{{ $admission->admission_number }}</td></tr>
                            <tr><td class="text-muted">Ward</td><td>{{ $admission->ward?->ward_name }}</td></tr>
                            <tr><td class="text-muted">Bed</td><td>{{ $admission->bed?->bed_number ?: '—' }}</td></tr>
                            <tr><td class="text-muted">Admitted</td><td>{{ $admission->admission_date?->format('d M Y') }}</td></tr>
                            <tr><td class="text-muted">Daily Charge</td><td>₹{{ number_format($admission->daily_charge, 2) }}</td></tr>
                            <tr><td class="text-muted">Status</td>
                                <td>
                                    <span class="badge badge-soft-{{ $admission->status === 'admitted' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($admission->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif

                {{-- OPD info --}}
                @if($context === 'opd' && isset($visit))
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-document-text mr-1"></i>OPD Visit</h6></div>
                    <div class="card-body py-2">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <tr><td class="text-muted" style="width:45%;">Token</td><td>{{ $visit->token_number }}</td></tr>
                            <tr><td class="text-muted">Date</td><td>{{ $visit->visit_date?->format('d M Y') }}</td></tr>
                            <tr><td class="text-muted">Doctor</td>
                                <td>Dr. {{ $visit->doctorProfile?->employee?->f_name }} {{ $visit->doctorProfile?->employee?->l_name }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif

                {{-- More Info — the intake's custom rows, printed above the lines on the bill.
                     Prefilled from the patient's standing values plus anything entered for this
                     visit, and editable here so a one-off correction never has to go back to the
                     intake screen. --}}
                @php 
                    $allowedLabels = ['Email', 'City', 'Pincode'];
                    $presetLabels = array_intersect($presetLabels ?? $allowedLabels, $allowedLabels);
                    if (empty($presetLabels)) { $presetLabels = $allowedLabels; }
                    $filteredCustomInfo = array_intersect_key($customInfo ?? [], array_flip($allowedLabels));
                @endphp
                <div class="card mb-3">
                    <div class="card-header py-2"><h6 class="mb-0"><i class="tio-label-outlined mr-1"></i>More Info</h6></div>
                    <div class="card-body py-2">
                        <div id="custom-buttons" class="mb-2">
                            @foreach ($presetLabels as $label)
                                @if (!array_key_exists($label, $filteredCustomInfo))
                                    <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-2 custom-header-btn"
                                        data-label="{{ $label }}" style="border-radius:999px;font-size:12px;">+ {{ $label }}</button>
                                @endif
                            @endforeach
                            <button type="button" class="btn btn-outline-danger btn-sm mr-2 mb-2 custom-header-btn"
                                data-label="Other" style="border-radius:999px;font-size:12px;">+ Other</button>
                        </div>

                        <div id="custom-fields">
                            @foreach ($filteredCustomInfo as $label => $value)
                                <div class="form-group custom-field" data-label="{{ $label }}">
                                    <label style="font-size:12px;font-weight:600;color:#56606e;">{{ $label }}</label>
                                    <div class="d-flex">
                                        <input type="hidden" name="header_label[]" value="{{ $label }}">
                                        <input type="text" class="form-control form-control-sm mr-2" name="header_field[]"
                                            value="{{ $value }}">
                                        <a type="button" class="text-danger remove-field" style="align-self:center;"><i class="tio-delete-outlined"></i></a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ── Receipts & Payment History ──────────────────────────
                     Alongside the bill rather than under it: what this patient has already paid
                     is the thing you check while deciding what to charge now, and at the foot of
                     the page it sat below the button that had already committed the decision.
                     The subtitle drops here — the heading says it, and a sidebar has no room to
                     say it twice. ── --}}
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0"><i class="tio-history mr-1"></i>Receipts &amp; Payment History</h6>
                    </div>
                    <div class="card-body p-0">
            @if(!empty($existingReceipts) && count($existingReceipts) > 0)
                <div class="rcpt-list">
                    @foreach($existingReceipts as $rec)
                        @php
                            // The status the controller worked out, not one re-guessed from the
                            // balance. A bill with nothing paid against it has a due, and reading
                            // "there is a due, so it must be part paid" is how a wholly unpaid
                            // ₹13,000 bill came to be labelled Partially Paid on this screen.
                            $rcptStatus = trim((string) ($rec['status'] ?? ''));
                            if ($rcptStatus === '') {
                                $rcptStatus = $rec['due'] > 0 ? 'Unpaid' : 'Paid';
                            }
                            $rcptTone = match (strtolower($rcptStatus)) {
                                'paid'   => 'success',
                                'unpaid' => 'danger',
                                default  => 'warning',
                            };
                        @endphp
                        <div class="rcpt-item">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="gap:6px;">
                                <span class="badge badge-soft-info">{{ $rec['type'] }}</span>
                                <span class="badge badge-soft-{{ $rcptTone }}">{{ $rcptStatus }}</span>
                            </div>

                            <div class="rcpt-ref">{{ $rec['receipt_no'] }}</div>
                            <div class="rcpt-sub">{{ $rec['item_name'] }} &middot; {{ $rec['date'] }}</div>

                            <div class="rcpt-figures">
                                <span><span class="rcpt-lbl">Total</span> ₹{{ number_format($rec['amount'], 2) }}</span>
                                <span class="text-success"><span class="rcpt-lbl">Paid</span> ₹{{ number_format($rec['paid'], 2) }}</span>
                                <span class="{{ $rec['due'] > 0 ? 'text-danger' : 'text-muted' }}">
                                    <span class="rcpt-lbl">Due</span> ₹{{ number_format($rec['due'], 2) }}
                                </span>
                            </div>

                            <div class="rcpt-foot">
                                <span class="text-muted">
                                    @if(!empty($rec['mode'])){{ $rec['mode'] }} &middot; @endif{{ $rec['billed_by'] }}
                                </span>
                                @if(!empty($rec['pdf_url']))
                                    <a href="{{ $rec['pdf_url'] }}" target="_blank"
                                       class="btn btn-xs btn-outline-primary" title="View / Print">
                                        <i class="tio-print"></i> View
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="tio-receipt-outlined style-2 opacity-50" style="font-size:28px;"></i>
                    <p class="mt-2 mb-0">No previous receipts found for this patient.</p>
                </div>
            @endif
                    </div>
                </div>

            </div>

            {{-- ── The bill itself: line items, medicines, totals and the button that raises it.
                   First on wide screens. ── --}}
            <div class="col-md-8 order-md-1">

                {{-- ── Section 1: Service Charges ────────────────────── --}}
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="tio-hospital mr-1"></i>Services, Treatments &amp; Reports</h6>
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="addServiceRow()">
                            <i class="tio-add"></i> Add Row
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Description</th>
                                    <th style="width:70px;">Qty</th>
                                    <th style="width:110px;">Rate (₹)</th>
                                    <th style="width:100px;" class="text-right">Amount</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="serviceBody">
                                @forelse($serviceItems as $item)
                                <tr class="item-row">
                                    <td>
                                        <input type="hidden" name="inv_id[]" value="">
                                        <input type="hidden" name="item_hsn[]" value="">
                                        <input type="text" name="item_name[]" class="form-control form-control-sm"
                                            value="{{ $item['name'] }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_qty[]" class="form-control form-control-sm item-qty"
                                            value="{{ $item['qty'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_price[]" class="form-control form-control-sm item-price"
                                            value="{{ $item['price'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td class="item-amount text-right align-middle">
                                        ₹{{ number_format($item['qty'] * $item['price'], 2) }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)">
                                            <i class="tio-delete-outlined"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr id="noServiceRow">
                                    <td colspan="5" class="text-center text-muted py-3" style="font-size:12px;">
                                        <i class="tio-checkmark-circle text-success mr-1"></i>
                                        Standard consultation fee for this visit has already been receipted below.
                                        Click <strong>+ Add Row</strong> above if you wish to add extra room or service charges.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Section 2: Medicines / Inventory Items ─────────── --}}
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="tio-medicine-bottle mr-1"></i>Medicines &amp; Supplies</h6>
                    </div>
                    <div class="card-body border-bottom pb-3">
                        {{-- Search bar --}}
                        <div class="input-group input-group-sm">
                            <input type="text" id="invSearch" class="form-control"
                                placeholder="Search medicine or inventory item by name…" autocomplete="off">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="tio-search"></i></span>
                            </div>
                        </div>
                        <div id="invSearchResults" class="border rounded mt-1" style="display:none; max-height:200px; overflow-y:auto; background:#fff; z-index:999; position:relative;"></div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Medicine / Item</th>
                                    <th style="width:70px;">Qty</th>
                                    <th style="width:110px;">Rate (₹)</th>
                                    <th style="width:100px;" class="text-right">Amount</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="medicineBody">
                                @forelse($medicineItems as $item)
                                <tr class="item-row">
                                    <td>
                                        <input type="hidden" name="inv_id[]" value="{{ $item['inv_id'] ?? '' }}">
                                        <input type="hidden" name="item_hsn[]" value="{{ $item['hsn'] ?? '' }}">
                                        <input type="text" name="item_name[]" class="form-control form-control-sm"
                                            value="{{ $item['name'] }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_qty[]" class="form-control form-control-sm item-qty"
                                            value="{{ $item['qty'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="number" name="item_price[]" class="form-control form-control-sm item-price"
                                            value="{{ $item['price'] }}" min="0" step="0.01" required>
                                    </td>
                                    <td class="item-amount text-right align-middle">
                                        ₹{{ number_format($item['qty'] * $item['price'], 2) }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)">
                                            <i class="tio-delete-outlined"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr id="noMedRow">
                                    <td colspan="5" class="text-center text-muted py-2" style="font-size:12px;">
                                        Search above to add medicines / items
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Grand Total ─────────────────────────────────────── --}}
                {{-- The bill stays whole: every line shows at full price and Grand Total is the
                     bill's own total, which is what gets printed and what the books record. Money
                     already collected against this visit is subtracted BELOW that, so the figure
                     staff actually collect on is the one still owed — not the total again. --}}
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-end align-items-center gap-4">
                            <span class="text-muted">Grand Total</span>
                            <h4 class="mb-0 font-weight-bold" id="grandTotal">₹0.00</h4>
                        </div>

                        @if(($alreadyPaid ?? 0) > 0)
                            <div class="d-flex justify-content-end align-items-center gap-4 mt-1"
                                 style="font-size:13px;">
                                <span class="text-muted">
                                    Already paid for this {{ $context === 'ipd' ? 'admission' : 'visit' }}
                                </span>
                                <span class="text-success" style="font-weight:600;">
                                    − ₹{{ number_format($alreadyPaid, 2) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-end align-items-center gap-4 mt-2 pt-2"
                                 style="border-top:1px solid #e9eef5;">
                                <span style="font-weight:700;">To pay</span>
                                <h4 class="mb-0 font-weight-bold text-danger" id="hbToPay">₹0.00</h4>
                            </div>
                        @endif
                    </div>

                    {{-- How it is being settled, between the total and the button that raises the
                         bill: the figure above is what is owed, this is what is being paid against
                         it, and the button commits both. A section rather than a card of its own —
                         it is part of this bill, not a panel sitting beside it. --}}
                    @php
                        $storeGstEnabled = \App\CentralLogics\Helpers::get_store_data()->gst &&
                            json_decode(\App\CentralLogics\Helpers::get_store_data()->gst)->status;
                    @endphp
                    <div class="hb-pay">
                        <div class="hb-pay-title"><i class="tio-money mr-1"></i>Payment</div>
                        {{-- Three self-contained groups, each owning whatever it reveals. Every
                             control used to be its own grid cell, so the GST note made the first
                             column tall, the status radios wrapped onto two lines, and showing
                             Transaction ID inserted a cell that reflowed the whole row. --}}
                        <div class="hb-pay-body">

                            <div class="hb-pay-group">
                                <span class="hb-pay-lbl">Tax Type</span>
                                <div class="hb-seg">
                                    <label>
                                        <input type="radio" name="tax_type" value="non-gst" checked
                                               onchange="toggleHospGst(this.value)"><span>Non-GST</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="tax_type" value="gst"
                                               {{ $storeGstEnabled ? 'onchange=toggleHospGst(this.value)' : 'disabled' }}><span>GST</span>
                                    </label>
                                </div>

                                @if(!$storeGstEnabled)
                                    <p class="hb-pay-note text-warning">
                                        <i class="tio-info-outined"></i> GST not configured.
                                        <a href="{{ route('vendor.shop.edit') }}" target="_blank">Set it up</a>
                                    </p>
                                @else
                                    <div id="gstPercentWrap" style="display:none;" class="mt-2">
                                        <span class="hb-pay-lbl">GST %</span>
                                        <input type="number" name="gst_percent" id="gstPercent" class="form-control form-control-sm"
                                               min="0" max="100" step="0.01" placeholder="e.g. 18">
                                    </div>
                                @endif
                            </div>

                            <div class="hb-pay-group">
                                <span class="hb-pay-lbl">Status</span>
                                <div class="hb-seg">
                                    <label>
                                        <input type="radio" name="payment_status" value="Unpaid" id="ps_unpaid"
                                               checked onchange="togglePaymentStatus()"><span>Unpaid</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="payment_status" value="Partially Paid" id="ps_partial"
                                               onchange="togglePaymentStatus()"><span>Partial</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="payment_status" value="Paid" id="ps_paid"
                                               onchange="togglePaymentStatus()"><span>Paid</span>
                                    </label>
                                </div>

                                <div id="hbPartialWrap" style="display:none;" class="mt-2">
                                    <span class="hb-pay-lbl">Amount Paid (₹) <span class="text-danger">*</span></span>
                                    <input type="number" name="paid_amount" id="hbPaidAmount" class="form-control form-control-sm"
                                           min="0" step="0.01" placeholder="Enter amount paid" oninput="recalc()">
                                    <small class="text-muted d-block mt-1" id="hbBalanceNotice">Remaining Balance: ₹0.00</small>
                                </div>
                            </div>

                            <div class="hb-pay-group">
                                <span class="hb-pay-lbl">Method</span>
                                <select name="payment_method" id="hbPayMethod" class="form-control form-control-sm" onchange="hbSyncTxn()">
                                    <option value="Cash">Cash</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Card">Card</option>
                                    <option value="Net Banking">Net Banking</option>
                                    <option value="Cheque">Cheque</option>
                                </select>

                                <div id="hbTxnWrap" style="display:none;" class="mt-2">
                                    <span class="hb-pay-lbl">
                                        Transaction ID
                                        <span class="text-danger" id="hbTxnReq">*</span>
                                        <span class="text-muted" id="hbTxnOpt" style="display:none; font-weight:400; text-transform:none; letter-spacing:0;">(optional)</span>
                                    </span>
                                    <input type="text" name="transaction_id" id="hbTxnId" class="form-control form-control-sm"
                                           placeholder="UPI / card / online reference">
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Inside the totals card, not floating on the page under it: raising the bill
                         is what the figure above is for, and the two read as one act. --}}
                    <div class="hb-actions">
                        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-sm hb-submit" id="hbSubmitBtn">
                            <i class="tio-receipt mr-1"></i> Save &amp; Generate Bill
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>

@endsection

@push('script_2')
<script>
const INV_SEARCH_URL = "{{ route('vendor.hospital-bill.inventory-search') }}";

// Offer a transaction ID for every non-cash method, but only insist on it where the payer is
// actually handed one. UPI settles on the counter's own phone with nothing to copy down until
// they go looking for it, so the box is there to fill in and never blocks the bill. The rule in
// HospitalBillController::store matches this exactly.
function hbSyncTxn() {
    const sel = document.getElementById('hbPayMethod'); if (!sel) return;
    const method = (sel.value || '').toLowerCase();
    const online = method !== 'cash';
    const mustHave = method === 'card' || method === 'net banking';

    const wrap = document.getElementById('hbTxnWrap');
    const txn  = document.getElementById('hbTxnId');
    wrap.style.display = online ? '' : 'none';
    txn.required = mustHave;
    if (!online) txn.value = '';

    const req = document.getElementById('hbTxnReq');
    const opt = document.getElementById('hbTxnOpt');
    if (req) req.style.display = mustHave ? '' : 'none';
    if (opt) opt.style.display = mustHave ? 'none' : '';
}
document.addEventListener('DOMContentLoaded', hbSyncTxn);

function togglePaymentStatus() {
    const status = document.querySelector('input[name="payment_status"]:checked')?.value || 'Unpaid';
    const wrap = document.getElementById('hbPartialWrap');
    const inp = document.getElementById('hbPaidAmount');
    if (wrap) wrap.style.display = (status === 'Partially Paid') ? '' : 'none';
    if (inp) inp.required = (status === 'Partially Paid');
    recalc();
}
document.addEventListener('DOMContentLoaded', togglePaymentStatus);

function toggleHospGst(val) {
    const wrap = document.getElementById('gstPercentWrap');
    const inp  = document.getElementById('gstPercent');
    if (wrap) { wrap.style.display = val === 'gst' ? '' : 'none'; }
    if (inp)  { inp.required = val === 'gst'; }
    recalc();
}

// ── Recalculate totals ────────────────────────────────────────────────
function recalc() {
    let base = 0;
    document.querySelectorAll('#billForm .item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const amt   = qty * price;
        const amtCell = row.querySelector('.item-amount');
        if (amtCell) amtCell.textContent = '₹' + amt.toFixed(2);
        base += amt;
    });
    const taxType   = document.querySelector('input[name="tax_type"]:checked')?.value || 'non-gst';
    const gstPct    = taxType === 'gst' ? (parseFloat(document.getElementById('gstPercent')?.value) || 0) : 0;
    const total     = base * (1 + gstPct / 100);
    const gstLabel  = gstPct > 0 ? ` (incl. ${gstPct}% GST)` : '';
    document.getElementById('grandTotal').textContent = '₹' + total.toFixed(2) + gstLabel;

    // Money already collected against this visit on an earlier bill. The bill's own total is
    // untouched — this only drives what is still owed, which is the number staff collect on.
    const alreadyPaid = @json((float) ($alreadyPaid ?? 0));
    const toPay       = Math.max(0, total - alreadyPaid);

    const toPayEl = document.getElementById('hbToPay');
    if (toPayEl) toPayEl.textContent = '₹' + toPay.toFixed(2);

    const status = document.querySelector('input[name="payment_status"]:checked')?.value || 'Unpaid';
    if (status === 'Partially Paid') {
        const paid = parseFloat(document.getElementById('hbPaidAmount')?.value) || 0;
        // Measured against what is still owed, not the bill total, so the hint does not tell
        // somebody ₹1,700 is outstanding when ₹300 of it has already been handed over.
        const due = Math.max(0, toPay - paid);
        const notice = document.getElementById('hbBalanceNotice');
        if (notice) {
            notice.textContent = alreadyPaid > 0
                ? 'Of ₹' + toPay.toFixed(2) + ' still owed, remaining after this: ₹' + due.toFixed(2)
                : 'Remaining Balance: ₹' + due.toFixed(2);
        }
    }
}

// ── Add blank service charge row ─────────────────────────────────────
function addServiceRow() {
    const placeholder = document.getElementById('noServiceRow');
    if (placeholder) placeholder.remove();

    const tbody = document.getElementById('serviceBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="inv_id[]" value="">
            <input type="hidden" name="item_hsn[]" value="">
            <input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="e.g. Nursing Charge" required>
        </td>
        <td><input type="number" name="item_qty[]" class="form-control form-control-sm item-qty" value="1" min="0" step="0.01" required></td>
        <td><input type="number" name="item_price[]" class="form-control form-control-sm item-price" value="0" min="0" step="0.01" required></td>
        <td class="item-amount text-right align-middle">₹0.00</td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)"><i class="tio-delete-outlined"></i></button></td>
    `;
    tbody.appendChild(tr);
    tr.querySelectorAll('.item-qty, .item-price').forEach(el => el.addEventListener('input', recalc));
    recalc();
}

// ── Remove a row ──────────────────────────────────────────────────────
function removeRow(btn) {
    btn.closest('tr').remove();
    recalc();
}

// ── Add an inventory item row to the medicines table ─────────────────
function addMedicineRow(item) {
    // Remove placeholder row
    const placeholder = document.getElementById('noMedRow');
    if (placeholder) placeholder.remove();

    const tbody = document.getElementById('medicineBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="inv_id[]" value="${item.id}">
            <input type="hidden" name="item_hsn[]" value="${item.hsn || ''}">
            <input type="text" name="item_name[]" class="form-control form-control-sm"
                value="${item.item_name}" required>
        </td>
        <td><input type="number" name="item_qty[]" class="form-control form-control-sm item-qty" value="1" min="0" step="0.01" required></td>
        <td><input type="number" name="item_price[]" class="form-control form-control-sm item-price" value="${item.mrp || 0}" min="0" step="0.01" required></td>
        <td class="item-amount text-right align-middle">₹${parseFloat(item.mrp || 0).toFixed(2)}</td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(this)"><i class="tio-delete-outlined"></i></button></td>
    `;
    tbody.appendChild(tr);
    tr.querySelectorAll('.item-qty, .item-price').forEach(el => el.addEventListener('input', recalc));
    recalc();
}

// ── Inventory search ──────────────────────────────────────────────────
let searchTimeout;
document.getElementById('invSearch').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    const resultsBox = document.getElementById('invSearchResults');

    if (q.length < 2) { resultsBox.style.display = 'none'; return; }

    searchTimeout = setTimeout(() => {
        fetch(INV_SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                resultsBox.innerHTML = '<div class="p-2 text-muted" style="font-size:12px;">No items found.</div>';
            } else {
                resultsBox.innerHTML = items.map(item => `
                    <div class="inv-result-item d-flex justify-content-between align-items-center px-3 py-2"
                         style="cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:13px;"
                         onmouseover="this.style.background='#f0f7ff'"
                         onmouseout="this.style.background=''"
                         onclick='selectInventoryItem(${JSON.stringify(item)})'>
                        <span><strong>${item.item_name}</strong>
                            ${item.hsn ? '<span class="text-muted ml-2" style="font-size:11px;">HSN: ' + item.hsn + '</span>' : ''}
                        </span>
                        <span class="badge badge-soft-primary">₹${parseFloat(item.mrp || 0).toFixed(2)}</span>
                    </div>
                `).join('');
            }
            resultsBox.style.display = 'block';
        })
        .catch(() => { resultsBox.style.display = 'none'; });
    }, 300);
});

function selectInventoryItem(item) {
    addMedicineRow(item);
    document.getElementById('invSearch').value = '';
    document.getElementById('invSearchResults').style.display = 'none';
}

// Close search results on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#invSearch') && !e.target.closest('#invSearchResults')) {
        document.getElementById('invSearchResults').style.display = 'none';
    }
});

// Attach listeners to pre-rendered rows and calculate on load
document.querySelectorAll('#billForm .item-qty, #billForm .item-price').forEach(el => {
    el.addEventListener('input', recalc);
});
const gstPctInput = document.getElementById('gstPercent');
if (gstPctInput) gstPctInput.addEventListener('input', recalc);

document.addEventListener('DOMContentLoaded', recalc);

// More Info rows — same interaction as the intake screen and the advanced bill: a named chip
// drops a row with that label fixed, "+ Other" drops one where the label is typed too.
$(document).on('click', '.custom-header-btn', function () {
    const label = $(this).data('label');
    let row;

    if (label === 'Other') {
        row = `
        <div class="form-group custom-field" data-label="Other">
            <div class="d-flex">
                <input type="text" class="form-control form-control-sm mr-2" placeholder="Label" name="header_label[]">
                <input type="text" class="form-control form-control-sm mr-2" placeholder="Value" name="header_field[]">
                <a type="button" class="text-danger remove-field" style="align-self:center;"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>`;
    } else {
        row = `
        <div class="form-group custom-field" data-label="${label}">
            <label style="font-size:12px;font-weight:600;color:#56606e;">${label}</label>
            <div class="d-flex">
                <input type="hidden" name="header_label[]" value="${label}">
                <input type="text" class="form-control form-control-sm mr-2" placeholder="${label}" name="header_field[]">
                <a type="button" class="text-danger remove-field" style="align-self:center;"><i class="tio-delete-outlined"></i></a>
            </div>
        </div>`;

        $(this).hide();
    }

    $('#custom-fields').append(row);
});

$('#custom-fields').on('click', '.remove-field', function () {
    const $row  = $(this).closest('.custom-field');
    const label = $row.data('label');

    $('#custom-buttons button').each(function () {
        if ($(this).data('label') === label) {
            $(this).show();
        }
    });

    $row.remove();
});

// Two guards on one submit.
//
// The confirm is for a visit that already carries a bill: this screen rebuilt the whole visit at
// full price and saving raises a second bill for the same work, so it is worth one deliberate
// click. The button lock is for the ordinary double-click — and for the slow save, where nothing
// appears to happen for several seconds and the natural reaction is to press it again.
(function () {
    const form = document.getElementById('billForm');
    const btn  = document.getElementById('hbSubmitBtn');
    if (!form || !btn) return;

    const alreadyBilled = @json(($existingBills ?? collect())->isNotEmpty());
    const contextWord   = @json($context === 'ipd' ? 'admission' : 'visit');

    form.addEventListener('submit', function (e) {
        if (alreadyBilled && !form.dataset.hbConfirmed) {
            const ok = confirm(
                'A bill has already been raised for this ' + contextWord + '.\n\n'
                + 'This will create a SECOND bill for the same work. To collect what is still '
                + 'owed, cancel and open the existing bill instead.\n\nRaise another bill anyway?'
            );
            if (!ok) { e.preventDefault(); return; }
            form.dataset.hbConfirmed = '1';
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="tio-refresh"></i> Generating bill…';
    });
})();
</script>
@endpush
