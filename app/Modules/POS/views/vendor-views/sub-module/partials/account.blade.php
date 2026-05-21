 @php
     $offer = _isSubmoduleEnabled('2')['offer'];
     $pay_warning = _isSubmoduleEnabled('2')['pay_warning'];
     $free_trial = _isSubmoduleEnabled('2')['free_trial'];
 @endphp
 @if($free_trial)
    <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>

@elseif ($offer || $pay_warning)
     <div class="background-pattern" style="background-color: #fff5fb;">
         <div class="container py-5">
             <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                 <div class="col-md-7">
                     <h1 class="display-4 font-weight-bold">Account Management.<br>Simplified.</h1>
                     <p class="lead mt-4">Let our system handle user accounts, billing, and subscription data so your
                         team can focus on growing your business.</p>
                     @if (_isSubmoduleEnabled('2')['expiring_text'])
                         <h4 class="text-danger trial_alert">{{ _isSubmoduleEnabled('2')['expiring_text'] }}</h4>
                     @endif
                 </div>
                @if($offer)

                 <div class="col-md-5">
                     <div class="highlight-box">
                         <h5 class="font-weight-bold">Just at
                             {{ \App\CentralLogics\Helpers::format_currency(_modulePrice(2)) }}/mo
                         </h5>
                         <ul class="list-unstyled mt-3 mb-4">
                             <li>✓ Manage Journal Entries</li>
                             <li>✓ Manage user profiles & account details</li>
                             <li>✓ Track billing history & invoices</li>
                             <li>✓ Secure access to account data</li>
                             <li>✓ Automated reminders & notifications</li>
                         </ul>
                         <a href="{{ route('vendor.sub-module.enable', ['1']) }}" class="btn btn-primary btn-block"
                             style="background-color: #661047 !important;">Enable Account Management</a>
                     </div>
                 </div>
                 @endif
             </div>
         </div>
     </div>
 @endif
