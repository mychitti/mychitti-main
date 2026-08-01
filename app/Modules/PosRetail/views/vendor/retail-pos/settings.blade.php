@extends('layouts.vendor.app')

@section('title', 'Retail POS Settings')

@push('css_or_js')
    @include('posretail::vendor.retail-pos._styles')
@endpush

@section('content')
    <div class="content container-fluid rp">
        <div class="rp-head">
            <div>
                <h1>Settings</h1>
                <div class="sub">Store-level Retail POS preferences</div>
            </div>
        </div>

        {{-- UPI ID for payment QR --}}
        <div class="rp-card">
            <div class="hd"><span class="accent">Payment</span></div>
            <div class="bd">
                <form method="post" action="{{ route('vendor.retail-pos.upi.save') }}" class="rp-filter">
                    @csrf
                    <label class="font-weight-bold mb-0 mr-1">Store UPI ID (for payment QR)</label>
                    <input type="text" name="upi_id" value="{{ $upiId ?? '' }}" class="rp-input" placeholder="storename@upi" style="min-width:240px">
                    <button class="rp-btn p">Save UPI ID</button>
                </form>
            </div>
        </div>

        {{-- New Sale screen UI template --}}
        @php
            $uiTemplate = $uiTemplate ?? 'classic';
            $uiTemplates = [
                'classic' => ['Classic', 'Indigo theme · two columns — products left, cart right (default).', '#4f46e5'],
                'compact' => ['Compact', 'Teal theme · dense, cart-first layout for fast billing.', '#0d9488'],
                'modern'  => ['Modern',  'Pink/gradient theme · large rounded touch tiles.', '#db2777'],
                'search'  => ['Search-first', 'Just a big search bar — results appear below as you type. No product grid.', '#0ea5e9'],
            ];
        @endphp
        <div class="rp-card">
            <div class="hd"><span class="accent">New Sale UI Template</span></div>
            <div class="bd">
                <p class="text-muted" style="font-size:12px;margin-bottom:10px;">Choose the layout used on the New Sale screen.</p>
                <form method="post" action="{{ route('vendor.retail-pos.ui-template.save') }}">
                    @csrf
                    <div class="row">
                        @foreach ($uiTemplates as $key => [$label, $desc, $color])
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label style="display:block;cursor:pointer;border:1.5px solid {{ $uiTemplate === $key ? $color : '#e4e4e7' }};border-radius:12px;padding:12px 14px;{{ $uiTemplate === $key ? 'background:' . $color . '0f;' : '' }}">
                                    <span style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" name="pos_ui_template" value="{{ $key }}" {{ $uiTemplate === $key ? 'checked' : '' }}>
                                        <span style="width:14px;height:14px;border-radius:4px;background:{{ $color }};display:inline-block;"></span>
                                        <b>{{ $label }}</b>
                                    </span>
                                    <span style="display:block;font-size:11.5px;color:#71717a;margin-top:4px;">{{ $desc }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <button class="rp-btn p">Save UI Template</button>
                </form>
            </div>
        </div>

        {{-- Printed receipt template --}}
        @php
            $receiptTemplate = $receiptTemplate ?? 'standard';
            $receiptTemplates = [
                'standard' => ['Standard', 'Monospace 80mm thermal slip with dashed separators (default).'],
                'modern'   => ['Modern',   'Clean sans-serif slip with a highlighted total box.'],
                'elegant'  => ['Elegant',  'Bordered boutique layout with serif heading and ruled item table.'],
            ];
        @endphp
        <div class="rp-card">
            <div class="hd"><span class="accent">Receipt Template</span></div>
            <div class="bd">
                <style>
                    .rp-tpl-opt { border: 1.5px solid #e4e4e7; background: #fff; transition: border-color .12s, background .12s; }
                    .rp-tpl-opt.selected { border-color: #111; background: #f4f4f5; }
                </style>
                <p class="text-muted" style="font-size:12px;margin-bottom:10px;">Choose the layout used for the printed customer receipt.</p>
                <form method="post" action="{{ route('vendor.retail-pos.receipt-template.save') }}">
                    @csrf
                    <div class="row">
                        @foreach ($receiptTemplates as $key => [$label, $desc])
                            <div class="col-md-2 mb-2">
                                <label class="rp-tpl-opt {{ $receiptTemplate === $key ? 'selected' : '' }}" style="display:block;cursor:pointer;border-radius:12px;padding:12px 14px;">
                                    <img src="{{ asset('storage/app/public/uploaded/templates/' . $key . '.png') }}" alt="{{ $label }} receipt preview"
                                        onerror="this.style.display='none';"
                                        style="display:block;max-width:140px;width:100%;height:auto;background:#fff;border:1px solid #e4e4e7;border-radius:8px;margin:0 auto 10px;">
                                    <span style="display:flex;align-items:center;gap:8px;">
                                        <input type="radio" name="pos_receipt_template" value="{{ $key }}" {{ $receiptTemplate === $key ? 'checked' : '' }}>
                                        <b>{{ $label }}</b>
                                    </span>
                                    <span style="display:block;font-size:11.5px;color:#71717a;margin-top:4px;">{{ $desc }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <button class="rp-btn p">Save Receipt Template</button>
                </form>
            </div>
        </div>

        <div class="rp-card">
            <div class="hd"><span class="accent">Receipt Contents</span></div>
            <div class="bd">
                <form method="post" action="{{ route('vendor.retail-pos.mrp-saving.save') }}">
                    @csrf
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:12px;">
                        {{-- Posted as 0 first so unticking actually turns it off; an unchecked box
                             sends nothing at all. --}}
                        <input type="hidden" name="pos_show_mrp_saving" value="0">
                        <input type="checkbox" name="pos_show_mrp_saving" value="1" style="margin-top:3px;"
                               {{ $showMrpSaving ? 'checked' : '' }}>
                        <span>
                            <b>Show “Saved Rs. …/- On MRP”</b>
                            <span style="display:block;font-size:11.5px;color:#71717a;margin-top:2px;">
                                Prints the customer's total saving against MRP at the bottom of the receipt,
                                including any bill discount and coupon. Turn it off if you'd rather not print
                                the gap between MRP and your selling price.
                            </span>
                        </span>
                    </label>
                    <button class="rp-btn p">Save</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('input[name="pos_receipt_template"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll('.rp-tpl-opt').forEach(function (label) {
                    label.classList.remove('selected');
                });
                this.closest('.rp-tpl-opt').classList.add('selected');
            });
        });
    </script>
@endsection
