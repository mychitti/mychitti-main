@extends('layouts.vendor.app')

@section('title', 'Available Modules')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @keyframes scaleUpThenNormal {
            0% {
                transform: scale(1);
                box-shadow: none;
            }

            50% {
                transform: scale(1.1);
                box-shadow: 0 0 20px rgba(0, 123, 255, 0.6);
                /* blue glow */
            }

            100% {
                transform: scale(1);
                box-shadow: none;
            }
        }

        .highlight {
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.6);
        }

        .scale-animate {
            animation: scaleUpThenNormal 0.6s ease-in-out;
            transition: box-shadow 0.3s ease;
        }

        .scale-animate {
            animation: scaleUpThenNormal 0.8s ease-in-out;
        }

        body {
            background-color: #f9fafb;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .wrap-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 40px;
            font-size: 24px;
        }

        .addon-layout {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .addon-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            box-sizing: border-box;
            transition: box-shadow 0.3s ease-in-out;
            height: 100%;
        }

        .addon-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .addon-logo {
            width: 40px;
            margin-bottom: 10px;
        }

        .addon-title {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .addon-subtitle {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .addon-description {
            font-size: 14px;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-secondary {
            flex: 1;
            padding: 10px 14px;
            background: linear-gradient(135deg, #ffffff, #d4f6f6);
            border: 1px solid #005555;
            color: #005555;
            cursor: pointer;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #c4eeee, #a0e0e0);
            color: #004040;
        }

        .btn-main {
            flex: 1;
            padding: 10px 14px;
            background: linear-gradient(135deg, #007f7f, #005555);
            border: none;
            color: #ffffff;
            cursor: pointer;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
            text-align: center;
        }

        .btn-main:hover {
            background: linear-gradient(135deg, #006b6b, #004444);
            color: #ffffff;

        }
    </style>
@endpush

@section('content')
    <div class="wrap-container">
        <h3 class="section-heading">Available Modules</h3>
        <div class="addon-layout">
            <div class="addon-card">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=A" alt="Account logo" class="addon-logo">
                </div>
                <div class="addon-title">Account Management</div>
                <div class="addon-subtitle">Finance Tools</div>
                <p class="addon-description">Automate and simplify financial tracking, invoicing, and account organization
                    with ease.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('2')) }}/mo</button>
                    @if (_isSubmoduleEnabled('2')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['2']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>
            <div class="addon-card" id="billing">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=A" alt="Account logo" class="addon-logo">
                </div>
                <div class="addon-title">Advanced Billing</div>
                <div class="addon-subtitle">Sales Tools</div>
                <p class="addon-description">Simplify advanced billing with automated tools and smart payment reminders.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('3')) }}/mo</button>
                    @if (_isSubmoduleEnabled('3')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['3']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>

            <div class="addon-card" id="leave-management">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=L" alt="Leave logo" class="addon-logo">
                </div>
                <div class="addon-title">Leave Management</div>
                <div class="addon-subtitle">HR Tools</div>
                <p class="addon-description">Streamline leave requests, approvals, balances, and policies to empower your
                    team.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('4')) }}/mo</button>
                    @if (_isSubmoduleEnabled('4')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['4']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>

            <div class="addon-card" id="salary-management">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=S" alt="Salary logo" class="addon-logo">
                </div>
                <div class="addon-title">Salary Management</div>
                <div class="addon-subtitle">Payroll</div>
                <p class="addon-description">Generate payslips, calculate deductions, and process payments with accuracy and
                    compliance.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('5')) }}/mo</button>
                    @if (_isSubmoduleEnabled('5')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['5']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>

            <div class="addon-card" id="attendance-management">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=AT" alt="Attendance logo" class="addon-logo">
                </div>
                <div class="addon-title">Attendance Management</div>
                <div class="addon-subtitle">Workforce Tools</div>
                <p class="addon-description">Track employee attendance, manage shifts, and gain real-time visibility on
                    workforce availability.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('6')) }}/mo</button>
                    @if (_isSubmoduleEnabled('6')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['6']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>

            <div class="addon-card" id="quotation-management">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=Q" alt="Quotation logo" class="addon-logo">
                </div>
                <div class="addon-title">Quotation Management</div>
                <div class="addon-subtitle">Sales Tools</div>
                <p class="addon-description">Easily create, share, and manage quotations to speed up sales cycles and win
                    more business.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('7')) }}/mo</button>
                    @if (_isSubmoduleEnabled('7')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['7']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>

            <div class="addon-card" id="project-management">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=PM" alt="Project logo" class="addon-logo">
                </div>
                <div class="addon-title">Project Management</div>
                <div class="addon-subtitle">Productivity Tools</div>
                <p class="addon-description">Plan, track, and manage tasks and deadlines to ensure successful project
                    delivery every time.</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('8')) }}/mo</button>
                    @if (_isSubmoduleEnabled('8')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['8']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>
            <div class="addon-card" id="inventory-management">
                <div class="d-flex justify-content-between">
                    <img src="https://dummyimage.com/40x40/000/fff&text=PM" alt="Inventory logo" class="addon-logo">
                </div>
                <div class="addon-title">{{ _moduleLabel('inventory_manage') }}</div>
                <div class="addon-subtitle">Sales Tools</div>
                <p class="addon-description">Manage your inventory with precision and ease..</p>
                <div class="action-buttons">
                    <button
                        class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('11')) }}/mo</button>
                    @if (_isSubmoduleEnabled('11')['billing'])
                        <button class="btn-secondary">Enabled</button>
                    @else
                        <a href="{{ route('vendor.sub-module.enable', ['11']) }}" class="btn-main">Enable</a>
                    @endif
                </div>
            </div>
            @if (_offeredModule('leads_manage'))
                <div class="addon-card" id="leads-management">
                    <div class="d-flex justify-content-between">
                        <img src="https://dummyimage.com/40x40/000/fff&text=LM" alt="Leads logo" class="addon-logo">
                    </div>
                    <div class="addon-title">Advanced Leads Management</div>
                    <div class="addon-subtitle">Productivity Tools</div>
                    <p class="addon-description">Streamline your lead capture, follow-ups, and conversions with our
                        advanced leads management.</p>
                    <div class="action-buttons">
                        <button
                            class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('10')) }}/mo</button>
                        @if (_isSubmoduleEnabled('10')['billing'])
                            <button class="btn-secondary">Enabled</button>
                        @else
                            <a href="{{ route('vendor.sub-module.enable', ['10']) }}" class="btn-main">Enable</a>
                        @endif
                    </div>
                </div>
                @else
                not allowd
            @endif
            @if (_offeredModule('patient_manage'))
                <div class="addon-card" id="patient-management">
                    <div class="d-flex justify-content-between">
                        <img src="https://dummyimage.com/40x40/000/fff&text=PM" alt="Project logo" class="addon-logo">
                    </div>
                    <div class="addon-title">Patient Management</div>
                    <div class="addon-subtitle">Client Tools</div>
                    <p class="addon-description">Handle patient records, appointments, and medical history</p>
                    <div class="action-buttons">
                        <button
                            class="btn-secondary">{{ \App\CentralLogics\Helpers::format_currency(_modulePrice('9')) }}/mo</button>
                        @if (_isSubmoduleEnabled('9')['billing'])
                            <button class="btn-secondary">Enabled</button>
                        @else
                            <a href="{{ route('vendor.sub-module.enable', ['9']) }}" class="btn-main">Enable</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>



@endsection

@push('script_2')
   <script>
    $(document).ready(function () {
        const urlSegments = window.location.pathname.split('/');
        const lastSegment = urlSegments.pop() || urlSegments.pop(); // handle trailing slash

        const el = $('#' + lastSegment);
        if (el.length) {
            el[0].scrollIntoView({
                behavior: 'smooth',
                block: 'end'
            });
            el.addClass('scale-animate highlight');

            // Remove animation class after it finishes
            setTimeout(() => {
                {{-- el.removeClass('scale-animate'); --}}
            }, 600);
        }
    });
</script>

@endpush
