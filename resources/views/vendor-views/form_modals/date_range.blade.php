  <div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog ">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Select Date Range</h5>
                  <button type="button" class="close inv_close_btn" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <div class="section-title">
                      <i class="fas fa-clock"></i>
                      Quick
                  </div>
                  <div class="preset-grid">
                      @php
                           $presets = [
                               'today' => 'Today',
                               'upcoming' => 'Upcoming',
                               'yesterday' => 'Yesterday',
                               'this_week' => 'This Week',
                               'last_week' => 'Last Week',
                               'this_month' => 'This Month',
                               'last_month' => 'Last Month',
                               'last_3_month' => 'Last 3 Months',
                           ];
                      @endphp

                      @foreach ($presets as $value => $label)
                          <label class="checkbox-item">
                              <input {{ $preset == $value ? 'checked' : '' }} type="radio" name="date_range"
                                  value="{{ $value }}" onchange="updateSelection()">
                              <div class="checkbox-label">{{ $label }}</div>
                          </label>
                      @endforeach
                  </div>

                  <div class="section-title">
                      <i class="fas fa-chart-line"></i>
                      Extended
                  </div>
                  <div class="preset-grid">
                      @php
                          $presetsGroup2 = [
                              'last_30_days' => 'Last 30 Days',
                              'this_year' => 'This Year',
                              'last_year' => 'Last Year',
                              'quarter' => 'Quarter',
                          ];
                      @endphp

                      @foreach ($presetsGroup2 as $value => $label)
                          <label class="checkbox-item">
                              <input type="radio" name="date_range" value="{{ $value }}"
                                  onchange="updateSelection()" {{ $preset == $value ? 'checked' : '' }}>
                              <div class="checkbox-label">{{ $label }}</div>
                          </label>
                      @endforeach
                      @php
                          $currentYear = now()->year;
                          $currentMonth = now()->month;

                          // If current month is Jan–Mar, we are still in previous FY
                          $startFY = $currentMonth <= 3 ? $currentYear - 1 : $currentYear;
                          $yearsToShow = 2; // Show 5 FYs
                      @endphp

                      @for ($i = 0; $i < $yearsToShow; $i++)
                          @php
                              $from = $startFY - $i;
                              $to = $from + 1;
                              $value = 'fy_' . substr($from, -2) . '_' . substr($to, -2);
                              $label = 'FY ' . $from . '-' . substr($to, -2);
                          @endphp

                          <label class="checkbox-item">
                              <input type="radio" value="{{ $value }}" name="date_range"
                                  onchange="updateSelection()">
                              <div class="checkbox-label">{{ $label }}</div>
                          </label>
                      @endfor
                  </div>

                  <label class="custom-section">
                      <input type="radio" name="date_range" value="custom">
                      <div class="custom-content">
                          <h6><i class="fas fa-calendar-plus me-1"></i>Custom Range</h6>
                          <input type="text" name="custom_date_range" id="daterange" class="form-control" />
                      </div>
                  </label>

                  <div class="action-buttons">
                      <div class="selected-count">
                      </div>
                      <div class="btn-group">
                          <button type="button" class="close">Cancel</button>
                      </div>
                  </div>
              </div>
              {{-- <div class="modal-body">
                                            
                                            <input type="date" value="{{ $from }}" name="from"
                                            onchange="this.form.submit()" class="form-control mx-1 mini-date">
                                            <input type="date" value="{{ $to }}" name="to"
                                            onchange="this.form.submit()" class="form-control mx-1 mini-date">
                                        </div> --}}
          </div>
      </div>
  </div>
