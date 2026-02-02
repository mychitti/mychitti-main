  <div class="document-container">
      <div class="document-header">
          <h3>Service Quotation</h3>
          <span class="document-id">QT-{{ $quotation->id }}</span>
      </div>
      <div class="document-body">
          <table class="document-table table">
              <thead>
                  <tr>
                      <th style="text-align: right;">Name</th>
                      <th style="text-align: center;">Qty</th>
                      <th style="text-align: right;">Rate</th>
                      <th style="text-align: right;">Amount</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach ($quotation->items as $key => $value)
                      <tr>
                          <td>{{ $value->name }}</td>
                          <td style="text-align: center;">{{ $value->qty }}</td>
                          <td style="text-align: right;">{{ _price($value->price, 1) }}</td>
                          <td style="text-align: right;">{{ _price($value->total, 1) }}</td>
                      </tr>
                  @endforeach

                  <tr>
                      <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                      <td style="text-align: right;"><strong>{{_price($quotation->sub_total, 1)}}</strong></td>
                  </tr>
                  <tr>
                      <td colspan="3" style="text-align: right;"><strong>Tax Amount:</strong></td>
                      <td style="text-align: right;"><strong>{{_price($quotation->tax_total, 1)}}</strong></td>
                  </tr>
                  <tr class="document-total-row">
                      <td colspan="3" style="text-align: right;"><strong>Grand Total:</strong></td>
                      <td style="text-align: right;"><strong>{{_price($quotation->total, 1)}}</strong></td>
                  </tr>
              </tbody>
          </table>
      </div>
       <div class="quotation-cta_{{ $quotation->id }}">
        @include('front-views.partials.dashboard.service._quotation_cta', ['quotation' => $quotation])
    </div>
      <div class="document-alert alert-success">
          <i class="fas fa-check-circle"></i>
          <div>
              <strong>Validity:</strong><br>
              This quotation is valid for 30 days from the date of issue. Terms and conditions apply.
          </div>
      </div>
  </div>
