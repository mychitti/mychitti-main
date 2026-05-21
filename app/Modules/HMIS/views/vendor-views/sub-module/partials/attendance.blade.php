@php
    $offer = _isSubmoduleEnabled('6')['offer'];
    $pay_warning = _isSubmoduleEnabled('6')['pay_warning'];
     $free_trial = _isSubmoduleEnabled('6')['free_trial'];
 @endphp
 @if($free_trial)
    <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">Free Trial</span>

@elseif ($offer || $pay_warning)
    <div class="background-pattern" style="background-color: #f5fff7;">
        <div class="container py-5">
            <div class="row align-items-center" style="max-width: 1000px;    margin: 0 auto;">
                <div class="col-md-7">
                    <h1 class="display-4 font-weight-bold">Automated Attendance. Real-Time Insights.</h1>
                    <p class="lead mt-4">No more guesswork — just clear, automated attendance that keeps your team in
                        sync.</p>
                    @if (_isSubmoduleEnabled('6')['expiring_text'])
                        <h4 class="text-danger trial_alert">{!! _isSubmoduleEnabled('6')['expiring_text'] !!}</h4>
                    @endif
                </div>
                @if($offer)

                <div class="col-md-5">
                    <div class="highlight-box">
                        <h5 class="font-weight-bold">Just at
                            {{ \App\CentralLogics\Helpers::format_currency(_modulePrice(6)) }}/mo
                        </h5>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li>✓ Track daily employee attendance</li>
                            <li>✓ Generate attendance reports</li>
                            <li>✓ time tracking of employees</li>
                            <li>✓ Real-time notifications & reminders</li>
                        </ul>
                        <a href="{{ route('vendor.sub-module.list', ['attendance-management']) }}" class="btn btn-primary btn-block"
                            style="background-color: #116100 !important;">Enable
                            Attendance Management</a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endif
