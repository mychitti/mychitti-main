<div class="row g-3">
    <?php
    
    $disbursement_type = \App\Models\BusinessSetting::where('key', 'disbursement_type')->first()->value ?? 'manual';
    $min_amount_to_pay_store = \App\Models\BusinessSetting::where('key', 'min_amount_to_pay_store')->first()->value ?? 0;
    
    $wallet_earning = round($wallet->total_earning - ($wallet->total_withdrawn + $wallet->pending_withdraw), 8);
    
    if ($wallet->balance > 0 && $wallet->collected_cash > 0) {
        $adjust_able = true;
    } elseif ($wallet->collected_cash != 0 && $wallet_earning != 0) {
        $adjust_able = true;
    } elseif ($wallet->balance == $wallet_earning) {
        $adjust_able = false;
    } else {
        $adjust_able = false;
    }
    
    $digital_payment = App\CentralLogics\Helpers::get_business_settings('digital_payment');
    $digital_payment = $digital_payment['status'];
    
    ?>

    @if (
        $adjust_able == true ||
            ($disbursement_type == 'manual' && $wallet->balance > 0) ||
            $wallet->balance < 0 ||
            ($wallet->collected_cash > 0 && $min_amount_to_pay_store <= $wallet->collected_cash))
        <?php
        $col_size = true;
        ?>
    @endif
    <style>
        .coupon-container {
            max-width: 400px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin: auto;
        }

        .coupon-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .arrow {
            font-size: 18px;
            transition: transform 0.3s;
        }

        .collapsed .arrow {
            transform: rotate(-90deg);
        }

        .coupon-content {
            display: none;
            overflow: hidden;
        }

        .coupon {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px;
            border-radius: 8px;
            border-left: 5px solid #d48734;
            margin-top: 10px;
            transition: 0.3s;
        }

        .coupon-code {
            font-size: 16px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 3px;
        }

        .coupon-details {
            font-size: 12px;
            color: #555;
        }

        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }

        .copy-btn:hover {
            background: #0056b3;
        }

        /* Celebration Animation */
        .celebrate {
            animation: glow 1s ease-out infinite alternate;
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 2px rgba(255, 0, 0, 0.5);
            }

            100% {
                box-shadow: 0 0 20px rgba(255, 106, 0, 0.65);
            }
        }
    </style>




    <div class="col-md-12">
        <div class="row g-3">
            <!-- Pending Requests Card Example -->
            <div class="col-sm-4">
                <div class="resturant-card  bg--1">
                    <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($wallet->total_earning) }}</h4>
                    <span class="subtitle">Total Earning</span>
                    <img class="resturant-icon"
                        src="{{ asset('/public/assets/admin/img/transactions/image_total89.png') }}" alt="public">
                </div>
            </div>

            <!-- Earnings (Monthly) Card Example -->
            <div class="col-sm-4">
                <div class="resturant-card  bg--3">
                @php $module_id = \App\CentralLogics\Helpers::get_store_data()->module_id; @endphp
                    <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($wallet->total_withdrawn) }}</h4>
                    <span class="subtitle">Total {{$module_id == 6 ? 'Expenses' : 'Withdrawn'}}</span>
                    <img class="resturant-icon"
                        src="{{ asset('/public/assets/admin/img/transactions/image_pending.png') }}" alt="public">
                </div>
            </div>


            <!-- Pending Requests Card Example -->
            @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                <div class="col-sm-4">
                    <div class="resturant-card  bg--1">
                        <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($wallet->pending_withdraw) }}
                        </h4>
                        <span class="subtitle">Pending Withdraw</span>
                        <img class="resturant-icon"
                            src="{{ asset('/public/assets/admin/img/transactions/image_total89.png') }}" alt="public">
                    </div>
                </div>
            @endif
            <!-- Pending Requests Card Example -->
            <div class="col-sm-4">
                <div class="resturant-card  bg--1">
                    <h4 class="title">{{ \App\CentralLogics\Helpers::format_currency($wallet->collected_cash) }}</h4>
                    <span class="subtitle">Collected Cash</span>
                    <img class="resturant-icon"
                        src="{{ asset('/public/assets/admin/img/transactions/image_total89.png') }}" alt="public">
                </div>
            </div>
            <div class="col-sm-4 ">
                <div class="resturant-card bg--3">

                    <div class="coupon-header collapsed" onclick="toggleCoupons()">
                        Available Coupons
                        <span class="arrow">▼</span>
                    </div>

                    <div class="coupon-content" id="couponContent">

                        {{-- coupon item  --}}
                        @php $coupons = \App\Models\ServiceCoupon::where('user_type' , 'store')->where('user_type_id' , \App\CentralLogics\Helpers::get_store_id())->get(); @endphp
                        @foreach ($coupons as $coupon)
                            <div class="coupon celebrate" data-new="true" data-id="{{ $coupon->code }}">
                                <div>
                                    <div class="coupon-code">{{ $coupon->code }}</div>
                                    <div class="coupon-details">Get Wallet Recharge worth ₹{{ $coupon->amount }} for
                                        free</div>
                                    <div class="coupon-details">Used : {{ $coupon->used_count . '/'. $coupon->use_limit }}</div>
                                </div>
                                <form action="{{ route('vendor.wallet.wallet-recharge-coupon') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="amount" value="{{ $coupon->amount }}">
                                    <input type="hidden" name="coupon" value="{{ $coupon->id }}">
                                    @if(($coupon->use_limit - $coupon->used_count) > 0)
                                    <button type="submit" class="btn btn-outline-warning btn-sm">Use</button>
                                    @else 
                                    <span class="badge badge-secondary">Used</span>
                                    @endif
                                </form>
                            </div>
                        @endforeach
                        <canvas id="confettiCanvas"></canvas>


                        {{-- coupon item  --}}


                    </div>
                </div>
            </div>

            <!-- Panding Withdraw Card Example -->
            <div class="col-sm-4">
                <div class="resturant-card shadow" style="padding-top:25px">
                    <h4 class="subtitle">Recharge Wallet</h4>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#rechargeModal">
                        Add to Wallet</button>
                    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#balance-modal">
                            Withdraw</button>
                    @endif

                    <img class="resturant-icon"
                        src="{{ asset('/public/assets/admin/img/transactions/image_total89.png') }}" alt="public">
                </div>
            </div>





            <div class="modal fade" id="rechargeModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Recharge Wallet</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @php
                            $gstStatus  = \App\Models\BusinessSetting::where('key', 'wallet_recharge_gst_status')->value('value') ?? 'included';
                            $gstPercent = \App\Models\BusinessSetting::where('key', 'wallet_recharge_gst_percent')->value('value') ?? 18;
                            $gstLabel   = $gstStatus === 'included' ? '' : '+ ' . $gstPercent . '% GST';
                        @endphp
                        <div class="modal-body">
                            <a class="btn btn-sm badge-soft-primary amount_btn_outer"><span
                                    class="amount_btn text-dark "><b>1000</b></span> <small>{{ $gstLabel }}</small></a>
                            <a class="btn btn-sm badge-soft-primary amount_btn_outer"><span
                                    class="amount_btn text-dark "><b>2000</b></span> <small>{{ $gstLabel }}</small></a>
                            <a class="btn btn-sm badge-soft-primary amount_btn_outer"><span
                                    class="amount_btn text-dark "><b>3000</b></span> <small>{{ $gstLabel }}</small></a>
                            
                            <br>
                            <br>
                            <form action="{{ route('vendor.wallet.wallet-recharge') }}" method="post">
                                @csrf
                                <input type="number" name="amount" class="form-control amount_input"
                                    placeholder="Ex: 2000">
                                <button type="submit" class="btn btn-primary my-2 ">Pay</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<div class="modal fade" id="balance-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    {{ translate('messages.withdraw_request') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true" class="btn btn--circle btn-soft-danger text-danger"><i
                            class="tio-clear"></i></span>
                </button>
            </div>

            <form action="{{ route('vendor.wallet.withdraw-request') }}" method="post">
                <div class="modal-body">
                    @csrf
                    <div class="">
                        <select class="form-control" id="withdraw_method" name="withdraw_method" required>
                            <option value="" selected disabled>{{ translate('Select_Withdraw_Method') }}
                            </option>
                            @foreach ($withdrawal_methods as $item)
                                <option value="{{ $item['id'] }}">{{ $item['method_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="" id="method-filed__div">
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="form-label">{{ translate('messages.amount') }}:</label>
                        <input type="number" name="amount" step="0.001" value="{{ abs($wallet->balance) }}"
                            class="form-control h--45px" id="" min="1"
                            max="{{ abs($wallet->balance) }}">
                    </div>
                </div>
                <div class="modal-footer pt-0 border-0">
                    <button type="button" class="btn btn--reset"
                        data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                    <button type="submit" id="submit_button"
                        class="btn btn--primary">{{ translate('messages.Submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('messages.Note') }}: </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>
            <div class="modal-body">

                <div class="form-group">
                    <p id="hiddenValue"> </p>
                </div>
            </div>
            <div class="modal-footer">
                <button id="reset_btn" type="reset" data-dismiss="modal"
                    class="btn btn-secondary">{{ translate('Close') }} </button>
            </div>
        </div>
    </div>
</div>
<!-- Content Row -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">

                <ul class="nav nav-tabs page-header-tabs pb-2">
                    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('wallet') ? 'active' : '' }}"
                                href="{{ route('vendor.wallet.index') }}">{{ translate('messages.withdraw_request') }}</a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link  {{ Request::is('wallet/wallet-payment-list') ? 'active' : '' }}"
                            href="{{ route('vendor.wallet.wallet_payment_list') }}"
                            aria-disabled="true">{{ translate('messages.Payment_history') }}</a>
                    </li>
                    @if (\App\CentralLogics\Helpers::get_store_data()->module_id == 5)
                        <li class="nav-item">
                            <a class="nav-link  {{ Request::is('wallet/disbursement-list') ? 'active' : '' }}"
                                href="{{ route('vendor.wallet.getDisbursementList') }}"
                                aria-disabled="true">{{ translate('messages.Next_Payouts') }}</a>
                        </li>
                    @endif
                </ul>

            </div>


            <script>
                function toggleCoupons() {
                    let content = document.getElementById("couponContent");
                    let header = document.querySelector(".coupon-header");

                    if (content.style.display === "block") {
                        content.style.display = "none";
                        header.classList.add("collapsed");
                    } else {
                        content.style.display = "block";
                        header.classList.remove("collapsed");
                    }
                }

                function copyCoupon(code) {
                    navigator.clipboard.writeText(code).then(() => {
                        alert("Coupon code copied: " + code);
                    });
                }
            </script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const newCoupon = document.querySelector(".coupon[data-new='true']");
                    if (!newCoupon) return; // No new coupon, exit script

                    const couponId = newCoupon.getAttribute("data-id");
                    let seenCoupons = JSON.parse(localStorage.getItem("seenCoupons")) || [];

                    if (!seenCoupons.includes(couponId)) {
                        triggerConfetti();
                        seenCoupons.push(couponId);
                        localStorage.setItem("seenCoupons", JSON.stringify(seenCoupons));
                    }

                    function triggerConfetti() {
                        const canvas = document.createElement("canvas");
                        document.body.appendChild(canvas);
                        const ctx = canvas.getContext("2d");

                        canvas.style.position = "fixed";
                        canvas.style.top = "0";
                        canvas.style.left = "0";
                        canvas.width = window.innerWidth;
                        canvas.height = window.innerHeight;
                        canvas.style.pointerEvents = "none"; // So it doesn't block clicks

                        let confetti = [];
                        const colors = ["#ff0", "#ff4500", "#ff69b4", "#00ff00", "#00ffff", "#1e90ff"];

                        for (let i = 0; i < 100; i++) {
                            confetti.push({
                                x: Math.random() * canvas.width,
                                y: Math.random() * canvas.height - canvas.height,
                                size: Math.random() * 8 + 2,
                                color: colors[Math.floor(Math.random() * colors.length)],
                                speedX: Math.random() * 2 - 1,
                                speedY: Math.random() * 3 + 2
                            });
                        }

                        function drawConfetti() {
                            ctx.clearRect(0, 0, canvas.width, canvas.height);

                            confetti.forEach((c, index) => {
                                ctx.fillStyle = c.color;
                                ctx.beginPath();
                                ctx.arc(c.x, c.y, c.size, 0, Math.PI * 2);
                                ctx.fill();
                                c.y += c.speedY;
                                c.x += c.speedX;

                                if (c.y > canvas.height) {
                                    confetti[index].y = -10;
                                    confetti[index].x = Math.random() * canvas.width;
                                }
                            });

                            requestAnimationFrame(drawConfetti);
                        }

                        drawConfetti();

                        setTimeout(() => {
                            document.body.removeChild(canvas);
                        }, 4000);
                    }
                });
            </script>
