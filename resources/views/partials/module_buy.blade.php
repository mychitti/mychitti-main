  <div class="pc-panel pc-active" id="calculator">

      <form id="subscriptionPlanForm"
                  @if (Route::currentRouteName() == 'admin.plan.module-store')

          action="{{ env('APP_MODE') != 'demo' ? route('admin.plan.buy-module') : 'javascript:' }}"
          @else 
          action="{{ env('APP_MODE') != 'demo' ? route('vendor.profile.buy-module') : 'javascript:' }}"
          
          @endif method="post"
          enctype="multipart/form-data">
          @csrf

          <div class="row"> 
              <div class="pc-calc-section col-md-8">
                  <h2>Subscribe Modules</h2>
                  @if (Route::currentRouteName() == 'admin.plan.module-store')
                  <div class="row">
                  <div class="col-md-6">
                      <label class="form-check-label" for="flexRadioDefault2">Store</label>
                      <select data-placeholder="Select Store" required name="store_id" id="search_store_id"
                          class="form-control js-select2-custom ">
                          <option value=""></option>
                        
                      </select>
                  </div>

                      <div class="col-md-6 my-3 d-flex gap-2 align-items-center">
                          <input type="radio" value="1" name="billing" class="billing_status" id="billing"
                              checked>
                          <label for="billing" class="mb-0">Billing</label>
                          <input type="radio" value="0" name="billing" class="billing_status" id="retail">
                          <label for="retail" class="mb-0">Retail</label>
                      </div>
                  </div>

                  @endif
                  @php $plan_durations = _planDurations(); @endphp
                  <div class="pc-global-duration mb-3">
                      <label class="pc-label"><b>Select Plan Duration</b></label>
                      <div class="pc-duration-grid">
                          @foreach($plan_durations as $i => $dur)
                          <div class="pc-global-duration-card {{ $i == 0 ? 'pc-selected' : '' }}" data-months="{{ $dur->months }}">{{ $dur->label }}</div>
                          @endforeach
                      </div>
                  </div>

                  <h3 style="color: var(--primary); margin-bottom: 12px; font-size: 16px;">Select Modules &
                      Duration 
                  </h3>
                  @php $sub_modules = _subMoudles(); $gst_settings = _planGstSettings(); @endphp
                  @if (isset($sub_modules) && count($sub_modules) > 0)
                      @foreach ($sub_modules as $module)
                          <div class="pc-module-item" data-module-id="{{ $module->id }}">
                              <div class="pc-module-top">
                                  <input type="checkbox" class="pc-checkbox pc-module-check"
                                      data-module-id="{{ $module->id }}">
                                  <div class="pc-module-name">{{ $module->name }}</div>
                                  <div class="pc-price-amount">
                                      ₹{{ number_format($module->price_per_month) }}/month
                                  </div>
                              </div>
                              <div class="pc-duration-wrap" data-module-id="{{ $module->id }}">
                                  <label class="pc-label">Select Duration:</label>
                                  <div class="pc-duration-grid">
                                      @foreach ($plan_durations as $duration)
                                          @php
                                              $duration = (object) [
                                                  'months' => $duration->months,
                                                  'label' => $duration->label,
                                                  'discount' => _moduleDiscount($module->id, $duration->id),
                                              ];
                                          @endphp 
                                          @php
                                              $basePrice = $module->price_per_month * $duration->months;
                                              $discountAmount = ($basePrice * $duration->discount) / 100;
                                              $finalPrice = $basePrice - $discountAmount;
                                          @endphp
                                          <div class="pc-duration-card" data-module-id="{{ $module->id }}"
                                              data-months="{{ $duration->months }}"
                                              data-base-price="{{ $basePrice }}"
                                              data-discount="{{ $duration->discount }}"
                                              data-discount-amount="{{ $discountAmount }}"
                                              data-final-price="{{ $finalPrice }}">
                                              <div class="pc-duration-title">{{ $duration->label }}</div>
                                              <div class="p5 {{ $duration->discount > 0 ? 'mrp-cut' : '' }}">
                                                  ₹{{ number_format($basePrice, 2) }}
                                              </div>
                                              <div class="pc-duration-cost">₹{{ number_format($finalPrice, 2) }}
                                              </div>
                                              <div class="pc-duration-save">
                                                  @if ($duration->discount > 0)
                                                      Save {{ $duration->discount }}%
                                                  @else
                                                      {{-- No discount --}}
                                                  @endif
                                              </div>
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
                      @if(($gst_settings['gst_percent'] ?? 0) > 0) 
                      <div class="pc-summary-row">
                          <span>GST ({{ $gst_settings['gst_percent'] }}%)
                              <small class="text-muted">[{{ ($gst_settings['gst_mode'] ?? 'exclude') == 'include' ? 'Incl.' : 'Excl.' }}]</small>
                          </span>
                          <span id="pcGst">₹0.00</span>
                      </div>
                      @if(!empty($gst_settings['hsn']))
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
