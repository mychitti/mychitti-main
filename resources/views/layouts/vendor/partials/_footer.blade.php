
 <div class="footer">
     <div class="row justify-content-between align-items-center">
         <div class="col">
             <p class="font-size-sm mb-0">
                 &copy; {{ Str::limit(\App\CentralLogics\Helpers::get_store_data()->name, 50, '...') }}. <span
                     class="d-none d-sm-inline-block"></span>
             </p>
         </div>
         <div class="col-auto">
             <div class="d-flex justify-content-end">
                 <!-- List Dot -->
                 <ul class="list-inline list-separator">
                   @if (auth('vendor')->check())
                        {{-- <li class="list-inline-item">
                            <a class="list-separator-link"
                                href="{{ route('vendor.hospital.activity-log') }}">Activity Logs</a>
                        </li> --}} 
                        <li class="list-inline-item">
                            <a class="list-separator-link"
                                href="{{ route('vendor.terms-and-conditions.view') }}">Terms and Conditions</a>
                        </li>
                    @elseif(auth('vendor_employee')->check())
                        <li class="list-inline-item">
                            <a class="list-separator-link"
                                href="{{ route('vendor.staff.terms-n-conditions') }}">Terms and Conditions</a>
                        </li>
                    @endif
                    
                     <li class="list-inline-item">
                         <a class="list-separator-link"
                             href="{{ route('vendor.business-settings.store-setup') }}">{{ translate('messages.store_settings') }}</a>
                     </li>

                     <li class="list-inline-item">
                         <a class="list-separator-link"
                             href="{{ route('vendor.shop.view') }}">{{ translate('messages.profile') }}</a>
                     </li>

                     <li class="list-inline-item">
                         <!-- Keyboard Shortcuts Toggle -->
                         <div class="hs-unfold">
                             <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle"
                                 href="{{ route('vendor.dashboard') }}">
                                 <i class="tio-home-outlined"></i>
                             </a>
                         </div>
                         <!-- End Keyboard Shortcuts Toggle -->
                     </li>
                     <li class="list-inline-item">
                         <label class="badge badge-soft-primary m-0">
                             {{ translate('messages.software_version') }} : {{ env('SOFTWARE_VERSION') }}
                         </label>
                     </li>
                 </ul>
                 <!-- End List Dot -->
             </div>
         </div>
     </div>
 </div>
 <div class="modal fade" id="subsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Buy Supscription Plan?</h5>
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>
             <div class="modal-body">
                 <p> You have no active subscription plan yet. Purchase One?</p>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-dismiss="modal">Later</button>
                 <a href="{{ route('vendor.profile.view') }}#planDiv" class="btn btn-primary">Browse Plans</a>
             </div>
         </div>
     </div>
 </div>
