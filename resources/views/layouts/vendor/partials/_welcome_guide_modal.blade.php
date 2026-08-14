{{-- First-visit welcome tour for the store owner.

     Rendered on every vendor page rather than in a dashboard view because the dashboard a
     vendor lands on depends on their saved preference (leads / POS / hospital / …), so there
     is no single view that every new vendor is guaranteed to see.

     Shown to the owner only (auth('vendor')). Staff logging in under vendor_employee get
     their own panel scope and their permissions decide what they can reach, so a tour of the
     owner's setup steps would mostly point at pages they cannot open.

     The markup is always emitted so the "Quick Start Guide" item in the account dropdown can
     re-open it later; only the auto-open depends on the flag. --}}

@php
    $wgVendor = auth('vendor')->user();
    $wgAutoOpen = $wgVendor && is_null($wgVendor->welcome_guide_seen_at);
    $wgName = $wgVendor?->f_name;

    $wgSteps = [
        [
            'icon' => '👋',
            'title' => 'Welcome' . ($wgName ? ', ' . $wgName : '') . '!',
            'lead' => 'Your store panel is ready. Here is the 60-second tour of what most businesses use in their first week.',
            'points' => [
                'Everything on the left menu is yours — the menu changes to suit your business type.',
                'Nothing here is final. You can come back and change any setting later.',
                'This guide stays available under your profile menu, top right.',
            ],
            'cta' => null,
        ],
        [
            'icon' => '🏪',
            'title' => 'Finish your store profile',
            'lead' => 'This is what customers see when they find you, so it is worth doing first.',
            'points' => [
                'Add your logo, cover photo, address and map location.',
                'Set your working hours and the phone number enquiries should reach.',
                'List the services or items you offer so you show up in the right searches.',
            ],
            'cta' => ['label' => 'Open Store Setup', 'route' => 'vendor.business-settings.store-setup'],
        ],
        [
            'icon' => '📥',
            'title' => 'Customer enquiries land in Leads',
            'lead' => 'Every enquiry from the app arrives here as a lead card.',
            'points' => [
                'Accept a new lead before its timer runs out — that unlocks the customer\'s contact details.',
                'Send your visiting charge as a confirmation request; the customer approves it from their app.',
                'Assign the job to a staff member, then start and close it with the customer\'s OTP.',
            ],
            'cta' => ['label' => 'Go to Leads', 'route' => 'vendor.service.leads_list'],
        ],
        [
            'icon' => '🧾',
            'title' => 'Bill your customer',
            'lead' => 'Raise a bill straight from a finished lead, or create one from scratch any time.',
            'points' => [
                'Quick bills for simple jobs, full GST invoices when you need them.',
                'Your invoice number format, template and terms are all configurable.',
                'Bills can be edited after they are generated, so a mistake is never final.',
            ],
            'cta' => ['label' => 'Create a Bill', 'route' => 'vendor.invoice.manual-bill'],
        ],
        [
            'icon' => '👥',
            'title' => 'Add your team',
            'lead' => 'Give your staff their own logins instead of sharing yours.',
            'points' => [
                'Each role decides exactly which pages and actions that person gets.',
                'Assign jobs to staff and see who is working on what.',
                'Attendance, leaves and salary all run from the same staff record.',
            ],
            'cta' => ['label' => 'Manage Staff', 'route' => 'vendor.employee.list'],
        ],
        [
            'icon' => '💬',
            'title' => 'When you get stuck',
            'lead' => 'You are not meant to work this out alone.',
            'points' => [
                'The Help button at the top of every page opens the assistant — ask it anything about the panel.',
                'Use the same button to request a new category, unit or anything else you need added.',
                'Prefer to talk? Call us on 9951968473.',
            ],
            'cta' => null,
        ],
    ];
    $wgLast = count($wgSteps) - 1;
@endphp

<div class="modal fade" id="welcomeGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

            <div class="modal-header"
                style="background:#18181b;border:none;padding:20px 26px;align-items:flex-start;">
                <div>
                    <h5 class="modal-title" style="color:#fff;font-weight:800;font-size:18px;margin:0;">
                        🚀 Quick Start Guide
                    </h5>
                    <small style="color:#a1a1aa;font-size:12px;">A short tour of your store panel</small>
                </div>
                <button type="button" class="close" style="color:#fff;opacity:1;text-shadow:none;"
                    data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body" style="padding:0;background:#f7f7f7;">
                <div style="height:4px;background:#e4e4e7;">
                    <div id="wgProgress" style="height:4px;background:#18181b;width:0;transition:width .2s;"></div>
                </div>

                <div style="padding:26px 28px;min-height:320px;">
                    @foreach ($wgSteps as $i => $step)
                        <div class="wg-step" data-step="{{ $i }}" style="display:{{ $i === 0 ? 'block' : 'none' }};">
                            <div style="font-size:34px;line-height:1;margin-bottom:12px;">{{ $step['icon'] }}</div>
                            <div style="font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#a1a1aa;margin-bottom:6px;">
                                Step {{ $i + 1 }} of {{ count($wgSteps) }}
                            </div>
                            <div style="font-size:20px;font-weight:800;color:#18181b;margin-bottom:8px;">
                                {{ $step['title'] }}
                            </div>
                            <div style="font-size:13.5px;color:#52525b;line-height:1.65;margin-bottom:16px;">
                                {{ $step['lead'] }}
                            </div>

                            <div style="display:flex;flex-direction:column;gap:9px;">
                                @foreach ($step['points'] as $point)
                                    <div style="display:flex;gap:11px;align-items:flex-start;background:#fff;border:1px solid #e4e4e7;border-radius:10px;padding:12px 14px;">
                                        <span style="color:#16a34a;font-weight:800;font-size:13px;line-height:1.5;">✓</span>
                                        <span style="font-size:12.5px;color:#3f3f46;line-height:1.6;">{{ $point }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($step['cta'])
                                <a href="{{ route($step['cta']['route']) }}"
                                    style="display:inline-block;margin-top:16px;padding:9px 18px;border-radius:9px;background:#fff;border:1px solid #18181b;color:#18181b;font-size:13px;font-weight:700;">
                                    {{ $step['cta']['label'] }} →
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer"
                style="background:#f7f7f7;border-top:1px solid #e4e4e7;padding:14px 26px;justify-content:space-between;align-items:center;">
                <div id="wgDots" style="display:flex;gap:6px;">
                    @foreach ($wgSteps as $i => $step)
                        <span class="wg-dot" data-go="{{ $i }}"
                            style="width:8px;height:8px;border-radius:50%;background:#d4d4d8;cursor:pointer;"></span>
                    @endforeach
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="button" id="wgSkip" class="btn btn-sm"
                        style="background:none;border:none;color:#a1a1aa;font-size:12.5px;font-weight:600;"
                        data-dismiss="modal">Skip tour</button>
                    <button type="button" id="wgBack" class="btn btn-sm"
                        style="background:#fff;border:1px solid #e4e4e7;color:#52525b;font-size:12.5px;font-weight:700;border-radius:8px;padding:7px 15px;display:none;">Back</button>
                    <button type="button" id="wgNext" class="btn btn-sm"
                        style="background:#18181b;border:none;color:#fff;font-size:12.5px;font-weight:700;border-radius:8px;padding:7px 18px;">Next</button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('script_2')
    <script>
        (function () {
            var $modal = $('#welcomeGuideModal');
            if (!$modal.length) return;

            var $steps = $modal.find('.wg-step'),
                $dots  = $modal.find('.wg-dot'),
                last   = {{ $wgLast }},
                step   = 0,
                marked = false;

            function render() {
                $steps.hide().filter('[data-step="' + step + '"]').show();
                $dots.each(function (i) {
                    $(this).css('background', i <= step ? '#18181b' : '#d4d4d8');
                });
                $('#wgProgress').css('width', ((step + 1) / (last + 1) * 100) + '%');
                $('#wgBack').toggle(step > 0);
                $('#wgNext').text(step === last ? 'Get started' : 'Next');
                $modal.find('.modal-body').scrollTop(0);
            }

            // Once the tour has been opened it does not come back on its own — whether the
            // vendor walked it to the end, skipped it or closed it. Re-opening from the
            // profile menu posts again, which the timestamp update absorbs harmlessly.
            function markSeen() {
                if (marked) return;
                marked = true;
                $.post('{{ route('vendor.welcome-guide-seen') }}', {
                    _token: $('meta[name="csrf-token"]').attr('content')
                });
            }

            $('#wgNext').on('click', function () {
                if (step === last) { $modal.modal('hide'); return; }
                step++;
                render();
            });

            $('#wgBack').on('click', function () {
                if (step > 0) { step--; render(); }
            });

            $dots.on('click', function () {
                step = parseInt($(this).data('go'), 10) || 0;
                render();
            });

            // Re-opening from the profile menu starts the tour over rather than resuming
            // wherever it was closed.
            $modal.on('show.bs.modal', function () {
                step = 0;
                render();
            });

            $modal.on('hidden.bs.modal', markSeen);

            // A CTA navigates away, so the modal never fires hidden.bs.modal — mark it here or
            // the tour reappears on the page the vendor just clicked through to.
            $modal.on('click', 'a[href]', markSeen);

            render();

            @if ($wgAutoOpen)
                $modal.modal('show');
            @endif
        })();
    </script>
@endpush
