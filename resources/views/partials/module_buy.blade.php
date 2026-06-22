  <div class="pc-panel pc-active" id="calculator">

      <form id="subscriptionPlanForm"
          @if (Route::currentRouteName() == 'admin.plan.module-store') action="{{ env('APP_MODE') != 'demo' ? route('admin.plan.buy-module') : 'javascript:' }}"
          @else 
          action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.buy-module') : 'javascript:' }}" @endif
          method="post" enctype="multipart/form-data">
          @csrf

          <div class="row">
              <div class="pc-calc-section col-md-8">
                  <h2>Subscribe Modules</h2>
                  @if (Route::currentRouteName() == 'admin.plan.module-store')
                      <div class="row">
                          <div class="col-md-3 p-1">
                              <label class="form-check-label" for="flexRadioDefault2">Store</label>
                              <select data-placeholder="Select Store" required name="store_id" id="search_store_id"
                                  class="form-control js-select2-custom ">
                                  <option value=""></option>

                              </select>
                          </div>
                          <div class="col-md-3 p-1 ">
                              <label class="form-check-label" for="invoice_date">Invoice Date</label>

                              <div class=" d-flex gap-2 align-items-center border rounded p-2 pb-3">

                                  <input type="radio" value="1" name="billing" class="billing_status"
                                      id="billing" checked>
                                  <label for="billing" class="mb-0">Billing</label>
                                  <input type="radio" value="0" name="billing" class="billing_status"
                                      id="retail">
                                  <label for="retail" class="mb-0">Retail</label>
                              </div>
                          </div>
                          <div class="col-md-3 p-1 invoice_date_inp">
                              <label class="form-check-label" for="invoice_date">Invoice Date</label>
                              <input type="date" name="invoice_date" id="invoice_date" class="form-control"
                                  value="{{ date('Y-m-d') }}">
                          </div>
                          <div class="col-md-3 p-1 invoice_date_inp">
                              <label class="form-check-label" for="bill_number">Bill Number</label>
                              @php
                                  $inv_prefix =
                                      \App\Models\BusinessSetting::where('key', 'admin_invoice_prefix')->first()
                                          ?->value ?? 'MSM';
                                  $fyStart = now()->month >= 4 ? now()->year : now()->year - 1;
                                  $fy_label = substr($fyStart, -2) . '-' . substr($fyStart + 1, -2);
                                  $next_serial =
                                      (int) (\App\Models\ManualInvoice::where('financial_year', $fy_label)
                                          ->where('generated_by', 'admin')
                                          ->max('invoice_serial') ?? 0) + 1;
                              @endphp
                              <div class="input-group">
                                  <span class="input-group-text bg-white"
                                      style="border-right:none; font-size:12px; padding-right:0;">
                                      {{ $inv_prefix }}_{{ $fy_label }}_
                                  </span>
                                  <input type="number" name="bill_number" id="bill_number" class="form-control"
                                      value="{{ $next_serial }}" style="border-left:none; padding-left:2px;"
                                      min="1">
                              </div>
                          </div>
                      </div>
                  @endif
                  @php $plan_durations = _planDurations(); @endphp
                  <div class="pc-global-duration mb-3">
                      <label class="pc-label"><b>Select Plan Duration</b></label>
                      <div class="pc-duration-grid">
                          @foreach ($plan_durations as $i => $dur)
                              <div class="pc-global-duration-card {{ $i == 0 ? 'pc-selected' : '' }}"
                                  data-months="{{ $dur->months }}">{{ $dur->label }}</div>
                          @endforeach
                      </div>
                  </div>

                  <h3 style="color: var(--primary); margin-bottom: 12px; font-size: 16px;">Select Modules &
                      Duration
                  </h3>
                  @php
                      $sub_modules = _subMoudles();
                      $gst_settings = _planGstSettings();
                      if (Route::currentRouteName() !== 'admin.plan.module-store') {
                          $storeBusinessType = \App\CentralLogics\Helpers::get_store_data()->business_type ?? null;
                          if ($storeBusinessType) {
                              $sub_modules = $sub_modules->filter(fn($m) =>
                                  in_array(strtolower($m->business_type ?? 'all'), ['all', strtolower($storeBusinessType)])
                              )->sortBy(fn($m) =>
                                  strtolower($m->business_type ?? 'all') === strtolower($storeBusinessType) ? 0 : 1
                              )->values();
                          }
                      }
                  @endphp
                  @php
                      $bedTier = $bedTier ?? null;
                  @endphp
                  @if (isset($sub_modules) && count($sub_modules) > 0)
                      @foreach ($sub_modules as $module)
                          @php
                              $isHospitalModule =
                                  str_contains(strtolower($module->name), 'hospital') &&
                                  (Route::is('admin.plan.module-store') || (isset($store) && $store->module_id == 6));
                              $isSchoolModule =
                                  (($module->Key ?? '') === 'school_manage') &&
                                  (Route::is('admin.plan.module-store') || strtolower($storeBusinessType ?? '') === 'school');
                              $tieredModule = $isHospitalModule || $isSchoolModule;
                              // hospital pre-selects the store's active tier; school lets the user pick.
                              $activeTier = $isHospitalModule ? ($bedTier ?? null) : null;
                              // POS Retail is only offered to pos_retail stores (always on the admin store-builder).
                              $hidePosRetail =
                                  (str_contains(strtolower($module->name), 'pos retail') ||
                                      strtolower($module->Key ?? '') === 'pos_retail') &&
                                  !Route::is('admin.plan.module-store') &&
                                  strtolower($storeBusinessType ?? '') !== 'pos_retail';
                          @endphp
                          @continue($hidePosRetail)
                          <div class="pc-module-item" data-module-id="{{ $module->id }}">
                              <div class="pc-module-top">
                                  <input type="checkbox" class="pc-checkbox pc-module-check"
                                      data-module-id="{{ $module->id }}">
                                  <div class="pc-module-name">{{ _moduleDisplayName($module->Key ?? null, $module->name) }}</div>
                                  <div class="pc-price-amount">
                                      @if ($tieredModule)
                                          @if ($activeTier)
                                              ₹{{ number_format($activeTier->price_monthly) }}/month
                                          @else
                                              Select a tier
                                          @endif
                                      @else
                                          ₹{{ number_format($module->price_per_month) }}/month
                                      @endif
                                  </div>
                              </div>

                              @if ($tieredModule)
                                  @php
                                      $tierList = $isSchoolModule
                                          ? \App\Models\SchoolStudentTier::where('is_active', true)->orderBy('min_students')->get()
                                          : \App\Models\HospitalBedTier::where('is_active', true)->orderBy('min_beds')->get();
                                      $tierInputName = $isSchoolModule ? 'student_tier_id' : 'bed_tier_id';
                                      $tierLabel = $isSchoolModule ? 'Select School Plan:' : 'Select Hospital Tier:';
                                      $tierRange = fn($t) => $isSchoolModule ? $t->student_range : $t->bed_range;
                                  @endphp
                                  @if ($tierList->isNotEmpty())
                                      <div class="mb-2 px-1">
                                          <label class="pc-label mb-1"><b>{{ $tierLabel }}</b></label>
                                          <div class="d-flex flex-wrap" style="gap:8px;" id="bedTierSelector">
                                              @foreach ($tierList as $tier)
                                                  <div class="bed-tier-option {{ $activeTier && $activeTier->id == $tier->id ? 'selected' : '' }}"
                                                      data-tier-id="{{ $tier->id }}"
                                                      data-price-monthly="{{ $tier->price_monthly }}"
                                                      data-price-yearly="{{ $tier->price_yearly }}"
                                                      data-is-custom="{{ $tier->is_custom ? 1 : 0 }}"
                                                      style="border: 2px solid {{ $activeTier && $activeTier->id == $tier->id ? '#00868f' : '#dee2e6' }};
                                                     border-radius: 8px; padding: 8px 14px; cursor: pointer; background: #fff; min-width: 140px;">
                                                      <div style="font-weight:600; color:#333; font-size:13px;">
                                                          {{ $tier->tier_name }}</div>
                                                      <div style="font-size:11px; color:#666;">{{ $tierRange($tier) }}
                                                      </div>
                                                      <div
                                                          style="font-size:13px; font-weight:700; color:#00868f; margin-top:2px;">
                                                          @if ($tier->is_custom)
                                                              Contact Us
                                                          @else
                                                              ₹{{ number_format($tier->price_monthly) }}/month
                                                          @endif
                                                      </div>
                                                  </div>
                                              @endforeach
                                          </div>
                                          <input type="hidden" id="selectedBedTierId" name="{{ $tierInputName }}"
                                              value="{{ $activeTier?->id }}">
                                      </div>
                                      <div class="alert alert-info py-2 px-3 mb-2 pc-tier-info-banner"
                                          style="font-size:13px;">
                                          @if ($activeTier)
                                              <strong>Selected Tier:</strong> {{ $activeTier->tier_name }}
                                              ({{ $tierRange($activeTier) }})
                                              &mdash;
                                              ₹{{ number_format($activeTier->price_monthly) }}/month
                                          @else
                                              Please select a tier above to see pricing.
                                          @endif
                                      </div>
                                  @endif
                              @endif

                              <div class="pc-duration-wrap" data-module-id="{{ $module->id }}">
                                  <label class="pc-label">Select Duration:</label>
                                  <div class="pc-duration-grid">
                                      @foreach ($plan_durations as $duration)
                                          @php
                                              $dur = (object) [
                                                  'months' => $duration->months,
                                                  'label' => $duration->label,
                                                  'discount' => _moduleDiscount($module->id, $duration->id),
                                              ];
                                              if ($tieredModule) {
                                                  $basePrice = $activeTier ? $activeTier->price_monthly * $dur->months : 0;
                                                  $discountAmount = 0;
                                                  $finalPrice = $basePrice;
                                              } else {
                                                  $basePrice = $module->price_per_month * $dur->months;
                                                  $discountAmount = ($basePrice * $dur->discount) / 100;
                                                  $finalPrice = $basePrice - $discountAmount;
                                              }
                                          @endphp
                                          <div class="pc-duration-card" data-module-id="{{ $module->id }}"
                                              data-months="{{ $dur->months }}" data-base-price="{{ $basePrice }}"
                                              data-discount="{{ $tieredModule ? 0 : $dur->discount }}"
                                              data-discount-amount="{{ $discountAmount }}"
                                              data-final-price="{{ $finalPrice }}">
                                              <div class="pc-duration-title">{{ $dur->label }}</div>
                                              @if (!$tieredModule && $dur->discount > 0)
                                                  <div class="p5 mrp-cut">₹{{ number_format($basePrice, 2) }}</div>
                                              @endif
                                              @if (!$tieredModule)
                                                  <div class="pc-duration-cost">₹{{ number_format($finalPrice, 2) }}
                                                  </div>
                                                  <div class="pc-duration-save">
                                                      @if ($dur->discount > 0)
                                                          Save {{ $dur->discount }}%
                                                      @endif
                                                  </div>
                                              @endif
                                          </div>
                                      @endforeach
                                  </div>
                              </div>
                          </div>
                      @endforeach
                  @else
                      <p style="color: #666; text-align: center; padding: 40px;">No modules available yet. Please
                          try
                          again later.</p>
                  @endif


              </div>
              <div class="col-md-4">
                  <div class="pc-summary ">
                      <div class="pc-summary-row">
                          <span>Subtotal (Base):</span>
                          <span id="pcSubtotal">₹0.00</span>
                      </div>
                      <div class="pc-summary-row">
                          <span>Discount:</span>
                          <span id="pcDiscount">₹0.00</span>
                      </div>
                      @if (($gst_settings['gst_percent'] ?? 0) > 0)
                          <div class="pc-summary-row">
                              <span>GST ({{ $gst_settings['gst_percent'] }}%)
                                  <small
                                      class="text-muted">[{{ ($gst_settings['gst_mode'] ?? 'exclude') == 'include' ? 'Incl.' : 'Excl.' }}]</small>
                              </span>
                              <span id="pcGst">₹0.00</span>
                          </div>
                          @if (!empty($gst_settings['hsn']))
                              <div class="pc-summary-row">
                                  <span>HSN:</span>
                                  <span>{{ $gst_settings['hsn'] }}</span>
                              </div>
                          @endif
                      @endif
                      <div class="pc-summary-row pc-total">
                          <span>Total:</span>
                          <span id="pcTotal">₹0.00</span>
                      </div>
                      <div style="font-size: 13px;" class="bg-white p-2">
                          <b>Note:</b> This plan supports only 10 users.
                          Need for more users? <a href="{{ route('contact') }}">Contact us</a> for an upgrade.
                      </div>
                      <div class="pc-breakdown" id="pcBreakdown"></div>
                  </div>
                  @if (Route::currentRouteName('admin.plan.module-store'))
                      <button class="btn btn-primary btn-lg w-100 mt-2">Proceed</button>
                  @else
                      <button class="btn btn-primary btn-lg w-100 mt-2">Pay</button>
                  @endif

              </div>

          </div>
          <input type="hidden" name="selected_modules" id="selectedModulesInput">
          <input type="hidden" name="grand_total" id="grandTotalInput">
          <input type="hidden" name="gst_amount" id="gstAmountInput">
      </form>

  </div>
