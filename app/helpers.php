<?php

use App\CentralLogics\CouponLogic;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Store;
use App\Models\ReceivableReceipt;
use App\Models\ServiceRequest;
use App\Models\Item;
use Illuminate\Support\Facades\Http;
use App\Models\JobCard;
use App\Models\Quotation;
use App\Models\Leave;
use App\Models\AdminWallet;
use App\Models\EmployeeTimeCard;
use App\Models\DeliveryMan;
use App\Models\WalletPayment;
use App\Scopes\StoreScope;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Mail\OrderVerificationMail;
use Illuminate\Support\Facades\App;
use App\CentralLogics\CustomerLogic;
use App\Models\AcceptedServiceRequest;
use App\Models\Attendance;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DataSetting;
use App\Models\InAppNotification;
use App\Models\SocialMedia;
use App\Models\VendorEmployee;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeNotification;
use App\Models\Coupon;
use App\Models\DMVehicle;
use App\Models\Plan;
use App\Models\ServiceInvoice;
use App\Models\User;
use App\Models\Zone;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use App\Http\Controllers\Vendor\ProfileController;
use App\Models\Branch;
use App\Models\BranchInventoryItem;
use App\Models\CashBook;
use App\Models\CustomerAddress;
use App\Models\DayBook;
use App\Models\GatePass;
use App\Models\GoogleAd;
use App\Models\InServiceQuotation;
use App\Models\InventoryItem;
use App\Models\InvItemVariationDetail;
use App\Models\InvoiceItem;
use App\Models\ItemVariationDetail;
use App\Models\ManualInvoice;
use App\Models\PurchaseOrder;
use App\Models\QuotationDetailItem;
use App\Models\RequestForm;
use App\Models\RequestFormUpdate;
use App\Models\RequestRule;
use App\Models\State;
use App\Models\StoreAccount;
use App\Models\StoreBankTransaction;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreEnabledModule;
use App\Models\StoreLedgerEntry;
use App\Models\StoreReview;
use App\Models\StoreShift;
use App\Models\StoreSignature;
use App\Models\StoreTask;
use App\Models\StoreTnc;
use App\Models\StoreVoucher;
use App\Models\StoreWallet;
use App\Models\SubModule;
use App\Models\TmpWallet;
use App\Models\Tracker;
use App\Models\Unit;
use App\Models\UserAddress;
use App\Models\AuditLog;
use App\Models\StoreDocument;
use App\Models\UserRecentSearch;
use App\Models\Vendor;
use App\Models\VendorEmpJob;
use App\Models\VendorTermsCondition;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ActionLog;

if (!function_exists('translate')) {
    function translate($key, $replace = [])
    {
        if (strpos($key, 'validation.') === 0 || strpos($key, 'passwords.') === 0 || strpos($key, 'pagination.') === 0 || strpos($key, 'order_texts.') === 0) {
            return trans($key, $replace);
        }

        $key = strpos($key, 'messages.') === 0 ? substr($key, 9) : $key;
        $local = app()->getLocale();

        try {
            $lang_array = include(base_path('resources/lang/' . $local . '/messages.php'));
            $processed_key = ucfirst(str_replace('_', ' ', Helpers::remove_invalid_charcaters($key)));

            if (!array_key_exists($key, $lang_array)) {
                $lang_array[$key] = $processed_key;
                $str = "<?php return " . var_export($lang_array, true) . ";";
                file_put_contents(base_path('resources/lang/' . $local . '/messages.php'), $str);
                $result = $processed_key;
            } else {
                $result = trans('messages.' . $key, $replace);
            }
        } catch (\Exception $exception) {
            $processed_key = ucfirst(str_replace('_', ' ', Helpers::remove_invalid_charcaters($key)));
            $result = $processed_key;

            // \Log::error('Translation error: ' . $exception->getMessage());
        }

        return $result;
    }
}
function _accountLastTxn($account_id, $fyStart, $fyEnd)
{

    $txn =   StoreBankTransaction::where('store_id', Helpers::get_store_id())
        ->whereBetween('txn_date', [$fyStart, $fyEnd])
        ->where('bank_id', $account_id)
        ->latest('id') // tie-breaker if same date
        ->first();
    return $txn;
}
function _getCreatedBy($created_by, $created_by_type)
{
    $name = '';
    if ($created_by_type == 'vendor') {
        $vendor = Vendor::where('id', $created_by)->first();
        $name = $vendor ? $vendor->f_name . ' ' . $vendor->l_name : '';
    } else {
        $vendor_emp = VendorEmployee::where('id', $created_by)->first();
        $name = $vendor_emp ? $vendor_emp->f_name . ' ' . $vendor_emp->l_name : '';
    }
    return $name;
}
function _updateFormStatus($form_id, $status,  $sent_to, $remark)
{
    $update = new RequestFormUpdate();
    $update->request_form_id = $form_id;
    $update->status = $status;
    $update->remark = $remark;
    $update->updated_by = auth('vendor_employee')->check() ? Helpers::get_loggedin_user()->id : 0;
    $update->sent_to = $sent_to;
    $update->save();
}
function _costCenters()
{
    $cost_centers = StoreAccount::with('ledgerAccountType')->where('account_type', 'cost_center')->where('store_id', Helpers::get_store_id())->get();
    return $cost_centers ?? [];
}
function _sendToPermission($form_type)
{
    // prx($form_type);
    $employee = Helpers::get_loggedin_user();
    $ruleForMe = RequestRule::where('department_id', $employee->department_id)->where('form_type', $form_type)->where('role_id', $employee->employee_role_id)->first();
    if ($ruleForMe) {

        $data['employees'] = VendorEmployee::where(function ($q) use ($ruleForMe) {
            $q->where('department_id', $ruleForMe->send_to_dep_id)
                ->orWhere('employee_role_id', $ruleForMe->send_to_role_id)
                ->orWhere('id', $ruleForMe->send_to_employee_id);
        })
            ->select('id', 'f_name', 'l_name')
            ->get();
    }
    return $data['employees'] ?? [];
}
function _formWiseRulePermissions($formType)
{
    $employee = Helpers::get_loggedin_user();
    $rule = RequestRule::where('department_id', $employee->department_id)->where("role_id", $employee->employee_role_id)->where('form_type', $formType)->first();
    return $rule->permissions ?? [];
}
function _requestFormNumber()
{
    $storeId = Helpers::get_store_id();

    $lastNumber = RequestForm::where('store_id', $storeId)
        ->max('request_number');

    return $lastNumber ? $lastNumber + 1 : 1;
}

function _accountCode($ledgerTypeId, $parentId = null, $storeId = null)
{
    if (!$storeId) {
        $storeId = Helpers::get_store_id();
    }

    $lastChild = StoreAccount::where('store_id', $storeId)
        ->where('parent_id', $parentId)
        ->orderBy('id', 'desc')
        ->first();

    if ($lastChild) {
        $segments = explode('-', $lastChild->code);
        $lastSegment = intval(end($segments));
        $newSegment = str_pad($lastSegment + 1, 2, '0', STR_PAD_LEFT);
    } else {
        $newSegment = '01';
    }

    if ($parentId) {
        $parent = StoreAccount::where('store_id', $storeId)->findOrFail($parentId);
        return $parent->code . '-' . $newSegment;

    }

    return $ledgerTypeId . '-' . $newSegment;
}




if (!function_exists('collect_cash_fail')) {
    function collect_cash_fail($data)
    {
        return 0;
    }
}
if (!function_exists('collect_cash_success')) {
    function collect_cash_success($data)
    {

        try {
            $account_transaction = new AccountTransaction();
            if ($data->attribute === 'store_collect_cash_payments') {
                $store = Store::where('vendor_id', $data->attribute_id)->first();
                $store->status = 1;
                $store->save();
                $user_data = $store?->vendor;
                $current_balance = $user_data?->wallet?->collected_cash ?? 0;
                $account_transaction->from_type = 'store';
                $account_transaction->from_id = $store?->vendor?->id;
                $account_transaction->created_by = 'store';
            } elseif ($data->attribute === 'deliveryman_collect_cash_payments') {
                $user_data = DeliveryMan::findOrFail($data->attribute_id);
                $user_data->status = 1;
                $user_data->save();
                $current_balance = $user_data?->wallet?->collected_cash ?? 0;
                $account_transaction->from_type = 'deliveryman';
                $account_transaction->from_id = $user_data->id;
                $account_transaction->created_by = 'deliveryman';
            } else {
                return 0;
            }
            $account_transaction->method = $data->payment_method;
            $account_transaction->ref = $data->attribute;
            $account_transaction->amount = $data->payment_amount;
            $account_transaction->current_balance = $current_balance;

            DB::beginTransaction();
            $account_transaction->save();
            $user_data?->wallet?->decrement('collected_cash', $account_transaction->amount);
            AdminWallet::where('admin_id', Admin::where('role_id', 1)->first()->id)->increment('digital_received',  $account_transaction->amount);

            DB::commit();
        } catch (\Exception $exception) {
            info($exception->getMessage());
            DB::rollBack();
        }


        try {
            if ($data->attribute == 'deliveryman_collect_cash_payments' && config('mail.status') && Helpers::get_mail_status('cash_collect_mail_status_dm') == 1) {
                Mail::to($user_data['email'])->send(new \App\Mail\CollectCashMail($account_transaction, $user_data['f_name']));
            }
        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
        return true;
    }
}

const TELEPHONE_CODES = [
    ["name" => 'UK (+44)', "code" => '+44'],
    ["name" => 'USA (+1)', "code" => '+1'],
    ["name" => 'Algeria (+213)', "code" => '+213'],
    ["name" => 'Andorra (+376)', "code" => '+376'],
    ["name" => 'Angola (+244)', "code" => '+244'],
    ["name" => 'Anguilla (+1264)', "code" => '+1264'],
    ["name" => 'Antigua & Barbuda (+1268)', "code" => '+1268'],
    ["name" => 'Argentina (+54)', "code" => '+54'],
    ["name" => 'Armenia (+374)', "code" => '+374'],
    ["name" => 'Aruba (+297)', "code" => '+297'],
    ["name" => 'Australia (+61)', "code" => '+61'],
    ["name" => 'Austria (+43)', "code" => '+43'],
    ["name" => 'Azerbaijan (+994)', "code" => '+994'],
    ["name" => 'Bahamas (+1242)', "code" => '+1242'],
    ["name" => 'Bahrain (+973)', "code" => '+973'],
    ["name" => 'Bangladesh (+880)', "code" => '+880'],
    ["name" => 'Barbados (+1246)', "code" => '+1246'],
    ["name" => 'Belarus (+375)', "code" => '+375'],
    ["name" => 'Belgium (+32)', "code" => '+32'],
    ["name" => 'Belize (+501)', "code" => '+501'],
    ["name" => 'Benin (+229)', "code" => '+229'],
    ["name" => 'Bermuda (+1441)', "code" => '+1441'],
    ["name" => 'Bhutan (+975)', "code" => '+975'],
    ["name" => 'Bolivia (+591)', "code" => '+591'],
    ["name" => 'Bosnia Herzegovina (+387)', "code" => '+387'],
    ["name" => 'Botswana (+267)', "code" => '+267'],
    ["name" => 'Brazil (+55)', "code" => '+55'],
    ["name" => 'Brunei (+673)', "code" => '+673'],
    ["name" => 'Bulgaria (+359)', "code" => '+359'],
    ["name" => 'Burkina Faso (+226)', "code" => '+226'],
    ["name" => 'Burundi (+257)', "code" => '+257'],
    ["name" => 'Cambodia (+855)', "code" => '+855'],
    ["name" => 'Cameroon (+237)', "code" => '+237'],
    ["name" => 'Canada (+1)', "code" => '+1'],
    ["name" => 'Cape Verde Islands (+238)', "code" => '+238'],
    ["name" => 'Cayman Islands (+1345)', "code" => '+1345'],
    ["name" => 'Central African Republic (+236)', "code" => '+236'],
    ["name" => 'Chile (+56)', "code" => '+56'],
    ["name" => 'China (+86)', "code" => '+86'],
    ["name" => 'Colombia (+57)', "code" => '+57'],
    ["name" => 'Comoros (+269)', "code" => '+269'],
    ["name" => 'Congo (+242)', "code" => '+242'],
    ["name" => 'Cook Islands (+682)', "code" => '+682'],
    ["name" => 'Costa Rica (+506)', "code" => '+506'],
    ["name" => 'Croatia (+385)', "code" => '+385'],
    ["name" => 'Cuba (+53)', "code" => '+53'],
    ["name" => 'Cyprus North (+90392)', "code" => '+90392'],
    ["name" => 'Cyprus South (+357)', "code" => '+357'],
    ["name" => 'Czech Republic (+42)', "code" => '+42'],
    ["name" => 'Denmark (+45)', "code" => '+45'],
    ["name" => 'Djibouti (+253)', "code" => '+253'],
    ["name" => 'Dominica (+1767)', "code" => '+1767'],
    ["name" => 'Dominican Republic (+1809)', "code" => '+1809'],
    ["name" => 'Ecuador (+593)', "code" => '+593'],
    ["name" => 'Egypt (+20)', "code" => '+20'],
    ["name" => 'El Salvador (+503)', "code" => '+503'],
    ["name" => 'Equatorial Guinea (+240)', "code" => '+240'],
    ["name" => 'Eritrea (+291)', "code" => '+291'],
    ["name" => 'Estonia (+372)', "code" => '+372'],
    ["name" => 'Ethiopia (+251)', "code" => '+251'],
    ["name" => 'Falkland Islands (+500)', "code" => '+500'],
    ["name" => 'Faroe Islands (+298)', "code" => '+298'],
    ["name" => 'Fiji (+679)', "code" => '+679'],
    ["name" => 'Finland (+358)', "code" => '+358'],
    ["name" => 'France (+33)', "code" => '+33'],
    ["name" => 'French Guiana (+594)', "code" => '+594'],
    ["name" => 'French Polynesia (+689)', "code" => '+689'],
    ["name" => 'Gabon (+241)', "code" => '+241'],
    ["name" => 'Gambia (+220)', "code" => '+220'],
    ["name" => 'Georgia (+7880)', "code" => '+7880'],
    ["name" => 'Germany (+49)', "code" => '+49'],
    ["name" => 'Ghana (+233)', "code" => '+233'],
    ["name" => 'Gibraltar (+350)', "code" => '+350'],
    ["name" => 'Greece (+30)', "code" => '+30'],
    ["name" => 'Greenland (+299)', "code" => '+299'],
    ["name" => 'Grenada (+1473)', "code" => '+1473'],
    ["name" => 'Guadeloupe (+590)', "code" => '+590'],
    ["name" => 'Guam (+671)', "code" => '+671'],
    ["name" => 'Guatemala (+502)', "code" => '+502'],
    ["name" => 'Guinea (+224)', "code" => '+224'],
    ["name" => 'Guinea - Bissau (+245)', "code" => '+245'],
    ["name" => 'Guyana (+592)', "code" => '+592'],
    ["name" => 'Haiti (+509)', "code" => '+509'],
    ["name" => 'Honduras (+504)', "code" => '+504'],
    ["name" => 'Hong Kong (+852)', "code" => '+852'],
    ["name" => 'Hungary (+36)', "code" => '+36'],
    ["name" => 'Iceland (+354)', "code" => '+354'],
    ["name" => 'India (+91)', "code" => '+91'],
    ["name" => 'Indonesia (+62)', "code" => '+62'],
    ["name" => 'Iran (+98)', "code" => '+98'],
    ["name" => 'Iraq (+964)', "code" => '+964'],
    ["name" => 'Ireland (+353)', "code" => '+353'],
    ["name" => 'Israel (+972)', "code" => '+972'],
    ["name" => 'Italy (+39)', "code" => '+39'],
    ["name" => 'Jamaica (+1876)', "code" => '+1876'],
    ["name" => 'Japan (+81)', "code" => '+81'],
    ["name" => 'Jordan (+962)', "code" => '+962'],
    ["name" => 'Kazakhstan (+7)', "code" => '+7'],
    ["name" => 'Kenya (+254)', "code" => '+254'],
    ["name" => 'Kiribati (+686)', "code" => '+686'],
    ["name" => 'Korea North (+850)', "code" => '+850'],
    ["name" => 'Korea South (+82)', "code" => '+82'],
    ["name" => 'Kuwait (+965)', "code" => '+965'],
    ["name" => 'Kyrgyzstan (+996)', "code" => '+996'],
    ["name" => 'Laos (+856)', "code" => '+856'],
    ["name" => 'Latvia (+371)', "code" => '+371'],
    ["name" => 'Lebanon (+961)', "code" => '+961'],
    ["name" => 'Lesotho (+266)', "code" => '+266'],
    ["name" => 'Liberia (+231)', "code" => '+231'],
    ["name" => 'Libya (+218)', "code" => '+218'],
    ["name" => 'Liechtenstein (+417)', "code" => '+417'],
    ["name" => 'Lithuania (+370)', "code" => '+370'],
    ["name" => 'Luxembourg (+352)', "code" => '+352'],
    ["name" => 'Macao (+853)', "code" => '+853'],
    ["name" => 'Macedonia (+389)', "code" => '+389'],
    ["name" => 'Madagascar (+261)', "code" => '+261'],
    ["name" => 'Malawi (+265)', "code" => '+265'],
    ["name" => 'Malaysia (+60)', "code" => '+60'],
    ["name" => 'Maldives (+960)', "code" => '+960'],
    ["name" => 'Mali (+223)', "code" => '+223'],
    ["name" => 'Malta (+356)', "code" => '+356'],
    ["name" => 'Marshall Islands (+692)', "code" => '+692'],
    ["name" => 'Martinique (+596)', "code" => '+596'],
    ["name" => 'Mauritania (+222)', "code" => '+222'],
    ["name" => 'Mayotte (+269)', "code" => '+269'],
    ["name" => 'Mexico (+52)', "code" => '+52'],
    ["name" => 'Micronesia (+691)', "code" => '+691'],
    ["name" => 'Moldova (+373)', "code" => '+373'],
    ["name" => 'Monaco (+377)', "code" => '+377'],
    ["name" => 'Montserrat (+1664)', "code" => '+1664'],
    ["name" => 'Morocco (+212)', "code" => '+212'],
    ["name" => 'Mozambique (+258)', "code" => '+258'],
    ["name" => 'Myanmar (+95)', "code" => '+95'],
    ["name" => 'Namibia (+264)', "code" => '+264'],
    ["name" => 'Nauru (+674)', "code" => '+674'],
    ["name" => 'Nepal (+977)', "code" => '+977'],
    ["name" => 'Netherlands (+31)', "code" => '+31'],
    ["name" => 'New Caledonia (+687)', "code" => '+687'],
    ["name" => 'New Zealand (+64)', "code" => '+64'],
    ["name" => 'Nicaragua (+505)', "code" => '+505'],
    ["name" => 'Niger (+227)', "code" => '+227'],
    ["name" => 'Nigeria (+234)', "code" => '+234'],
    ["name" => 'Niue (+683)', "code" => '+683'],
    ["name" => 'Norfolk Islands (+672)', "code" => '+672'],
    ["name" => 'Northern Marianas (+670)', "code" => '+670'],
    ["name" => 'Norway (+47)', "code" => '+47'],
    ["name" => 'Oman (+968)', "code" => '+968'],
    ["name" => 'Palau (+680)', "code" => '+680'],
    ["name" => 'Panama (+507)', "code" => '+507'],
    ["name" => 'Papua New Guinea (+675)', "code" => '+675'],
    ["name" => 'Paraguay (+595)', "code" => '+595'],
    ["name" => 'Peru (+51)', "code" => '+51'],
    ["name" => 'Philippines (+63)', "code" => '+63'],
    ["name" => 'Poland (+48)', "code" => '+48'],
    ["name" => 'Portugal (+351)', "code" => '+351'],
    ["name" => 'Qatar (+974)', "code" => '+974'],
    ["name" => 'Reunion (+262)', "code" => '+262'],
    ["name" => 'Romania (+40)', "code" => '+40'],
    ["name" => 'Russia (+7)', "code" => '+7'],
    ["name" => 'Rwanda (+250)', "code" => '+250'],
    ["name" => 'San Marino (+378)', "code" => '+378'],
    ["name" => 'Sao Tome & Principe (+239)', "code" => '+239'],
    ["name" => 'Saudi Arabia (+966)', "code" => '+966'],
    ["name" => 'Senegal (+221)', "code" => '+221'],
    ["name" => 'Serbia (+381)', "code" => '+381'],
    ["name" => 'Seychelles (+248)', "code" => '+248'],
    ["name" => 'Sierra Leone (+232)', "code" => '+232'],
    ["name" => 'Singapore (+65)', "code" => '+65'],
    ["name" => 'Slovak Republic (+421)', "code" => '+421'],
    ["name" => 'Slovenia (+386)', "code" => '+386'],
    ["name" => 'Solomon Islands (+677)', "code" => '+677'],
    ["name" => 'Somalia (+252)', "code" => '+252'],
    ["name" => 'South Africa (+27)', "code" => '+27'],
    ["name" => 'Spain (+34)', "code" => '+34'],
    ["name" => 'Sri Lanka (+94)', "code" => '+94'],
    ["name" => 'St. Helena (+290)', "code" => '+290'],
    ["name" => 'St. Kitts (+1869)', "code" => '+1869'],
    ["name" => 'St. Lucia (+1758)', "code" => '+1758'],
    ["name" => 'Sudan (+249)', "code" => '+249'],
    ["name" => 'Suriname (+597)', "code" => '+597'],
    ["name" => 'Swaziland (+268)', "code" => '+268'],
    ["name" => 'Sweden (+46)', "code" => '+46'],
    ["name" => 'Switzerland (+41)', "code" => '+41'],
    ["name" => 'Syria (+963)', "code" => '+963'],
    ["name" => 'Taiwan (+886)', "code" => '+886'],
    ["name" => 'Tajikstan (+7)', "code" => '+7'],
    ["name" => 'Thailand (+66)', "code" => '+66'],
    ["name" => 'Togo (+228)', "code" => '+228'],
    ["name" => 'Tonga (+676)', "code" => '+676'],
    ["name" => 'Trinidad & Tobago (+1868)', "code" => '+1868'],
    ["name" => 'Tunisia (+216)', "code" => '+216'],
    ["name" => 'Turkey (+90)', "code" => '+90'],
    ["name" => 'Turkmenistan (+7)', "code" => '+7'],
    ["name" => 'Turkmenistan (+993)', "code" => '+993'],
    ["name" => 'Turks & Caicos Islands (+1649)', "code" => '+1649'],
    ["name" => 'Tuvalu (+688)', "code" => '+688'],
    ["name" => 'Uganda (+256)', "code" => '+256'],
    ["name" => 'Ukraine (+380)', "code" => '+380'],
    ["name" => 'United Arab Emirates (+971)', "code" => '+971'],
    ["name" => 'Uruguay (+598)', "code" => '+598'],
    ["name" => 'Uzbekistan (+7)', "code" => '+7'],
    ["name" => 'Vanuatu (+678)', "code" => '+678'],
    ["name" => 'Vatican City (+379)', "code" => '+379'],
    ["name" => 'Venezuela (+58)', "code" => '+58'],
    ["name" => 'Vietnam (+84)', "code" => '+84'],
    ["name" => 'Virgin Islands - British (+1284)', "code" => '+1284'],
    ["name" => 'Virgin Islands - US (+1340)', "code" => '+1340'],
    ["name" => 'Wallis & Futuna (+681)', "code" => '+681'],
    ["name" => 'Yemen (North)(+969)', "code" => '+969'],
    ["name" => 'Yemen (South)(+967)', "code" => '+967'],
    ["name" => 'Zambia (+260)', "code" => '+260'],
    ["name" => 'Zimbabwe (+263)', "code" => '+263'],
];

function smartTimeFormat($datetime)
{
    $date = Carbon::parse($datetime);
    $diffInMinutes = $date->diffInMinutes(Carbon::now());

    if ($diffInMinutes <= 10) {
        return $date->diffForHumans();
    } else {
        return $date->format('Y-m-d h:i A'); // Or customize format as needed
    }
}
function formatTimeDifference($start, $end)
{
    $startTime = new DateTime($start);
    $endTime = new DateTime($end);
    $interval = $startTime->diff($endTime);

    $parts = [];

    if ($interval->d > 0) {
        $parts[] = $interval->d . 'd';
    }
    if ($interval->h > 0) {
        $parts[] = $interval->h . 'h';
    }
    if ($interval->i > 0) {
        $parts[] = $interval->i . 'm';
    }
    if ($interval->s > 0 && empty($parts)) {
        $parts[] = $interval->s . 's';
    }

    return count($parts) > 0 ? implode(' ', $parts) : '0 seconds';
}

function invoice_payment_success($data)
{
    $invoice = ManualInvoice::find($data->attribute_id);

    $invoice->payment_status = 'Paid';
    $invoice->payment_method = 'Razorpay';
    $invoice->transaction_id = $data->transaction_id;
    $invoice->save();


    $data = _createBillPdf($invoice, 'admin');
    $invoice->update(['pdf' => $data['pdf']]);

    $moduleIds = explode(',', trim($invoice->store_module_ids, '[]'));

    $store_enabled_modules = StoreEnabledModule::where(function ($query) use ($moduleIds) {
        foreach ($moduleIds as $id) {
            $query->orWhereRaw('FIND_IN_SET(?, id)', [$id]);
        }
    })->get();

    foreach ($store_enabled_modules as $module) {
        $module->paid_on = date('Y-m-d');
        $module->save();
    }
}

function module_success($data)
{
    $module_ids = $data->attribute_id;
    $vendor_id = $data->payer_id;
    $controller = new ProfileController();
    return $controller->buyModule($vendor_id, $module_ids);
}
function plan_success($data)
{
    $plan_id = $data->attribute_id;
    $vendor_id = $data->payer_id;
    $controller = new ProfileController();
    return $controller->buyPlan($vendor_id, $plan_id);
}
function plan_failed($data)
{

    // $store_id = $data->attribute_id;

}

function getStateCodeFromPincode($pincode)
{
    // State name to state code mapping
    $stateCodes = [
        'Andaman and Nicobar Islands' => 35,
        'Andhra Pradesh' => 37,
        'Arunachal Pradesh' => 12,
        'Assam' => 18,
        'Bihar' => 10,
        'Chandigarh' => 4,
        'Chhattisgarh' => 22,
        'Dadra and Nagar Haveli and Daman and Diu' => 26,
        'Delhi' => 7,
        'Goa' => 30,
        'Gujarat' => 24,
        'Haryana' => 6,
        'Himachal Pradesh' => 2,
        'Jammu and Kashmir' => 1,
        'Jharkhand' => 20,
        'Karnataka' => 29,
        'Kerala' => 32,
        'Ladakh' => 38,
        'Lakshadweep' => 31,
        'Madhya Pradesh' => 23,
        'Maharashtra' => 27,
        'Manipur' => 14,
        'Meghalaya' => 17,
        'Mizoram' => 15,
        'Nagaland' => 13,
        'Odisha' => 21,
        'Puducherry' => 34,
        'Punjab' => 3,
        'Rajasthan' => 8,
        'Sikkim' => 11,
        'Tamil Nadu' => 33,
        'Telangana' => 36,
        'Tripura' => 16,
        'Uttar Pradesh' => 9,
        'Uttarakhand' => 5,
        'West Bengal' => 19
    ];

    // Google Maps Geocoding API
    $apiKey = \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value;
    $url = "https://maps.googleapis.com/maps/api/geocode/json";

    // Send a request to Google Maps API with the pincode
    $response = Http::get($url, [
        'address' => $pincode,  // Google accepts pincode as address
        'key' => $apiKey
    ]);

    // Check if the response is successful
    if ($response->successful()) {
        $results = $response->json('results');

        if (!empty($results)) {
            // Loop through the address components to find the state
            foreach ($results[0]['address_components'] as $component) {
                if (in_array('administrative_area_level_1', $component['types'])) {
                    $stateName = $component['long_name'];
                    $stateAbbr = $component['short_name'];
                    // prx($component);
                    // Check if the state name exists in our state codes array
                    if (array_key_exists($stateName, $stateCodes)) {
                        return ['code' => $stateCodes[$stateName], 'abbr' =>  $stateAbbr]; // Return numeric state code
                    }
                }
            }
        }
    }

    // Return null or error if no state code found
    return null;
}
function _stateName($state_id)
{
    return State::find($state_id)->state_name ?? '';
}
function formatLabel($input)
{
    return ucwords(str_replace('_', ' ', $input));
}

function _signImgById($sign_id)
{
    $sign = StoreSignature::find($sign_id);
    return $sign->image;
}
function _vendorSubscriptionPlans()
{
    $plans = Plan::where('status', 1)->get();
    return $plans;
}
function _getLocalVendorsList()
{
    $stores = Store::withoutGlobalScopes()->whereNot('id', Helpers::get_store_id())->where('module_id', 6)->get();
    return $stores;
}
function _getVendorsList()
{
    $stores = StoreCustomer::where('store_id', Helpers::get_store_id())->where('user_type', 'vendor')->get();
    return $stores;
}
function canAddBranch($storeId, $type, $excludeId = null)
{
    $query = Branch::where('store_id', $storeId);

    if ($excludeId) {
        $query->where('id', '!=', $excludeId);
    }

    $totalBranches = (clone $query)->count();
    $mainCount = (clone $query)->where('type', 'main')->count();
    $subCount = (clone $query)->where('type', 'sub')->count();

    // total max 3
    if ($totalBranches >= 3) {
        return [false, 'Maximum 3 branches are allowed in total.'];
    }

    if ($type === 'main' || $type === 'Main') {
        if ($mainCount >= 1) {
            return [false, 'Only 1 main branch is allowed.'];
        }
    }

    if ($type === 'sub' || $type === 'Sub') {
        if ($mainCount >= 1 && $subCount >= 2) {
            return [false, 'Only 2 sub-branches are allowed when a main exists.'];
        }
    }

    return [true, null]; // ✅ allowed
}
function _createBillPdfOld($invoice, $from, $shipping_address_id = null, $renderOnly = false, $quotation = false, $heading = '')
{
    $shipping_address =  null;
    $bill_to = null;
    $bill_data['template_type'] = $quotation ? 'quotation' : 'invoice';
    $bill_data['tax_type'] = $invoice->tax_type ?? 'non-gst';
    $bill_data['module_id'] = $invoice->module_id;
    $bill_data['total_amount'] = $invoice->total_amount;
    $bill_data['invoice_number'] = $invoice->invoice_id;
    $bill_data['invoice_date'] = date('d M Y');
    $bill_data['payment_method'] =  $invoice->payment_method;
    $bill_data['footer_text'] =  Helpers::get_settings('footer_text');
    $bill_data['vendor_typ'] = '';
    $bill_data['heading'] = $heading;
    if ($quotation == true) {
        $bill_data['invoice_items'] = QuotationDetailItem::where('quotation_det_id', $invoice->id)->get();
    } else {
        $bill_data['invoice_items'] = InvoiceItem::with('unitId')->where('rand_invoice_id', $invoice->invoice_id)->get();
    }

    if ($invoice->bill_to_type == 'user' && $invoice->bill_to) {
        $bill_to['address'] = '';
        $shipping_address = new \stdClass();
        if ($invoice->bill_to) {
            if (($invoice->user_type == 'store_user' || $invoice->user_type == 'store_vendor')) {
                $uDetails = StoreCustomer::with('billing_address', 'shipping_address')->where('id', $invoice->bill_to)->first();
                $sAddr = $uDetails->shipping_address;
                $bAddr = $uDetails->billing_address;
                if ($bAddr) {
                    $bill_to['address']  = $bAddr->address1 . ', ' . $bAddr->address2 . ', ' . $bAddr->state . ', ' . $bAddr->city . '- ' . $bAddr->pincode;
                }
                if ($sAddr) {
                    $shipping_address->address = $sAddr->address1 . ', ' . $sAddr->address2 . ', ' . $sAddr->state . ', ' . $sAddr->city . '- ' . $sAddr->pincode;
                    $shipping_address->contact_person_name = $uDetails->f_name;
                    $shipping_address->email  = $uDetails->email;
                    $shipping_address->contact_person_number  = $uDetails->phone;
                }
            } else {
                $uDetails = User::find($invoice->bill_to);
                $userDefaultAddr = CustomerAddress::where('user_id', $invoice->bill_to)->first();
                if ($userDefaultAddr) {
                    $bill_to['address'] = $userDefaultAddr->address;
                } else {
                    $bill_to['address'] = $uDetails->address;
                }
            }

            $bill_to['full_name'] = $uDetails->f_name . ' ' . $uDetails->l_name;
            $bill_to['gst'] = $uDetails->gst;
            $pin_code = $uDetails->pin_code;

            if (!$pin_code) {
                if (preg_match('/\b\d{6}\b/', $uDetails->address, $matches)) {
                    $pin_code = $matches[0];
                }
            }
            $bill_to['state_code'] = getStateCodeFromPincode($pin_code);
            $bill_to['pin_code'] = $pin_code;

            if ($shipping_address_id) {
                $shipping_address = CustomerAddress::where('id', $shipping_address_id)->first();
                if ($shipping_address) {
                    $pin_code = getPincodeFromCoordinates($shipping_address->latitude, $shipping_address->longitude);
                    $bill_to['state_code'] = getStateCodeFromPincode($pin_code);
                    $bill_to['pin_code'] = $pin_code;
                }
            }
            $bill_to['phone'] = $uDetails->phone;
            $bill_to['email'] = $uDetails->email;
        }
        // $bill_data['pin_code2'] = $pin_code . ' condtion 2';
    } elseif ($invoice->bill_to_type == 'vendor') {
        $uDetails = DB::table('stores')->where('id', $invoice->bill_to)->first();
        $pin_code = $uDetails->pin_code;
        if (!$pin_code) {
            if (preg_match('/\b\d{6}\b/', $uDetails->address, $matches)) {
                $pin_code = $matches[0];
            } else {
                $pin_code = $uDetails->address; // Default pin code if not found
            }
        }
        // prx($pin_code);
        $bill_data['vendor_typ'] = $uDetails->module_id == 5 ? 'shop' : 'service';
        $bill_to['full_name'] = $uDetails->name;
        $bill_to['address'] = $uDetails->address;
        $bill_to['gst'] = $uDetails->gst_number;
        $bill_to['state_code'] = getStateCodeFromPincode($pin_code);
        $bill_to['pin_code'] = $pin_code;
        $bill_to['phone'] = $uDetails->phone;
        $bill_to['email'] = $uDetails->email;
        $bill_data['pin_code2'] = $bill_to['state_code'];
    }

    if ($from == 'vendor') {
        $store = Store::find($invoice->vendor_id);
        $bill_from_type = 'vendor';
        $quote_tnc = StoreTnc::where('store_id', $store->id)->where('tnc_type', 'quotation')->first();
        $bill_data['quote_tnc'] = $quote_tnc ? $quote_tnc->content :  '';
        $bill_data['store'] = $store;
        $bill_data['vendor_typ'] = $store->module_id == 5 ? 'shop' : 'service';
        $bill_from['id'] = $store->id;
        $bill_from['logo'] = $store->logo;
        $bill_from['name'] = $store->name;
        $bill_from['gst'] = $store->gst;
        $bill_from['phone'] = $store->phone;
        $bill_from['email'] = $store->email;
        $bill_from['address'] = $store->address;
        $bill_from['state_code'] = getStateCodeFromPincode($store->pin_code);
        $bill_from['pin_code'] = $store->pin_code;
        $bill_from['cin_number'] = null;
        if ($store->gst_number || ($store->gst && json_decode($store->gst)->status)) {
            $bill_data['tax_type'] = $invoice->tax_type ?? 'non-gst';
        } else {
            $bill_data['tax_type'] = 'non-gst';
        }
    } else {
        $bill_from_type = 'admin';
        $bill_from['id'] = 0;
        $bill_from['name'] = BusinessSetting::where('key', 'business_name')->first()->value;
        $bill_from['gst'] = BusinessSetting::where('key', 'gst_number')->first()->value;
        $bill_from['phone'] = BusinessSetting::where('key', 'phone')->first()->value;
        $bill_from['email'] = BusinessSetting::where('key', 'email_address')->first()->value;
        $bill_from['address'] = BusinessSetting::where('key', 'address')->first()->value;
        $bill_from['cin_number'] = BusinessSetting::where('key', 'cin_number')->first()->value;
        $bill_from['pin_code'] = BusinessSetting::where('key', 'pin_code')->first()->value;
        $bill_from['state_code'] = getStateCodeFromPincode(BusinessSetting::where('key', 'pin_code')->first()->value);
    }

    // day book entry
    $existsDayBookEntry = DayBook::where('invoice_id', $invoice->id)->first();
    $entry = false;

    if (!$quotation && !$renderOnly  && $invoice->payment_status == 'Paid' && !$existsDayBookEntry) {
        if ($invoice->bill_to_type == 'vendor'  && !$invoice->vendor_id) { // admin to vendor
            $type = 'debit';
            $store_id = $invoice->bill_to;
            $particular = 'Purchase';
            $entry = true;
        } elseif ($invoice->bill_to_type == 'user' && $invoice->vendor_id) { // vendor to user
            $type = 'credit';
            $store_id = $invoice->vendor_id;
            $particular = 'Sales';
            $entry = true;
        }
        if ($entry) {
            _saveDayBookEntry($invoice->total_amount, $type, $store_id, $particular, $invoice->id, null);
        }
    }

    $bill_gst_type = 'cgst_sgst';
    if ($bill_to) {
        if (
            isset($bill_from['state_code']) &&
            $bill_from['state_code'] &&
            isset($bill_to['state_code']) &&
            $bill_to['state_code'] &&
            $bill_from['state_code'] != $bill_to['state_code']
        ) {
            $bill_gst_type = 'igst';
        }
    }


    $totalTaxAmount = 0;

    foreach ($bill_data['invoice_items'] as $key => $qt) {
        $totalTaxAmount += _taxPrice($qt->price * $qt->qty, $qt->tax, 'actual');
    }

    $invoice->bill_gst_type = $bill_gst_type;
    $invoice->final_tax = $invoice->final_tax ??  $totalTaxAmount;
    $invoice->save();

    // day book entry end

    $tempDir = storage_path('app/mpdf_temp');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0775, true);
    }

    $mpdf = new Mpdf([
        'tempDir' => $tempDir,
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
    ]);
    $bill_from_type = $bill_from_type . '_to_' . $invoice->bill_to_type;

    $html = View::make('invoice_template.service_n_manual', compact('invoice', 'bill_from', 'bill_to', 'bill_data', 'bill_from_type', 'shipping_address'))->render();

    if ($renderOnly) {
        return $html;
    }
    $mpdf->WriteHTML($html);
    $pdfName = 'invoice_' . date('YmdHis') . rand(100000, 999999) . '.pdf';

    $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/invoice', $pdfName);

    $data['pdf'] = $pdfName;
    $data['url'] = asset('storage/app/public/invoice') . '/' . $pdfName;

    return $data;
}
if (!function_exists('generatePassword')) {
    function generatePassword($length = 12)
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>?';

        // Ensure at least one of each category
        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill remaining length with random mix (no spaces)
        $all = $upper . $lower . $numbers . $symbols;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle to mix character types
        return str_shuffle($password);
    }
}


function _onlyStoreAddEdit()
{
    $permission = auth('admin')->user()->role->modules;
    $perm_array = (array) json_decode($permission, true); // decode as associative array

    $values = array_values($perm_array);

    if (count($values) === 1 && in_array('store_add_edit', $values)) {
        return true;
    }

    return false;
}

function _isEnabled($column)
{
    $store_id = Helpers::get_store_id();
    $store_config = StoreConfig::where('store_id', $store_id)->first();
    if ($store_config && $store_config[$column] == 0) {
        return false;
    }
    return true;
}
function processTableForMPDF($html)
{
    // Load HTML into DOMDocument
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);

    // Wrap content to avoid adding doctype
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Find all tables
    $tables = $dom->getElementsByTagName('table');

    foreach ($tables as $table) {
        // Add width: 100% to table
        $tableStyle = $table->getAttribute('style');
        if (strpos($tableStyle, 'width') === false) {
            $newStyle = trim($tableStyle) . '; width: 100%; border-collapse: collapse;';
            $table->setAttribute('style', $newStyle);
        }

        // Get all rows
        $rows = $table->getElementsByTagName('tr');

        if ($rows->length > 0) {
            // Get first row to count columns
            $firstRow = $rows->item(0);
            $cells = $firstRow->getElementsByTagName('td');

            // If no td, check for th
            if ($cells->length === 0) {
                $cells = $firstRow->getElementsByTagName('th');
            }

            $columnCount = $cells->length;

            if ($columnCount > 0) {
                $width = (100 / $columnCount) . '%';

                // Apply width to all cells in all rows
                foreach ($rows as $row) {
                    // Process td cells
                    $tdCells = $row->getElementsByTagName('td');
                    foreach ($tdCells as $cell) {
                        $cellStyle = $cell->getAttribute('style');
                        // Remove existing width if present
                        $cellStyle = preg_replace('/width\s*:\s*[^;]+;?/i', '', $cellStyle);
                        $newCellStyle = trim($cellStyle) . '; width: ' . $width . ';';
                        $cell->setAttribute('style', $newCellStyle);
                    }

                    // Process th cells
                    $thCells = $row->getElementsByTagName('th');
                    foreach ($thCells as $cell) {
                        $cellStyle = $cell->getAttribute('style');
                        $cellStyle = preg_replace('/width\s*:\s*[^;]+;?/i', '', $cellStyle);
                        $newCellStyle = trim($cellStyle) . '; width: ' . $width . ';';
                        $cell->setAttribute('style', $newCellStyle);
                    }
                }
            }
        }
    }

    // Get body content only (remove added wrapper tags)
    $body = $dom->getElementsByTagName('body')->item(0);
    $html = '';
    if ($body) {
        foreach ($body->childNodes as $node) {
            $html .= $dom->saveHTML($node);
        }
    } else {
        $html = $dom->saveHTML();
    }

    return $html;
}



function _masterLedgerEntry($data,   $credit_account, $debit_account, $debit_entity_type, $credit_entity_type, $existing_voucher_id = null, $store_id = null)
{


    try {
        $storeId   = $store_id ?? Helpers::get_store_id();
        $user      = Helpers::get_loggedin_user();
        $user_type = auth('vendor')->check() ? 'vendor' : (auth('vendor_employee')->check() ? 'employee' : '');

        if ($existing_voucher_id) {
            $voucher = StoreVoucher::find($existing_voucher_id);
        } else {
            $voucherNo = Helpers::_generateVoucherNumber($storeId);

            Log::info('User details:', ['user' => $user]);

            $voucher = StoreVoucher::create([
                'store_id'        => $storeId,
                'voucher_number'  => $voucherNo,
                'voucher_type'    => $data['voucher_type'] ?? 'Payment',
                'voucher_date'    => $data['date'] ?? now(),
                'total_amount'    => $data['amount'],
                'narration'       => $data['description'] ?? $data['voucher_type'],
                'request_no'      => null,
                'status'          => $data['status'] ?? 'pending',
                'maintanace_id'   => $data['maintanace_id'] ?? '',
                'invoice_id'      => $data['invoice_id'] ?? '',
                'completed_at'    => ($data['status'] ?? '' == 'approved') ? ($data['date'] ?? now()) : null,
                'debit_entity_type' => $debit_entity_type,
                'credit_entity_type' => $credit_entity_type,
                'created_by'      => $user ? $user->id : 0,
                'created_by_type' => $user_type,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $file = null;
        if (isset($data['file']) && $data['file']) {
            $extension = $data['file']->getClientOriginalExtension();
            $file = Helpers::upload('store/docs/', $extension, $data['file']);
        }

        $entries = [
            [
                'store_id'        => $storeId,
                'voucher_type'    => $voucher->voucher_type,
                'voucher_id'      => $voucher->id,
                'entry_date'      => $data['date'] ?? $voucher->voucher_date,
                'account_id'      => $debit_account->id,
                'debit'           => $data['amount'],
                'credit'          => 0.00,
                'narration'       => $data['description'] ?? '',
                'request_no'      => $voucher->request_no,
                'status'          => $data['status'] ?? 'pending',
                'completed_at'    => $data['status'] ?? '' == 'approved' ? ($data['date'] ?? now()) : null,
                'gst_amount'      => $data['gst_amount'] ?? 0,
                'payment_mode'    => $data['payment_mode'] ?? '',
                'note'            => $data['note'] ?? '',
                'document'        => $file,
                'created_by'      => $user ? $user->id : 0,
                'created_by_type' => $user_type,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            [
                'store_id'        => $storeId,
                'voucher_type'    => $voucher->voucher_type,
                'voucher_id'      => $voucher->id,
                'entry_date'      => $data['date'] ?? $voucher->voucher_date,
                'account_id'      => $credit_account->id,
                'debit'           => 0.00,
                'credit'          => $data['amount'],
                'narration'       => $data['description'] ?? '',
                'request_no'      => $voucher->request_no,
                'status'          => $data['status'] ?? 'pending',
                'completed_at'    => $data['status'] ?? '' == 'approved' ? ($data['date'] ?? now()) : null,
                'gst_amount'      => $data['gst_amount'] ?? 0,
                'payment_mode'    => $data['payment_mode'] ?? '',
                'note'            => $data['note'] ?? '',
                'document'        => $file,
                'created_by'      =>  $user ? $user->id : 0,
                'created_by_type' => $user_type,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        foreach ($entries as $entry) {
            $exists = StoreLedgerEntry::where('voucher_id', $voucher->id)
                ->where('account_id', $entry['account_id'])
                ->where('store_id', $storeId)
                ->exists();

            if (!$exists) {
                StoreLedgerEntry::create($entry);
            }
        }
    } catch (Exception $th) {
        // print_r($th);
        return null;
        // Log::error('masterLedgerEntry failed: ' . $th->getMessage());
    }
    return $voucher;
}
function _userByLedgerAccountId($ledger_account_id)
{
    $user = StoreCustomer::where('ledger_account_id', $ledger_account_id)->where('store_id', Helpers::get_store_id())->first();
    return $user;
}
function _quickActions()
{
    $quickActions = DB::table('store_menu_visibility')
        ->join('menu', 'menu.slug', 'store_menu_visibility.menu_key')
        ->where('store_menu_visibility.store_id', Helpers::get_store_id())
        ->where('store_menu_visibility.menu_type', 'quick_action')
        ->where('menu.status', '1')
        ->select('menu.name', 'menu.route')
        ->get();

    return $quickActions;
}
function selected_menu($key, $menu_type = 'sidebar')
{
    // check if vendor has menu preference saved 
    $saved_pref = DB::table('store_menu_visibility')
        ->where('store_id', Helpers::get_store_id())
        ->where('menu_type', $menu_type)
        ->exists();
    if ($saved_pref) {
        return DB::table('store_menu_visibility')
            ->where('store_id', Helpers::get_store_id())
            ->where('menu_key', $key)
            ->where('is_visible', 1)
            ->where('menu_type', $menu_type)
            ->exists();
    }
    // default options
    elseif ($menu_type == 'sidebar') {
        return DB::table('menu')
            ->where('slug', $key)
            ->where('default', 1)
            ->exists();
    } else {
        return false;
    }
}
function _subMoudles()
{
    return DB::table('sub_modules')->get();
}
function _createBillPdf($invoice, $from, $shipping_address_id = null, $renderOnly = false, $quotation = false, $heading = '')
{
    // to show task id on invoice
    $task_id = '';
    $quote = null;
    if ($invoice->quotation_id) {
        $quote = Quotation::where('id', $invoice->quotation_id)->first();
        if ($quote) {
            $task = StoreTask::where('id', $quote->task_id)->first();
            $task_id = $task ? $task->task_id : '';
        }
    } elseif ($invoice->task_id) {
        $task = StoreTask::where('id', $invoice->task_id)->first();
        $task_id = $task ? $task->task_id : '';
    }

    $bill_data = [
        'template_type' => $quotation ? 'quotation' : 'invoice',
        'tax_type' => $invoice->tax_type ?? 'non-gst',
        'module_id' => $invoice->module_id,
        'total_amount' => $invoice->total_amount,
        'invoice_number' => $invoice->invoice_id,
        'quotation_id' => $quote?->quotation_id ?? null,
        'invoice_date' => date('d M Y'),
        'payment_method' => $invoice->payment_method,
        'footer_text' => Helpers::get_settings('footer_text'),
        'vendor_typ' => '',
        'heading' => $heading,
        'quote_tnc' => '',
        'task_id' => $task_id,
    ];

    $bill_data['invoice_items'] = $quotation
        ? QuotationDetailItem::where('quotation_det_id', $invoice->id)->get()
        : InvoiceItem::with('unitId')->where('rand_invoice_id', $invoice->invoice_id)->get();

    [$bill_to, $shipping_address] = processBillToInfo($invoice, $shipping_address_id);

    $bill_from = processBillFromInfo($invoice, $from, $bill_data);

    handleDayBookEntry($invoice, $quotation, $renderOnly);

    $bill_gst_type = determineBillGstType($bill_from, $bill_to);
    $tax = calculateTax($bill_data['invoice_items'], $bill_to, $bill_from, $invoice);

    $dataToUpdate = [
        'bill_gst_type' => $bill_gst_type,
    ];

    $invoice->additional_charges;
    if ($invoice->tax_type == 'gst') {
        $dataToUpdate['final_tax'] = $invoice->final_tax ?? $tax['total_tax'];
        $dataToUpdate['cgst'] = $bill_gst_type == 'cgst_sgst' ? ($tax['total_tax'] / 2) : 0;
        $dataToUpdate['sgst'] = $bill_gst_type == 'cgst_sgst' ? ($tax['total_tax'] / 2) : 0;
        $dataToUpdate['igst'] = $bill_gst_type == 'igst' ? $tax['total_tax'] : 0;
        $dataToUpdate['taxable_amount'] = $invoice->total_amount - $tax['total_tax'];
    } else {
        $dataToUpdate['taxable_amount'] = $invoice->total_amount;
    }

    $invoice->update($dataToUpdate);

    return generatePdf($invoice, $bill_from, $bill_to, $bill_data, $from, $shipping_address, $renderOnly);
}

function processBillToInfo($invoice, $shipping_address_id)
{
    $bill_to = null;
    $shipping_address = null;

    if ($invoice->bill_to_type === 'user' && $invoice->bill_to) {
        $bill_to = processUserBillTo($invoice, $shipping_address_id);
        $shipping_address = processShippingAddress($invoice, $shipping_address_id);
    } elseif ($invoice->bill_to_type === 'vendor') {
        $bill_to = processVendorBillTo($invoice);
    }

    return [$bill_to, $shipping_address];
}

function processUserBillTo($invoice, $shipping_address_id)
{
    $bill_to = ['address' => ''];

    if ($invoice->user_type === 'store_user' || $invoice->user_type === 'store_vendor') {
        $uDetails = StoreCustomer::with(['billing_address', 'shipping_address'])
            ->findOrFail($invoice->bill_to);

        if ($uDetails->billing_address) {
            $bAddr = $uDetails->billing_address;
            $bill_to['address'] = implode(', ', array_filter([
                $bAddr->address1,
                $bAddr->address2,
                $bAddr->state,
                $bAddr->city . '- ' . $bAddr->pincode
            ]));
        }
    } else {
        $uDetails = User::findOrFail($invoice->bill_to);
        $userDefaultAddr = CustomerAddress::where('user_id', $invoice->bill_to)->first();
        $bill_to['address'] = $userDefaultAddr ? $userDefaultAddr->address : $uDetails->address;
    }

    $pin_code = extractPinCode($uDetails, $shipping_address_id);

    return array_merge($bill_to, [
        'full_name' => $uDetails->f_name . ' ' . $uDetails->l_name,
        'gst' => $uDetails->gst,
        'state_code' => getStateCodeFromPincode($pin_code),
        'pin_code' => $pin_code,
        'phone' => $uDetails->phone,
        'email' => $uDetails->email,
    ]);
}

function processShippingAddress($invoice, $shipping_address_id)
{
    if ($invoice->user_type === 'store_user' || $invoice->user_type === 'store_vendor') {
        $uDetails = StoreCustomer::with('shipping_address')->find($invoice->bill_to);

        if ($uDetails && $uDetails->shipping_address) {
            $sAddr = $uDetails->shipping_address;
            return (object) [
                'address' => implode(', ', array_filter([
                    $sAddr->address1,
                    $sAddr->address2,
                    $sAddr->state,
                    $sAddr->city . '- ' . $sAddr->pincode
                ])),
                'contact_person_name' => $uDetails->f_name,
                'email' => $uDetails->email,
                'contact_person_number' => $uDetails->phone,
            ];
        }
    }

    if ($shipping_address_id) {
        return CustomerAddress::find($shipping_address_id);
    }

    return new \stdClass();
}

function processVendorBillTo($invoice)
{
    $uDetails = DB::table('stores')->where('id', $invoice->bill_to)->first();
    $pin_code = extractPinCodeFromStore($uDetails);

    return [
        'full_name' => $uDetails->name,
        'address' => $uDetails->address,
        'gst' => $uDetails->gst_number,
        'state_code' => getStateCodeFromPincode($pin_code),
        'pin_code' => $pin_code,
        'phone' => $uDetails->phone,
        'email' => $uDetails->email,
        'logo' => $uDetails->logo,
    ];
}

function processBillFromInfo($invoice, $from, &$bill_data)
{
    if ($from === 'vendor') {
        return processVendorBillFrom($invoice, $bill_data);
    } else if ($from === 'store_vendor') {
        return processStoreVendorBillFrom($invoice, $bill_data);
    } else {
        return processAdminBillFrom();
    }
}

function processVendorBillFrom($invoice, &$bill_data)
{
    $store = Store::findOrFail($invoice->vendor_id);

    // Get quotation T&C in single query
    $quote_tnc = StoreTnc::where(['store_id' => $store->id, 'tnc_type' => 'quotation'])->first();
    $bill_data['quote_tnc'] = $quote_tnc ? $quote_tnc->content : '';
    $bill_data['store'] = $store;
    $bill_data['vendor_typ'] = $store->module_id == 5 ? 'shop' : 'service';

    // Determine tax type
    $bill_data['tax_type'] = ($store->gst_number || ($store->gst && json_decode($store->gst)->status))
        ? ($invoice->tax_type ?? 'non-gst')
        : 'non-gst';

    return [
        'id' => $store->id,
        'logo' => $store->logo,
        'name' => $store->name,
        'gst' => $store->gst,
        'phone' => $store->phone,
        'email' => $store->email,
        'address' => $store->address,
        'state_code' => getStateCodeFromPincode($store->pin_code),
        'pin_code' => $store->pin_code,
        'cin_number' => null,
    ];
}
function processStoreVendorBillFrom($invoice, &$bill_data)
{
    $store = StoreCustomer::where('id', $invoice->store_vendor_id)->first();

    // Get quotation T&C in single query
    $bill_data['quote_tnc'] =  '';
    $bill_data['store'] = $store;
    $bill_data['vendor_typ'] =  'service';

    // Determine tax type
    $bill_data['tax_type'] = ($store->gst)
        ? ($invoice->tax_type ?? 'non-gst')
        : 'non-gst';
    $pin_code = $store->pin_code;
    if (!$pin_code) {
        $address = UserAddress::where('user_type', 'store_vendor')->where('user_id', $store->id)->first();
        if ($address) {
            if (!$pin_code) {
                $pin_code =  $address ? $address->pincode : null;
            }
            $u_address =   $address->address1 . ' ' . $address->address2 . ' ' . $address->state . ' ' . $address->city . ' ' . $address->pincode;
        }
    }
    $u_address = $u_address ?? $store->address;

    return [
        'id' => $store->id,
        'logo' => $store->logo,
        'name' => $store->f_name,
        'gst' => $store->gst,
        'phone' => $store->phone,
        'email' => $store->email,
        'address' => $u_address,
        'state_code' => getStateCodeFromPincode($pin_code),
        'pin_code' => $pin_code,
        'cin_number' => null,
    ];
}

function processAdminBillFrom()
{
    // Cache business settings to avoid multiple queries
    static $businessSettings = null;

    if ($businessSettings === null) {
        $settings = BusinessSetting::whereIn('key', [
            'business_name',
            'gst_number',
            'phone',
            'email_address',
            'address',
            'cin_number',
            'pin_code'
        ])->pluck('value', 'key');

        $businessSettings = $settings->toArray();
    }

    $pin_code = $businessSettings['pin_code'] ?? '';

    return [
        'id' => 0,
        'name' => $businessSettings['business_name'] ?? '',
        'gst' => $businessSettings['gst_number'] ?? '',
        'phone' => $businessSettings['phone'] ?? '',
        'email' => $businessSettings['email_address'] ?? '',
        'address' => $businessSettings['address'] ?? '',
        'cin_number' => $businessSettings['cin_number'] ?? '',
        'pin_code' => $pin_code,
        'state_code' => getStateCodeFromPincode($pin_code),
    ];
}

function extractPinCode($uDetails, $shipping_address_id)
{
    $pin_code = $uDetails->pin_code;

    if (!$pin_code && preg_match('/\b\d{6}\b/', $uDetails->address, $matches)) {
        $pin_code = $matches[0];
    }

    if ($shipping_address_id) {
        $shipping_address = CustomerAddress::find($shipping_address_id);
        if ($shipping_address) {
            $pin_code = getPincodeFromCoordinates($shipping_address->latitude, $shipping_address->longitude);
        }
    }

    return $pin_code;
}

function extractPinCodeFromStore($storeDetails)
{
    if ($storeDetails->pin_code) {
        return $storeDetails->pin_code;
    }

    if (preg_match('/\b\d{6}\b/', $storeDetails->address, $matches)) {
        return $matches[0];
    }

    return $storeDetails->address; // Fallback
}

function handleDayBookEntry($invoice, $quotation, $renderOnly)
{
    if ($quotation || $renderOnly || $invoice->payment_status !== 'Paid') {
        return;
    }

    $existsDayBookEntry = DayBook::where('invoice_id', $invoice->id)->exists();
    if ($existsDayBookEntry) {
        return;
    }

    $entry_data = null;

    if ($invoice->bill_to_type === 'vendor' && !$invoice->vendor_id) {
        // Admin to vendor
        $entry_data = ['debit', $invoice->bill_to, 'Purchase'];
    } elseif ($invoice->bill_to_type === 'user' && $invoice->vendor_id) {
        // Vendor to user
        $entry_data = ['credit', $invoice->vendor_id, 'Sales'];
    }

    if ($entry_data) {
        _saveDayBookEntry($invoice->total_amount, $entry_data[0], $entry_data[1], $entry_data[2], $invoice->id, null);
    }
}

function determineBillGstType($bill_from, $bill_to)
{
    $bill_gst_type = 'cgst_sgst';
    if ($bill_to) {
        if (
            isset($bill_from['state_code']) &&
            $bill_from['state_code'] &&
            isset($bill_to['state_code']) &&
            $bill_to['state_code'] &&
            $bill_from['state_code'] != $bill_to['state_code']
        ) {
            $bill_gst_type = 'igst';
        }
    }
    return $bill_gst_type;
}

function calculateTax($invoice_items, $bill_to, $bill_from, $invoice)
{
    $bill_gst_type = 'cgst_sgst';
    if ($bill_to) {
        if (
            isset($bill_from['state_code']) &&
            $bill_from['state_code'] &&
            isset($bill_to['state_code']) &&
            $bill_to['state_code'] &&
            $bill_from['state_code'] != $bill_to['state_code']
        ) {
            $bill_gst_type = 'igst';
        }
    }
    // additional charges
    $summary = [
        'taxable_amount' => 0,
        'tax_amount' => 0,
        'subtotal' => 0,
        'total' => 0,
    ];
    $additional = $invoice['additional_charges'];

    // Decode if stored as JSON string
    if (is_string($additional)) {
        $additional = json_decode($additional, true);
    }

    if (empty($additional)) {
        $additional = []; // avoid errors
    }

    foreach ($additional as $item) {

        $calcAmount = (float) $item['calc_amount'];
        $taxRate = (float) $item['tax'];

        if ($item['status'] == 'excluded') {
            $itemTaxable = $calcAmount;
            $itemTax = ($calcAmount * $taxRate) / 100;
            $itemTotal = $itemTaxable + $itemTax;
        } else {
            $itemTaxable = $calcAmount / (1 + ($taxRate / 100));
            $itemTax = $calcAmount - $itemTaxable;
            $itemTotal = $calcAmount;
        }

        $summary['taxable_amount'] += $itemTaxable;
        $summary['tax_amount'] += $itemTax;
        $summary['subtotal'] += $itemTaxable;
        $summary['total'] += $itemTotal;
    }

    $summary = array_map(fn($v) => round($v, 2), $summary);

    $total_tax = $invoice_items->sum(function ($item) {
        return _taxPrice($item->price * $item->qty, $item->tax, 'actual');
    });
    return ['total_tax' => $total_tax + $summary['tax_amount'], 'gst_type' => $bill_gst_type, 'invoice_total' => $invoice->total_amount + $summary['total']];
}

function generatePdf($invoice, $bill_from, $bill_to, $bill_data, $from, $shipping_address, $renderOnly)
{
    $bill_from_type = ($from === 'vendor'  || $from === 'store_vendor' ? 'vendor' : 'admin') . '_to_' . $invoice->bill_to_type;
    if ($bill_from_type == 'vendor_to_vendor') {
        $html = View::make('invoice_template.purchase_invoice', compact(
            'invoice',
            'bill_from',
            'bill_to',
            'bill_data',
            'bill_from_type',
            'shipping_address'
        ))->render();
    } else {
        $html = View::make('invoice_template.service_n_manual', compact(
            'invoice',
            'bill_from',
            'bill_to',
            'bill_data',
            'bill_from_type',
            'shipping_address'
        ))->render();
    }


    if ($renderOnly) {
        return $html;
    }

    $tempDir = storage_path('app/mpdf_temp');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0775, true);
    }

    $mpdf = new Mpdf([
        'tempDir' => $tempDir,
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
    ]);

    $mpdf->WriteHTML($html);

    $pdfName = 'invoice_' . date('YmdHis') . rand(100000, 999999) . '.pdf';
    $fileUrl = Helpers::savePdfToPublic($mpdf, 'invoice', $pdfName);

    return [
        'pdf' => $pdfName,
        'url' => asset('storage/app/public/invoice') . '/' . $pdfName
    ];
}
if (!function_exists('_poTNC')) {

    function _poTNC()
    {
        $storeId = Helpers::get_store_id();
        $tnc = VendorTermsCondition::where("vendor_id", $storeId)->where('type', 'purchase_order')->first();
        return $tnc ? $tnc->terms_n_conditons : null;
    }
}
if (!function_exists('_updateInventoryStock')) {

    function _updateInventoryStock($inv_item_id, $qty, $unit = null)
    {
        try {
            $inv_item = InventoryItem::where('id', $inv_item_id)
                ->where('store_id', Helpers::get_store_id())
                ->first();

            if (!$inv_item || $qty <= 0) {
                return;
            }

            $deductQty = $qty;

            // Convert ONLY if:
            // 1. unit is provided
            // 2. item has secondary unit
            // 3. selected unit matches secondary unit
            // 4. conversion values are valid
            if (
                $unit &&
                $inv_item->secondary_unit &&
                $unit == $inv_item->secondary_unit &&
                $inv_item->primary_qty > 0 &&
                $inv_item->secondary_qty > 0
            ) {
                // 1 secondary = primary_qty / secondary_qty
                $conversionRate = $inv_item->primary_qty / $inv_item->secondary_qty;
                $deductQty = $qty * $conversionRate;
            }

            // Update stock (always in primary unit)
            $updated_stock = $inv_item->stock - $deductQty;
            $inv_item->stock = max(0, $updated_stock);
            $inv_item->save();

            // low stock alert
            if ($updated_stock <= 5) {
                $url = route('vendor.inventory.purchase.orders');
                _notifLowStock(null, $updated_stock, $inv_item_id, $url);
                _placePurchaseOrder($inv_item_id, $updated_stock);
            }
        } catch (\Throwable $th) {
            //  Log::error($th);
        }
    }
}
if (!function_exists('_placePurchaseOrder')) {

    function _placePurchaseOrder($item_id, $stock)
    {
        $order = new PurchaseOrder();
        $order->store_id = Helpers::get_store_id();
        $order->inventory_item_id = $item_id;
        $order->stock = $stock;
        $order->save();
    }
}
if (!function_exists('_branchInventoryItems')) {

    function _branchInventoryItems($branch = null, $search = null, $action = 'view')
    {
        $query  = DB::table('inventory_items as ii')
            ->join('branch_inventory_item as bi', 'ii.id', '=', 'bi.inventory_item_id')
            ->join('branches as b', 'bi.branch_id', '=', 'b.id')
            ->where('bi.store_id', Helpers::get_store_id())
            ->when($search, function ($query) use ($search) {
                $query->where('ii.item_name', 'like', "%{$search}%");
            })
            ->when($branch && $branch != 'all', function ($query) use ($branch) {
                $query->where('branch_id', $branch);
            })->select(
                'ii.id',
                'ii.item_name as name',
                'bi.branch_id',
                'bi.qty',
                'bi.qty_left',
                'b.name as branch_name',
                'b.type as branch_type',
                'bi.price',
                'bi.gst_percent',
                DB::raw("'Inventory Item' as item_type")
            );

        return $query->get() ?? [];
    }
}
if (!function_exists('_posDashboardColors')) {

    function _posDashboardColors()
    {
        $store_config = StoreConfig::where('store_id', Helpers::get_store_id())->first();

        $colors = [
            'main_card'     => $store_config && $store_config->main_card
                ? $store_config->main_card
                : '#c2feef', // rgb(194 254 239 / 68%)

            'branch_1_color' => $store_config && $store_config->branch_1_color
                ? $store_config->branch_1_color
                : '#c7f0ff', // rgb(199 240 255 / 80%)

            'branch_2_color' => $store_config && $store_config->branch_2_color
                ? $store_config->branch_2_color
                : '#ffd3a5', // rgba(255, 211, 165, 0.8)

            'branch_3_color' => $store_config && $store_config->branch_3_color
                ? $store_config->branch_3_color
                : '#fd9cb8', // rgba(253, 156, 184, 0.8)
        ];

        return $colors;
    }
}
if (!function_exists('_inventoryItems')) {

    function _inventoryItems()
    {
        $store_id = Helpers::get_store_id();
        $items = InventoryItem::where('store_id', $store_id)->select('item_name as name', 'id', 'category_id', 'image', 'selling_price as price')->get();
        return $items ?? [];
    }
}
if (!function_exists('_notifLowStock')) {

    function _notifLowStock($itemRow = null, $qty_left = null, $inv_id = null, $url = null)
    {
        if ($inv_id == null) {
            $inv_id = $itemRow->inventory_item_id;
        }
        $store_id =  Helpers::get_store_id();
        $item = InventoryItem::where('id', $inv_id)->where('store_id', $store_id)->first();
        $itemName = $item ? ucfirst($item->item_name) : ' and Item ';

        $title =  'Low Stock Alert';
        $msg = "Item '{$itemName}' is low in stock. Only {$qty_left} left.";

        if ($url == null) {
            $url = route('vendor.pos.items');
        }

        _inAppNotification($title, $msg, null, $store_id, $url, 'vendor'); // to vendor

        if (auth('vendor_employee')->check()) {
            $staff_id = Helpers::get_loggedin_user()->id;
            _inAppNotification($title, $msg, null, $staff_id, $url, 'vendor_employee'); // to staff .. if staff generated token 
        }
    }
}

function _storeBranches()
{

    $branches = Branch::where('store_id', Helpers::get_store_id())->get();
    return $branches ?? [];
}
function _taskQuotationExist($task_id)
{
    return Quotation::where('task_id', $task_id)->first();
}
function _jobCardExist($task_id)
{
    return JobCard::where('task_id', $task_id)->first();
}
function _taskRRExist($task_id)
{
    return ReceivableReceipt::where('task_id', $task_id)->first();
}
function _taskInvoiceExist($task_id)
{
    return ManualInvoice::where('task_id', $task_id)->first();
}
function _getAdminNotifications()
{
    $notif['all'] = InAppNotification::where('user_type', 'admin')->orderBy('id', 'desc')->get();
    $notif['unread_count'] = InAppNotification::where('user_type', 'admin')->where('is_read', 0)->count();
    return $notif;
}
function _generateOrderInvoicePdf($order)
{
    $tempDir = storage_path('app/mpdf_temp');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0775, true);
    }

    $mpdf = new Mpdf([
        'tempDir' => $tempDir,
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
    ]);

    $html = View::make('invoice_template.shop', compact('order'))->render();

    $mpdf->WriteHTML($html);
    $pdfName = 'invoice_' . date('YmdHis') . '.pdf';
    $fileUrl = Helpers::savePdfToPublic($mpdf, 'invoice', $pdfName);

    $data['pdf'] = $pdfName;
    $data['url'] = asset('storage/app/public/invoice') . '/' . $pdfName;

    return $data;
}

function getStateFromPincode($pincode)
{
    $apiKey = \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value;

    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$pincode&key=$apiKey";
    $json = file_get_contents($url);
    $data = json_decode($json, true);

    foreach ($data['results'][0]['address_components'] as $component) {
        if (in_array("administrative_area_level_1", $component['types'])) {
            return $component['long_name'];
        }
    }
    return null;
}
function getPincodeFromCoordinates($lat, $lng)
{
    $apiKey = \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value;
    $url = "https://maps.googleapis.com/maps/api/geocode/json";

    $response = Http::get($url, [
        'latlng' => "$lat,$lng",
        'key' => $apiKey
    ]);

    if ($response->successful()) {
        $results = $response->json('results');

        if (!empty($results)) {
            foreach ($results as $key => $value) {
                foreach ($value['address_components'] as $component) {
                    if (in_array('postal_code', $component['types'])) {
                        return $component['long_name'];
                    }
                }
            }
        }
    }

    return null; // if no postal code found
}
function isInTrial($userId, $planId, $currentDate = null)
{
    $currentDate = $currentDate ?: now();
    $module = DB::table('store_enabled_modules')->where('user_id', $userId)
        ->where('plan_id', $planId)
        ->first();
    if (!$module) return false;

    $plan = Plan::find($planId);
    $startDate = Carbon::parse($module->start_date);

    if ($currentDate->lte($startDate->copy()->addDays($plan->free_trial_days - 1))) {
        return true;
    }

    $manualTrials = ManualTrial::where('user_id', $userId)
        ->where('plan_id', $planId)
        ->get();

    foreach ($manualTrials as $trial) {
        $start = Carbon::parse($trial->start_date);
        $end = $start->copy()->addDays($trial->trial_days - 1);
        if ($currentDate->between($start, $end)) {
            return true;
        }
    }

    return false;
}
function _modulePrice($submoduleId)
{
    $sub_module = DB::table('sub_modules')->where('id', $submoduleId)->first();
    return  $sub_module->price_per_month;
}
function _offeredModule($submoduleKey)
{
    $business_type = DB::table('store_types')->where('name', Helpers::get_store_data()->business_type)->first();
    $permittedSubmodules = [];
    if ($business_type && $business_type->permitted_submodules) {
        $permittedSubmodules = explode(',', $business_type->permitted_submodules);
    }
    return in_array($submoduleKey, $permittedSubmodules);
}
function fetchInvoices($model, $invoiceType, $paymentStatus, $operator = null, $date = null, $statusLabel = null, $startOfMonth = null, $endOfMonth = null, $search = null)
{
    // prx($startOfMonth);
    $query = $model::with(['websiteUser', 'storeCustomer'])
        ->where('vendor_id', Helpers::get_store_id())
        ->where('payment_status', $paymentStatus);
    // ->where('invoice_id', 'SER_2'); 

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('invoice_id', 'like', "%{$search}%");
        });
    } else {
        $query->where(function ($query) use ($operator, $date, $startOfMonth, $endOfMonth) {
            $query->when($operator, function ($q) use ($operator, $date, $startOfMonth, $endOfMonth) {
                $q->where('created_at', $operator, $date)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
                ->when(is_null($operator), function ($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                })
                ->orWhereNull('created_at');
        });
    }

    return $query->get()->each(function ($item) use ($invoiceType, $statusLabel) {
        $item->invoice_type = $invoiceType;
        $item->status = $statusLabel;
    });
}


function _freeTrialEndingDate($submoduleId)
{
    $storeId = Helpers::get_store_id();
    $today = Carbon::today()->toDateString();

    $date = StoreEnabledModule::where('store_id', $storeId)
        ->where('submodule_id', $submoduleId)
        ->where('type', '=', 'free')
        ->where('free_trial_extended_until', '>', $today)
        ->first();

    if ($date) {
        return $date->free_trial_extended_until;
    } else {
        return date('Y-m-d');
    }
}
function _isSubmoduleEnabled($submoduleId)
{
    $storeId = Helpers::get_store_id();
    $today = Carbon::today();
    $gracePeriodDays = 7;

    $enabledPaid = false;
    $inGracePeriod = false;
    $graceEndDate = null;
    $expiringText = '';

    $submodule = SubModule::find($submoduleId);

    // Get the latest paid entry
    $paidModule = StoreEnabledModule::where('store_id', $storeId)
        ->where('submodule_id', $submoduleId)
        ->where('type', 'postpaid')
        ->orderByDesc('paid_on')
        ->first();

    if ($paidModule && $paidModule->paid_on) {
        $paidOn = Carbon::parse($paidModule->paid_on);
        $paidMonth = $paidOn->copy()->startOfMonth(); // Paid for this month (May)

        $billingDueDate = $paidMonth->copy()->addMonthNoOverflow()->startOfMonth()->addDays(1); // 2nd of next month (June 2)
        $graceEndDate = $billingDueDate->copy()->addDays($gracePeriodDays); // e.g. June 9


        if ($today->lessThan($billingDueDate)) {
            $enabledPaid = true;
        } elseif ($today->between($billingDueDate, $graceEndDate)) {
            $inGracePeriod = true;
        }
    }

    // Check if free trial is active
    $freeModule = StoreEnabledModule::where('store_id', $storeId)
        ->where('submodule_id', $submoduleId)
        ->where('type', 'free')
        ->where('free_trial_extended_until', '>', $today->toDateString())
        ->exists();

    $lastModule = StoreEnabledModule::where('store_id', $storeId)
        ->where('submodule_id', $submoduleId)
        ->where('type', 'free')
        ->orderByDesc('created_at')
        ->first();

    // Check latest unpaid invoice for pay now button
    $latestInvoice = \App\Models\ManualInvoice::where('bill_to', $storeId)
        ->where('bill_to_type', 'vendor')
        ->where('payment_status', 'Unpaid')
        ->whereRaw("JSON_CONTAINS(service_key, '\"$submodule->Key\"')")
        ->where('key_purpose', 'services_billing')
        ->latest()
        ->first();

    // prx($latestInvoice);

    $payNowButton = '';
    if ($latestInvoice) {
        $payNowButton = ' <a href="' . route('vendor.invoice.pay-bill', ['invoice_id' => $latestInvoice->id]) . '" class="btn btn-sm btn-primary ml-2">Pay Now</a>';
    }

    // Prepare expiring text
    if ($inGracePeriod) {
        $expiringText = "Your payment due date has passed. Please pay to avoid service disruption. Access will be blocked after the grace period ends on "
            . $graceEndDate->format('F j, Y') . $payNowButton;
    } elseif (!$enabledPaid && !$freeModule && $latestInvoice) {
        $expiringText = "Your access has been disabled due to non-payment. Please pay to restore access." . $payNowButton;
    } elseif ($lastModule && $lastModule->free_trial_extended_until) {
        $status = $lastModule->free_trial_extended_until < $today->toDateString() ? 'Expired' : 'Expiring';
        $expiringText = "Free Trial {$status} on " . $lastModule->free_trial_extended_until;
    }

    return [
        'free_trial' => $freeModule,
        'enabled' => $enabledPaid || $freeModule || $inGracePeriod,
        'warning' => $inGracePeriod,
        'offer' => !$paidModule,
        'billing' => $paidModule,
        'expiring_text' => $expiringText,
        'pay_warning' => (!$enabledPaid && !$freeModule) || $inGracePeriod,
    ];
}

function _accessibleModules()
{
    $accessibleModules = [];

    $submodules = SubModule::all();

    foreach ($submodules as $submodule) {
        $status = _isSubmoduleEnabled($submodule->id);

        if ($status['enabled']) {
            $accessibleModules[$submodule->Key] = $submodule->name;
        }
    }
    return $accessibleModules;
}


function wallet_recharge($data)
{

    $store_id = $data->attribute_id;
    $info = TmpWallet::where('store_id', $store_id)->latest()->first();
    $amount = $data->payment_amount;

    $wallet =  StoreWallet::where('vendor_id', $store_id)->first();
    if ($wallet) {
        $wallet->increment('total_earning', $info->amount);
        $wallet->save();
    } else {
        $store = Store::find($store_id);
        $wallet = new StoreWallet();
        $wallet->vendor_id = $store->vendor->id;
        $wallet->total_earning = $info->amount;
        $wallet->total_withdrawn = 0.0;
        $wallet->pending_withdraw = 0.0;
        $wallet->created_at = now();
        $wallet->updated_at = now();
        $wallet->save();
    }

    //insert into transactions 
    $account_transaction = new AccountTransaction();
    $account_transaction->current_balance = $wallet->sum('total_earning') + $amount;
    $account_transaction->from_type = 'store';
    $account_transaction->amount = $info->amount;
    $account_transaction->from_id = $store_id;
    $account_transaction->method = 'wallet';
    $account_transaction->action = 'credit';
    $account_transaction->reason = 'Wallet Recharge';
    $account_transaction->created_by = 'store';
    $account_transaction->save();

    $wallet_recharge_gst_percent = \App\Models\BusinessSetting::where('key', 'wallet_recharge_gst_percent')->first();
    $wallet_recharge_hsn = \App\Models\BusinessSetting::where('key', 'wallet_recharge_hsn')->first();
    $wallet_recharge_gst_status = \App\Models\BusinessSetting::where('key', 'wallet_recharge_gst_status')->first()?->value ?? 'included';

    // generate bill 
    $invoice = new ManualInvoice();
    $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
    $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
    $invoice->vendor_id = NULL;
    $invoice->bill_to = Helpers::get_store_id();
    $invoice->bill_to_type = 'vendor';
    $invoice->module_id =  Helpers::get_store_data()->module_id;
    $invoice->total_amount = $wallet_recharge_gst_status == 'included' ? $info->amount :  floor(_taxIncludedPrice($info->amount, $wallet_recharge_gst_percent->value, 'actual'));
    $invoice->payment_method = 'Cash';
    $invoice->tax_type =  'gst';
    $invoice->payment_status =  'Paid';
    $invoice->payment_date =  date('Y-m-d');
    $invoice->generated_by =  'admin';
    $invoice->save();

    $InvoiceItem = new InvoiceItem();
    $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
    $InvoiceItem->name = 'Wallet Recharge';
    $InvoiceItem->qty = 1;
    $InvoiceItem->price = $info->amount;
    $InvoiceItem->tax =  $wallet_recharge_gst_percent->value;
    $InvoiceItem->hsn =  $wallet_recharge_hsn->value;
    $InvoiceItem->save();

    // ledger entry 
    $debit_account = Helpers::ensurePurchaseAccount('Wallet Recharge');
    $credit_account = Helpers::ensureWalletRevenueAccount();

    $data = [
        'date' => now(),
        'amount' => $invoice->total_amount,
        'voucher_type' => 'Purchase',
        'status' => 'approved',
    ];
    $store_id = Helpers::get_store_id();
    $voucher =  _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'admin', null);
    _saveDayBookEntry($invoice->total_amount, 'debit', $store_id, 'Wallet Recharge', $invoice->id, $voucher?->id);

    try {
        $data = _createBillPdf($invoice, 'admin');
        $invoice->update(['pdf' => $data['pdf']]);
    } catch (\Throwable $th) {
        //
    }
    $info = TmpWallet::where('store_id', $store_id)->delete();
}
function _getInvoicePrefix($tax_type, $store = null)
{
    if (!$store) {
        $store = Store::find(Helpers::get_store_id());
    }
    $store_prefix = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $store->name)), 0, 3);
    $infix = $infix ?? 'INV';

    $today = now();
    $currentYear = $today->year;
    $financialYearStart = Carbon::createFromDate($currentYear, 4, 1);

    if ($today->month < 4) {
        $financialYearStart->subYear();
    }
    $financialYearEnd = $financialYearStart->copy()->addYear()->subDay(); // 31 March
    $year = $financialYearStart->format('y') . '-' . $financialYearEnd->format('y');

    if ($tax_type == 'gst') {
        $prefixe = $store_prefix . '_' . $infix . '_' . $year . '_';
    } else {
        $prefixe = $store_prefix . '_';
    }
    return $prefixe;
}
function is_serial_number_used($number, $prefix, $tax_type)
{
    $today = now();
    $currentYear = $today->year;
    $financialYearStart = Carbon::createFromDate($currentYear, 4, 1);

    if ($today->month < 4) {
        $financialYearStart->subYear();
    }
    $financialYearEnd = $financialYearStart->copy()->addYear()->subDay(); // 31 March next year
    // if ($tax_type == 'gst') {
    $manualExists = DB::table('manual_invoices')
        ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
        ->where("invoice_id", $prefix . $number)
        ->exists();
    $serviceExists = DB::table('service_invoices')
        ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
        ->where("invoice_id", $prefix . $number)
        ->exists();
    // } else {
    //     $manualExists = DB::table('manual_invoices')
    //         ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
    //         ->where("invoice_id", $number)
    //         ->exists();
    //     $serviceExists = DB::table('service_invoices')
    //         ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
    //         ->where("invoice_id", $number)->exists();
    // }
    return $serviceExists || $manualExists;
}
if (!function_exists('shortAmount')) {
    function shortAmount($num)
    {
        if ($num >= 100000) {
            // 1 Lakh or more
            return \App\CentralLogics\Helpers::currency_symbol() . round($num / 100000, 1) . ' L';
        } elseif ($num >= 1000) {
            // Thousands
            return \App\CentralLogics\Helpers::currency_symbol() . round($num / 1000, 1) . ' K';
        }
        return \App\CentralLogics\Helpers::currency_symbol() . $num; // small numbers show as is
    }
}

function order_place($data)
{
    $order = Order::find($data->attribute_id);
    $order->order_status = 'confirmed';
    if ($order->payment_method != 'partial_payment') {
        $order->payment_method = $data->payment_method;
    }
    // $order->transaction_reference=$data->transaction_ref;
    $order->payment_status = 'paid';
    $order->confirmed = now();
    $order->save();
    OrderLogic::update_unpaid_order_payment(order_id: $order->id, payment_method: $data->payment_method);
    try {
        Helpers::send_order_notification($order);
        $address = json_decode($order->delivery_address, true);

        $order_verification_mail_status = Helpers::get_mail_status('order_verification_mail_status_user');
        if (config('order_delivery_verification') == 1 && $order_verification_mail_status == '1' && $order->is_guest == 0) {
            Mail::to($order->customer->email)->send(new OrderVerificationMail($order->otp, $order->customer->f_name));
        }

        if ($order->is_guest == 1 && config('mail.status') && $order_verification_mail_status == '1' && isset($address['contact_person_email'])) {
            Mail::to($address['contact_person_email'])->send(new OrderVerificationMail($order->otp, $order->customer->f_name));
        }
    } catch (\Exception $e) {
        info($e);
    }
}

function order_failed($data)
{
    $order = Order::find($data->attribute_id);
    $order->order_status = 'failed';
    if ($order->payment_method != 'partial_payment') {
        $order->payment_method = $data->payment_method;
    }
    $order->failed = now();
    $order->save();
}

function wallet_success($data)
{
    $order = WalletPayment::find($data->attribute_id);
    $order->payment_method = $data->payment_method;
    // $order->transaction_reference=$data->transaction_ref;    
    $order->payment_status = 'success';
    $order->save();
    $wallet_transaction = CustomerLogic::create_wallet_transaction($data->payer_id, $data->payment_amount, 'add_fund', $data->payment_method);
    if ($wallet_transaction) {
        $mail_status = Helpers::get_mail_status('add_fund_mail_status_user');
        try {
            if (config('mail.status') && $mail_status == '1') {
                Mail::to($wallet_transaction->user->email)->send(new \App\Mail\AddFundToWallet($wallet_transaction));
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }
    }
}

function wallet_failed($data)
{
    $order = WalletPayment::find($data->attribute_id);
    $order->payment_status = 'failed';
    $order->payment_method = $data->payment_method;
    $order->save();
}

if (!function_exists('addon_published_status')) {
    function addon_published_status($module_name)
    {
        $is_published = 0;
        try {
            $full_data = include("Modules/{$module_name}/Addon/info.php");
            $is_published = $full_data['is_published'] == 1 ? 1 : 0;
            return $is_published;
        } catch (\Exception $exception) {
            return 0;
        }
    }
}

if (!function_exists('config_settings')) {
    function config_settings($key, $settings_type)
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $key)
                ->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }
        return (isset($config)) ? $config : null;
    }
}
if (!function_exists('_getWhere')) {
    function _getWhere($table, $whereCondition)
    {
        return
            DB::table($table)->where($whereCondition)->get();
    }
}
if (!function_exists('_getWhereOne')) {
    function _getWhereOne($table, $whereCondition)
    {
        return
            DB::table($table)->where($whereCondition)->first();
    }
}
if (!function_exists('_getOneWhere')) {
    function _getOneWhere($table, $whereCondition)
    {
        return
            DB::table($table)->where($whereCondition)->first();
    }
}
if (!function_exists('_getWhereLimit')) {
    function _getWhereLimit($table, $whereCondition, $limit)
    {
        return
            DB::table($table)->where($whereCondition)->limit($limit)->get();
    }
}
if (!function_exists('_isMenuMinimized')) {
    function _isMenuMinimized()
    {
        $pref = request()->cookie('vendor_minimized_menu');

        return $pref == 1;
    }
}

if (!function_exists('_clockedInEmployee')) {
    function _clockedInEmployee($all = false)
    {
        $today = now()->toDateString();

        if ($all) {
            return \App\Models\EmployeeTimeCard::select('emp_id')
                ->whereDate('in_time', $today)
                ->whereRaw("id IN (
                    SELECT MAX(id) 
                    FROM employee_time_cards 
                    WHERE DATE(in_time) = '$today'
                    GROUP BY emp_id
                )")
                ->where('vendor_id', Helpers::get_store_id())
                ->where(function ($q) {
                    $q->whereNull('out_time')
                        ->orWhere('out_time', '');
                })
                ->count();
        }

        $empId = \App\CentralLogics\Helpers::get_loggedin_user()->id;

        $clockIn = \App\Models\EmployeeTimeCard::where('emp_id', $empId)
            ->whereDate('in_time', $today)
            ->orderBy('id', 'desc')
            ->first();

        if (!$clockIn) {
            return false;
        }

        return ($clockIn->out_time == '' || $clockIn->out_time === null);
    }
}



if (!function_exists('_clockedInEmployeeDutyHours')) {
    function _clockedInEmployeeDutyHours()
    {
        $employee = Helpers::get_loggedin_user();
        $shift = StoreShift::where('store_id', Helpers::get_store_id())->where('id', $employee->store_shift_id)->first();
        if (!$shift) {
            return 0;
        }
        $start_time = $shift->start_time;
        $end_time   = $shift->end_time;

        $start = strtotime($start_time);
        $end   = strtotime($end_time);

        // Handle overnight shift (optional safety)
        if ($end < $start) {
            $end += 86400;
        }

        return round(($end - $start) / 3600, 2); // 4.00
    }
}



if (!function_exists('_inTime')) {
    function _inTime($type = 'phrase')
    {
        $empId = \App\CentralLogics\Helpers::get_loggedin_user()->id;
        $clockIn =  EmployeeTimeCard::where('emp_id', $empId)->orderBy('id', 'desc')
            ->limit(1)
            ->first();
        if ($type = 'timestamp') {
            return $clockIn->in_time;
        }
        if (_clockedInEmployee()) {
            return 'Clock started at ' . explode(' ', $clockIn->in_time)[1];
        } else {
            return 'You are currently clocked out';
        }
    }
}
if (!function_exists('_todayInTime')) {
    function _todayInTime($empId)
    {
        $clockIn =  EmployeeTimeCard::where('emp_id', $empId)->first();
        if ($clockIn) {
            return  $clockIn->in_time;
        } else {
            return '-';
        }
    }
}
if (!function_exists('_todayOutTime')) {
    function _todayOutTime($empId)
    {
        $clockIn =  EmployeeTimeCard::where('emp_id', $empId)->first();
        if ($clockIn) {
            return  $clockIn->out_time;
        } else {
            return '-';
        }
    }
}

if (!function_exists('_pendingLeavesCount')) {
    function _pendingLeavesCount($id)
    {
        $count = Leave::where('emp_id', $id)
            ->where('status', 'pending')
            ->count();

        return $count;
    }
}

if (!function_exists('_reviewStatus')) {
    function _reviewStatus($acc_id)
    {
        $service = AcceptedServiceRequest::find($acc_id);
        if (isset($service) == false || $service->current_status != 'Completed') {
            return  false;
        }
        $store_id = $service->vendor_id;
        $user_id = DB::table('service_requests')->where('id', $service->service_request_id)->first();
        if ($user_id) {
            $user_id = $user_id->user_id;
        } else {
            $user_id = null;
        }
        $store = Store::find($store_id);
        if (isset($store) == false) {
            return false;
        }
        $multi_review = StoreReview::where(['store_id' => $store_id, 'user_id' => $user_id, 'order_id' => $acc_id])->first();
        if (isset($multi_review)) {
            return false;
        }
        return true;
    }
}
if (!function_exists('_newServiceRequestsCount')) {
    function _newServiceRequestsCount()
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();
        $store_data = \App\CentralLogics\Helpers::get_store_data();

        $serviceRequests = ServiceRequest::where('zone_id', '[' . $store_data->zone_id . ']')->where('expired', 0)->get();

        $count = 0;
        foreach ($serviceRequests as $serviceRequest) {
            $itemExists = Item::withoutGlobalScope(StoreScope::class)->where('id', $serviceRequest->item_id)
                ->whereRaw("FIND_IN_SET(?, store_ids)", [$store_id])
                ->exists();

            if ($itemExists) {
                $exist =  DB::table('accepted_service_requests')->where('service_request_id', $serviceRequest->id)->where('vendor_id', $store_id)->whereNot('current_status', 'Confirmation Request Sent')->exists();
                if (!$exist) {

                    $count++;
                }
            }
        }
        return $count;
    }
}
if (!function_exists('_leadChargesAdded')) {

    function _leadChargesAdded($cat_id)
    {
        $charges = DB::table('lead_charges')
            ->where('lead_charges.category_id', $cat_id)
            ->get();

        if (count($charges)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('_acceptedReq')) {

    function _acceptedReq($req_id)
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();
        $charges = DB::table('accepted_service_requests')
            ->where('service_request_id', $req_id)
            ->where('vendor_id', $store_id)
            ->exists();
        $charges2 = DB::table('cancelled_service_requests')
            ->where('service_request_id', $req_id)
            ->where('vendor_id', $store_id)
            ->exists();

        if ($charges || $charges2) {
            return true;
        } else {
            return false;
        }
    }
}


if (!function_exists('_leadFinalStatus')) {

    function _leadFinalStatus($lead)
    {
        if (isset($lead->additional_status) && $lead->additional_status === 'missed') {
            $finalStatus = 'Missed';
        } elseif ($lead->current_status === 'Completed') {
            $finalStatus = 'Completed';
        } elseif (str_starts_with($lead->current_status, 'Cancelled')) {
            $finalStatus = 'Cancelled';
        } elseif ($lead->current_status === 'Confirmed') {

            if ($lead->assigned_status === 'Unassigned') {
                $finalStatus = 'Confirmed - Unassigned';
            } else {
                if ($lead->assigned_type === 'vendor') {
                    $finalStatus = 'Confirmed - Assigned (Self)';
                } elseif ($lead->assigned_type === 'staff') {
                    $finalStatus = 'Confirmed - Assigned (Staff)';
                } else {
                    $finalStatus = 'Confirmed - Assigned';
                }
            }
        } elseif ($lead->current_status === 'Confirmation Request Sent') {
            $finalStatus = 'Waiting for Confirmation';
        } else {
            $currentServiceStatus = _getCurrentServiceStatus($lead->id);
            $isAcceptedReq = _acceptedReq($lead->id);

            if ($isAcceptedReq) {
                $finalStatus = 'Accepted';
            } elseif ($currentServiceStatus === 'Confirmation Request Sent') {
                $finalStatus = 'Waiting for Confirmation';
            } else {
                $finalStatus = 'Pending';
            }
        }
        return  $finalStatus;
    }
}
if (!function_exists('_isCancelled')) {

    function _isCancelled($req_id)
    {
        $store_id = \App\CentralLogics\Helpers::get_store_id();

        $charges2 = DB::table('cancelled_service_requests')
            ->where('service_request_id', $req_id)
            ->where('vendor_id', $store_id)
            ->exists();

        if ($charges2) {
            return true;
        } else {
            return false;
        }
    }
}
if (!function_exists('addStatus')) {
    function addStatus(&$arr, $key)
    {
        if (!isset($arr[$key])) {
            $arr[$key] = 0;
        }
        $arr[$key]++;
    }
}
if (!function_exists('_serviceHistory')) {
    function _serviceHistory($uid)
    {
        try {
            //code...


            $confirmationReq1 =
                DB::table('accepted_service_requests')
                ->join('service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
                ->join('stores', 'stores.id', 'accepted_service_requests.vendor_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->where('service_requests.user_id', $uid)
                ->where('accepted_service_requests.current_status', 'Completed')
                ->select('accepted_service_requests.*', 'service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image', 'stores.name as store_name',  'stores.logo as store_logo', 'stores.id as store_id', 'stores.address as store_address', 'stores.slug as store_slug', 'stores.phone as store_phone');

            $confirmationReq2 = DB::table('cancelled_service_requests')
                ->join('service_requests', 'cancelled_service_requests.service_request_id', 'service_requests.id')
                ->join('stores', 'stores.id', 'cancelled_service_requests.vendor_id')
                ->join('items', 'items.id', 'service_requests.item_id')
                ->where('service_requests.user_id', $uid)
                ->select('cancelled_service_requests.*', 'service_requests.id as service_id', 'items.name as item_name', 'items.image as item_image', 'stores.name as store_name', 'stores.logo as store_logo', 'stores.id as store_id', 'stores.address as store_address', 'stores.slug as store_slug', 'stores.phone as store_phone');

            $confirmationReq = $confirmationReq1
                ->union($confirmationReq2)
                ->get();
            foreach ($confirmationReq as $key => $req) {
                $confirmationReq[$key]->item_image =  asset('storage/app/public/product') . '/' . $req->item_image;

                $existingInvoice = DB::table('service_invoices')->where('service_id', $req->service_request_id)->first();

                if ($existingInvoice) {
                    $confirmationReq[$key]->invoice = asset('storage/app/public/invoice') . '/' .  $existingInvoice->pdf;
                } else {
                    $confirmationReq[$key]->invoice = null;
                }
                // print_r($existingInvoice);

                if ($req->assigned_status == 'Assigned' && $req->assigned_to != NULL) {

                    if ($req->assigned_type == 'vendor') {
                        // self assigned 
                        $vendorInfo = DB::table('vendors')->join('stores', 'stores.vendor_id', 'vendors.id')->where('stores.id',  $req->assigned_to)
                            ->select('vendors.*')
                            ->first();
                        $confirmationReq[$key]->staff_name = $vendorInfo ? $vendorInfo->f_name . ' ' . $vendorInfo->l_name : '';
                        $confirmationReq[$key]->staff_role = 'Vendor';
                        $confirmationReq[$key]->staff_image = $vendorInfo ? asset('storage/app/public/vendor') . '/' . $vendorInfo->image : '';
                        $confirmationReq[$key]->staff_contact = $vendorInfo ? $vendorInfo->phone : '';
                    } else {

                        $staffInfo = DB::table('vendor_employees')->where('vendor_employees.id', $req->assigned_to)
                            ->join('employee_roles', 'employee_roles.id', 'vendor_employees.employee_role_id')
                            ->select('vendor_employees.*', 'employee_roles.name')
                            ->first();
                        $confirmationReq[$key]->staff_image = $staffInfo ? asset('storage/app/public/vendor') . '/' . $staffInfo->image : '';
                        $confirmationReq[$key]->staff_name = $staffInfo ? $staffInfo->f_name . ' ' . $staffInfo->l_name : '';
                        $confirmationReq[$key]->staff_role = $staffInfo ? $staffInfo->name : '';
                        $confirmationReq[$key]->staff_contact = $staffInfo ? $staffInfo->phone : '';
                    }
                }
                // prx($req);
                $confirmationReq[$key]->gatepass_exists  = GatePass::where('accepted_service_id', $req->id)->exists();
                $confirmationReq[$key]->quotation_exists = InServiceQuotation::where('service_id', $req->service_request_id)->exists();
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $confirmationReq ?? [];
    }
}
if (!function_exists('_serviceRunning')) {
    function _serviceRunning($uid)
    {
        $confirmationReq = DB::table('service_requests')
            ->leftJoin('accepted_service_requests', 'accepted_service_requests.service_request_id', 'service_requests.id')
            ->leftJoin('stores', 'stores.id', 'accepted_service_requests.vendor_id')
            ->join('items', 'items.id', 'service_requests.item_id')
            ->where('service_requests.user_id', $uid)
            ->where('service_requests.expired', 0)
            ->where(function ($query) {
                $query->where('accepted_service_requests.current_status', '!=', 'Completed')
                    ->orWhereNull('accepted_service_requests.current_status'); // Include rows where there is no accepted request
            })
            ->select(
                'accepted_service_requests.*',
                'service_requests.id as service_request_id',
                'service_requests.created_at',
                'items.name as item_name',
                'items.image as item_image',
                'stores.name as store_name',
                'stores.id as store_id',
                'stores.address as store_address',
                'stores.slug as store_slug',
                'stores.logo as store_logo',
                'stores.phone as store_phone',
            )
            ->orderBy('service_requests.created_at', 'desc')
            ->get();

        foreach ($confirmationReq as $key => $req) {
            // Constructing item_image and store_logo paths
            $confirmationReq[$key]->item_image = asset('storage/app/public/product') . '/' . ($req->item_image ?? 'default_image.png');
            $confirmationReq[$key]->store_logo = asset('storage/app/public/store') . '/' . ($req->store_logo ?? 'default_logo.png');

            // Fetching reviews for the store
            $reviews = StoreReview::where('store_id', $req->store_id)->get();
            $confirmationReq[$key]->reviews = $reviews;

            // Checking and processing reviews
            if ($reviews->isNotEmpty()) {
                foreach ($reviews as $reviewKey => $review) {
                    if (!empty($review->attachment)) {
                        // Decode JSON attachments
                        $attachments = json_decode($review->attachment, true);

                        if (is_array($attachments)) {
                            foreach ($attachments as $attachmentKey => $attachmentValue) {
                                $attachments[$attachmentKey] = asset('storage/app/public/review') . '/' . $attachmentValue;
                            }
                        }

                        // Update the attachment in the review
                        $reviews[$reviewKey]->attachment = $attachments;
                    }
                }
            }
            if (isset($req->id)) {
                if ($req->assigned_status == 'Assigned' && $req->assigned_to != NULL) {
                    if ($req->assigned_type == 'vendor') {
                        // self assigned 
                        $vendorInfo = DB::table('vendors')->join('stores', 'stores.vendor_id', 'vendors.id')->where('stores.id',  $req->assigned_to)
                            ->select('vendors.*')
                            ->first();
                        // echo $req->id . ' - '; 
                        $confirmationReq[$key]->staff_name =  $vendorInfo  ? $vendorInfo->f_name . ' ' . $vendorInfo->l_name : null;
                        $confirmationReq[$key]->staff_role = 'Vendor';
                        $confirmationReq[$key]->staff_image = $vendorInfo  ? asset('storage/app/public/vendor') . '/' . $vendorInfo->image : null;
                        $confirmationReq[$key]->staff_contact = $vendorInfo  ? $vendorInfo->phone : null;
                    } else {
                        //staff
                        $staffInfo = DB::table('vendor_employees')->where('vendor_employees.id', $req->assigned_to)
                            ->join('employee_roles', 'employee_roles.id', 'vendor_employees.employee_role_id')
                            ->select('vendor_employees.*', 'employee_roles.name')
                            ->first();
                        if ($staffInfo) {
                            $confirmationReq[$key]->staff_name = $staffInfo->f_name . ' ' . $staffInfo->l_name;
                            $confirmationReq[$key]->staff_role = $staffInfo->name;
                            $confirmationReq[$key]->staff_image = asset('storage/app/public/vendor') . '/' . $staffInfo->image;
                            $confirmationReq[$key]->staff_contact = $staffInfo->phone;
                        } else {
                            $confirmationReq[$key]->staff_name = '';
                            $confirmationReq[$key]->staff_role = '';
                            $confirmationReq[$key]->staff_image = '';
                            $confirmationReq[$key]->staff_contact = '';
                        }
                    }
                } else {
                }
            } else {
                $confirmationReq[$key]->id = 0;
                $confirmationReq[$key]->vendor_id = 0;
                $confirmationReq[$key]->quoted_price = 0;
                $confirmationReq[$key]->qty = 1;
                $confirmationReq[$key]->status = 1;
                $confirmationReq[$key]->current_status = "Enquiry Sent";
                $confirmationReq[$key]->cancelled_by = null;
                $confirmationReq[$key]->cancel_reason = null;
                $confirmationReq[$key]->assigned_status = "Not Assigned";
                $confirmationReq[$key]->assigned_at = null;
                $confirmationReq[$key]->assigned_to = null;
                $confirmationReq[$key]->assigned_type = "N/A";
                $confirmationReq[$key]->confirmed_at = null;
                $confirmationReq[$key]->completed_at = null;
                $confirmationReq[$key]->tieup = 0;
                $confirmationReq[$key]->accepted_by_staff = 0;
                $confirmationReq[$key]->sales_commission = 0;
                // $confirmationReq[$key]->created_at = null;
                $confirmationReq[$key]->updated_at = null;
                $confirmationReq[$key]->staff_name = "";
                $confirmationReq[$key]->staff_role = "";
                $confirmationReq[$key]->staff_image = "";
                $confirmationReq[$key]->staff_contact = "";
            }
            if (isset($req->id)) {
                $confirmationReq[$key]->gatepass_exists  = GatePass::where('accepted_service_id', $req->id)->exists();
                $confirmationReq[$key]->quotation_exists = InServiceQuotation::where('service_id', $req->service_request_id)->exists();
            } else {
                $confirmationReq[$key]->gatepass_exists  = 0;
                $confirmationReq[$key]->quotation_exists = 0;
            }
        }
        // prx($confirmationReq);
        return $confirmationReq;
    }
}
if (!function_exists('_states')) {
    function _states()
    {
        return DB::table('states')->get();
    }
}
if (!function_exists('_getUserDetails')) {
    function _getUserDetails($uid, $uType = 'user')
    {
        if ($uType == 'store') {
            $uDet = DB::table('stores')
                ->where('id', $uid)
                ->first();
        } elseif ($uType == 'vendor') {
            $uDet = DB::table('vendors')
                ->where('id', $uid)
                ->first();
        } elseif ($uType == 'staff') {
            $uDet = DB::table('vendor_employees')
                ->where('id', $uid)
                ->first();
        } else {
            $uDet = DB::table('users')
                ->where('id', $uid)
                ->first();
        }
        return $uDet;
    }
}
if (!function_exists('_getCurrentServiceStatus')) {
    function _getCurrentServiceStatus($r_id)
    {
        $uDet = DB::table('accepted_service_requests')
            ->where('service_request_id', $r_id)
            ->where('vendor_id', \App\CentralLogics\Helpers::get_store_id())
            ->first();
        if (!$uDet) {
            return false;
        } else {
            return ucfirst($uDet->current_status);
        }
    }
}

if (!function_exists('_newEmpId')) {
    function _newEmpId($increment = false)
    {
        $store = Helpers::get_store_data();
        if ($store->prefix_status == 1 && $store->emp_prefix) {
            $id = $store->emp_prefix . $store->emp_id_serial + 1;
        } else {
            $id = $store->emp_id_serial + 1;
        }
        if ($increment == true) {
            $store->emp_id_serial = $store->emp_id_serial + 1;
            $store->save();
        }
        return $id;
    }
}
if (!function_exists('_getStoreShifts')) {
    function _getStoreShifts()
    {
        $store_id = Helpers::get_store_id();
        $shifts = StoreShift::where('store_id', $store_id)->get();

        return $shifts;
    }
}
if (!function_exists('_todayPayableSalary')) {
    function _todayPayableSalary()
    {
        $today = Carbon::today();
        $storeId = Helpers::get_store_id();

        $employees = VendorEmployee::where('store_id', $storeId)->get();

        $totalPayableToday = 0;

        foreach ($employees as $emp) {
            $payableToday = 0;
            $salaryType = $emp->salary_type;
            $baseSalary = (float) $emp->base_salary;

            if ($salaryType === 'Hourly') {
                // Timecard logic for today
                $records = DB::table('employee_time_cards')
                    ->where('emp_id', $emp->id)
                    ->whereDate('in_time', $today)
                    ->get();

                $totalSeconds = 0;

                foreach ($records as $record) {
                    if ($record->in_time && $record->out_time) {
                        $in = Carbon::parse($record->in_time);
                        $out = Carbon::parse($record->out_time);

                        if ($out->greaterThan($in)) {
                            $totalSeconds += $out->diffInSeconds($in);
                        }
                    }
                }

                $workedHours = $totalSeconds / 3600;
                $payableToday = $baseSalary * $workedHours;
            } elseif ($salaryType === 'Monthly') {
                $totalDays = Carbon::now()->daysInMonth;
                $perDaySalary = $totalDays ? ($baseSalary / $totalDays) : 0;

                // Check if today was a working day
                $attendance = Attendance::where([
                    'vendor_id'     => $storeId,
                    'employee_type' => 'vendor_employee',
                    'employee_id'   => $emp->id,
                    'day'           => $today->day,
                    'month'         => $today->month,
                    'year'          => $today->year,
                ])->first();

                if ($attendance && in_array($attendance->label, ['P', 'HDS', 'HDF'])) {
                    if (in_array($attendance->label, ['HDS', 'HDF'])) {
                        $payableToday = $perDaySalary / 2;
                    } else {
                        $payableToday = $perDaySalary;
                    }
                }
            } elseif ($salaryType === 'Task-Wise') {

                $tasks = StoreTask::where('employee_id', $emp->id)
                    ->where('status', 'Completed')
                    ->whereDate('updated_at', $today)
                    ->get();

                $payableToday = $tasks->sum('task_amount');
            }

            $totalPayableToday += $payableToday;
        }

        return round($totalPayableToday, 2);
    }
}
if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            [$f, $a] = explode('.', $perm);
            if (hasPermission($f, $a)) {
                return true;
            }
        }
        return false;
    }
}
if (! function_exists('hasAnyModulePermission')) {
    function hasAnyModulePermission(array $permissions): bool
    {
        // Vendor → allowed
        if (auth('vendor')->check()) {
            return true;
        }

        $user = Auth::guard('vendor_employee')->user();
        if (! $user) {
            return false;
        }

        // Fetch matching permissions
        $rows = DB::table('role_feature_permissions as rfp')
            ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->where('rfp.role_id', $user->employee_role_id)
            ->whereIn('f.name', $permissions)
            ->select('fp.free', 'f.master_module')
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        foreach ($rows as $row) {
            // ✅ Free permission → allow immediately
            if ((int) $row->free === 1) {
                return true;
            }

            // 🔒 Paid → check master module
            if (
                ! empty($row->master_module) &&
                Helpers::permission_check($row->master_module)
            ) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hasMasterModulePermission')) {
    function hasMasterModulePermission(string $masterModule): bool
    {
        if (Helpers::permission_check($masterModule)) {
            if (auth('vendor')->check()) {
                return true;
            }

            $user = Auth::guard('vendor_employee')->user();

            if (!$user) {
                return false;
            }

            $hasPermission =  DB::table('role_feature_permissions as rfp')
                ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
                ->join('features as f', 'fp.feature_id', '=', 'f.id')
                ->where('rfp.role_id', $user->employee_role_id)
                ->where('f.master_module', $masterModule)
                ->exists();

            return $hasPermission;
        } else {
            return false;
        }
    }
}
if (!function_exists('hasPermission')) {

    function hasPermission($feature, $action)
    {

        $masterModule = DB::table('features')
            ->where('name', $feature)
            ->value('master_module');

        if (
            auth('vendor')->check() &&
            !empty($masterModule) &&
            Helpers::permission_check($masterModule)
        ) {
            return true;
        }

        // check if free 
        $featureAction = DB::table('feature_permissions as fp')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->where('f.name', $feature)
            ->where('fp.action', $action)
            ->select('f.master_module', 'fp.free')
            ->first();

        if ($featureAction && $featureAction->free == 1) {
            return true;
        }

        $permissionRow = DB::table('role_feature_permissions as rfp')
            ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->where('f.name', $feature)
            ->where('fp.action', $action)
            ->select('f.master_module', 'fp.free')
            ->first();


        if (! $permissionRow || empty($permissionRow->master_module)) {
            return false;
        }


        if (empty($permissionRow->free) || $permissionRow->free != 1) {
            if (! Helpers::permission_check($permissionRow->master_module)) {
                return false;
            }
        }


        if (auth('vendor')->check()) {
            return true;
        }


        $user = Auth::guard('vendor_employee')->user();
        if (! $user) {
            return false;
        }

        return DB::table('role_feature_permissions as rfp')
            ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->where('rfp.role_id', $user->employee_role_id)
            ->where('f.name', $feature)
            ->where('fp.action', $action)
            ->exists();
    }
}

function _verifiedStoreBadge($store)
{
    $verifiedDoc = $store->id_doc || $store->gst_doc;
    return $verifiedDoc ? '<img src="' . asset('storage/app/public/util/verified_badge.jpeg') . '" style="width:32px;    position: absolute2;left: 3px;top: -3px; aspect-ratio:1;" alt="">' : '';
}
function _manualInvoice($id)
{
    $invoice = ManualInvoice::where("id", $id)->first();
    return $invoice;
}
function _manualInvoiceByInvoiceId($invoice_id)
{
    $invoice = ManualInvoice::where("invoice_id", $invoice_id)->first();
    return $invoice ?? null;
}
function _vendorOrStaffName($id)
{
    if ($id === 0) {
        $name = Helpers::get_store_data()->name;
    } else {
        $emp  = VendorEmployee::where("id", $id)->first();
        $name = $emp ? $emp->f_name .  ' ' . $emp->l_name . ' #' . ($emp->employee_id ?? $emp->id) : '';
    }
    return $name;
}
if (!function_exists('_savePettyCashBookEntry')) {
    function _savePettyCashBookEntry($amount, $type, $particular, $date = null, $invoice_id = null)
    {
        $cashbook = new CashBook();
        $cashbook->store_id = Helpers::get_store_id();
        $cashbook->entry_date = $date ?? date('Y-m-d');
        $cashbook->amount = $amount;
        $cashbook->particular = $particular;
        $cashbook->type = $type;
        $cashbook->invoice_id = $invoice_id;
        $cashbook->save();
    }
}
if (!function_exists('_saveDayBookEntry')) {
    function _saveDayBookEntry($amount, $type, $store_id, $particular, $invoice_id = null, $voucher_id = null, $date = null, $reference_number = null, $payment_mode = 'cash')
    {
        if ($date == null) {
            $date = NOW();
        }
        $voucher_number = null;
        $voucher = StoreVoucher::where('id', $voucher_id)->first();
        if ($voucher) {
            $voucher_number = $voucher->voucher_number;
        }
        $daybook = new DayBook();
        $daybook->amount = $amount;
        $daybook->store_id = $store_id;
        $daybook->type = $type;
        $daybook->particular = $particular;
        $daybook->invoice_id = $invoice_id;
        $daybook->voucher_id = $voucher_number;
        $daybook->entry_date = $date;
        $daybook->reference_number = $reference_number;
        $daybook->payment_mode = $payment_mode;
        $daybook->save();
    }
}
if (!function_exists('_todayPurchasesTotal')) {
    function _todayPurchasesTotal()
    {
        $today = Carbon::today();

        $manualPaidInvoices = ManualInvoice::where(['bill_to_type' => 'vendor', 'bill_to' => Helpers::get_store_id(), 'payment_status' => 'Paid'])->whereDate('updated_at', $today)->get()->toArray();
        //    prx( $manualPaidInvoices);
        $manualPaidAmount = 0;
        if (!empty($manualPaidInvoices)) {
            foreach ($manualPaidInvoices as $key => $value) {
                $manualPaidAmount += $value['total_amount'];
            }
        }
        return $manualPaidAmount;
    }
}
if (!function_exists('_todaySalesTotal')) {
    function _todaySalesTotal()
    {
        $today = Carbon::today();
        // prx($today);

        $manualPaidInvoices = ManualInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])->whereDate('updated_at', $today)->get()->toArray();
        //   prx( $manualPaidInvoices );
        $serviePaidInvoices = ServiceInvoice::where(['vendor_id' => Helpers::get_store_id(), 'payment_status' => 'Paid'])->whereDate('updated_at', $today)->get()->toArray();
        $manualPaidAmount = 0;
        $servicePaidAmount = 0;
        if (!empty($manualPaidInvoices)) {
            foreach ($manualPaidInvoices as $key => $value) {
                $manualPaidAmount += $value['total_amount'];
            }
        }
        if (!empty($serviePaidInvoices)) {
            foreach ($serviePaidInvoices as $key => $value) {
                $servicePaidAmount += $value['total_amount'];
            }
        }

        return $servicePaidAmount + $manualPaidAmount;
    }
}
if (!function_exists('_formatted_datetime')) {
    function _formatted_datetime($datetime, $type = 'full')
    {
        $date = new DateTime($datetime);
        if ($type == 'date') {
            $formattedDate = $date->format('jS F Y');
        } else {
            $formattedDate = $date->format('h:i A, jS F Y');
        }
        return $formattedDate;
    }
}
if (!function_exists('_formatted_date')) {
    function _formatted_date($datetime)
    {

        $date = new DateTime($datetime);

        $formattedDate = $date->format('jS F Y');

        return $formattedDate;
    }
}
if (!function_exists('_monthNYear')) {
    function _monthNYear($datetime)
    {

        $date = new DateTime($datetime);

        $formattedDate = $date->format('F Y');

        return $formattedDate;
    }
}

if (!function_exists('_getStoreHolidays')) {
    function _getStoreHolidays($storeId, $type = null, $limit = null)
    {
        $today = now()->toDateString();

        //  Global holidays with optional override by store
        $global = DB::table('holidays')
            ->leftJoin('holiday_overrides', function ($join) use ($storeId) {
                $join->on('holidays.id', '=', 'holiday_overrides.holiday_id')
                    ->where('holiday_overrides.vendor_id', '=', $storeId);
            })
            ->where('holidays.is_global', 1)
            ->where(function ($q) {
                $q->whereNull('holiday_overrides.is_deleted')
                    ->orWhere('holiday_overrides.is_deleted', 0);
            })
            ->selectRaw('
            holidays.id as holiday_id,
            COALESCE(holiday_overrides.id, holidays.id) as id,
            COALESCE(holiday_overrides.custom_title, holidays.title) as title,
            COALESCE(holiday_overrides.custom_date, holidays.date) as date
        ')
            ->get();

        //  store's own holidays
        $custom = DB::table('holidays')
            ->where('vendor_id', $storeId)
            ->where('is_global', 0)
            ->select([
                'id as holiday_id',
                'id',
                'title',
                'date'
            ])
            ->get();

        $holidays = $global->merge($custom);

        if ($type === 'upcoming') {
            $holidays = $holidays->filter(function ($h) use ($today) {
                return $h->date > $today;
            });
        }

        $holidays = $holidays->sortBy('date')->values();

        if ($limit) {
            $holidays = $holidays->take($limit);
        }

        return $holidays;
    }
}
if (!function_exists('_getGoogleAds')) {
    function _getGoogleAds()
    {
        $ads = GoogleAd::all();
        return $ads ?? [];
    }
}
if (!function_exists('_cleanPhoneNumber')) {
    function _cleanPhoneNumber($phone)
    {
        $digitsOnly = preg_replace('/\D/', '', $phone);

        return substr($digitsOnly, -10);
    }
}
if (!function_exists('_saveUnitIfNotExist')) {
    function _saveUnitIfNotExist($unitInput, $getField = 'id')
    {
        if (is_numeric($unitInput)) {
            $inv_unit = (int) $unitInput;
            if ($getField == 'name') {
                return Unit::where('id', $inv_unit)->first()->unit;
            }
        } else {
            $existing = Unit::where('unit', 'like', "%{$unitInput}%")->first();
            if ($existing) {
                $inv_unit = $existing->id;
            } else {
                $unit = new Unit();
                $unit->unit = $unitInput;
                $unit->save();
                $inv_unit = $unit->id;
            }
            if ($getField == 'name') {
                return  $unitInput;
            }
        }

        return $inv_unit;
    }
}
if (!function_exists('_unitNaneById')) {
    function _unitNaneById($unitId)
    {
        return Unit::where('id', $unitId)->first()->unit;
    }
}
if (!function_exists('_auditLogs')) {
    function _auditLogs($action)
    {
        $created_by = auth('vendor_employee')->check() ? Helpers::get_loggedin_user()->id : 0;
        $log = new AuditLog();
        $log->store_id = Helpers::get_store_id();
        $log->created_by = $created_by;
        $log->action = $action;
        $log->save();
    }
}
if (!function_exists('_price')) {
    function _price($rawPrice, $method = '', $decimal = 3)
    {
        // ✅ Force correct types
        $rawPrice = (float) str_replace(',', '', $rawPrice);
        $decimal = (int) $decimal;

        if ($method === 'ceil') {
            $rawPrice = ceil($rawPrice);
        } elseif ($method === 'floor') {
            $rawPrice = floor($rawPrice);
        } elseif ($method === 'round') {
            $rawPrice = round($rawPrice, $decimal);
        } else {
            $rawPrice = $rawPrice;
        }

        $formattedPrice = number_format($rawPrice, $decimal);

        return \App\CentralLogics\Helpers::currency_symbol() . $formattedPrice;
    }
}

if (!function_exists('_quotationExist')) {
    function _quotationExist($serviceId)
    {
        return DB::table('in_service_quotations')->where('service_id', $serviceId)->exists();
    }
}
if (!function_exists('_gatepassExist')) {
    function _gatepassExist($serviceId)
    {

        return DB::table('gate_passes')->where('service_id', $serviceId)->exists();
    }
}
if (!function_exists('_customerDetByserviceId')) {
    function _customerDetByserviceId($serviceId)
    {

        return DB::table('users')
            ->join('service_requests', 'service_requests.user_id', 'users.id')
            ->where('service_requests.id', $serviceId)->first();
    }
}

if (!function_exists('_staffDetByserviceId')) {
    function _staffDetByserviceId($serviceId)
    {

        return  DB::table('vendor_employees')
            ->join('accepted_service_requests', 'accepted_service_requests.assigned_to', 'vendor_employees.id')
            ->where('accepted_service_requests.service_request_id', $serviceId)->first();
    }
}

function _checkIfTieUp($service_id)
{
    $aexist =  AcceptedServiceRequest::where('service_request_id', $service_id)->where('tieup', 1)->exists();
    if ($aexist) {
        return true;
    } else {
        return false;
    }
}

if (!function_exists('prx')) {
    function prx($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }
}


if (!function_exists('_calcDeliveryCharge')) {
    function _calcDeliveryCharge($address, $store, $user, $coupon_code = null, $order_type = 'delivery')
    {
        $user_latitude =  $address->latitude;
        $user_longitude =  $address->longitude;

        $store_latitude =  $store->latitude;
        $store_longitude =  $store->longitude;

        $earthRadius = 6371;

        $distance = $earthRadius * acos(
            cos(deg2rad($user_latitude)) *
                cos(deg2rad($store_latitude)) *
                cos(deg2rad($store_longitude) - deg2rad($user_longitude)) +
                sin(deg2rad($user_latitude)) *
                sin(deg2rad($store_latitude))
        );
        $coupon = null;
        $maximum_shipping_charge = 0;
        $delivery_charge = null;
        $free_delivery_by = null;
        $distance_data = 1015.810;
        $increased = 0;
        $coupon_created_by = null;
        $maximum_shipping_charge = 0;

        $data =  DMVehicle::active()->where(function ($query) use ($distance_data) {
            $query->where('starting_coverage_area', '<=', $distance_data)->where('maximum_coverage_area', '>=', $distance_data)
                ->orWhere(function ($query) use ($distance_data) {
                    $query->where('starting_coverage_area', '>=', $distance_data);
                });
        })
            ->orderBy('starting_coverage_area')->first();
        $extra_charges = (float) (isset($data) ? $data->extra_charges  : 0);
        $zone = null;
        if ($user_latitude && $user_longitude) {
            $point = new Point($user_latitude, $user_longitude);
            $store = Store::with('discount')->selectRaw('*')->where('id', $store->id)->first();

            $zone_id =  [$store->zone_id];
            $zone = Zone::where('id', $zone_id)->whereContains('coordinates', new Point($user_latitude, $user_longitude, POINT_SRID))->first();
        }

        if (!$zone) {
            $errors = [];
            // return  'location out of coverage';
            $response['charges'] = 0;
            $response['error'] =  true;
            $response['msg'] = 'Location out of coverage. Please try with another address.';
            return  $response;
        }
        if ($zone && $zone->increased_delivery_fee_status == 1) {
            $increased = $zone->increased_delivery_fee ?? 0;
        }

        if ($coupon_code) {
            $coupon = Coupon::active()->where(['code' => $coupon_code])->first();
            if (isset($coupon)) {
                $staus = CouponLogic::is_valide($coupon, $user->id, $store->id);

                $coupon_created_by = $coupon->created_by;
                if ($coupon->coupon_type == 'free_delivery') {
                    $delivery_charge = 0;
                    $free_delivery_by =  $coupon_created_by;
                    $coupon_created_by = null;
                }
            }
        }

        $module_wise_delivery_charge = $store->zone->modules()->where('modules.id', 5)->first();
        if ($module_wise_delivery_charge) {
            $per_km_shipping_charge = $module_wise_delivery_charge->pivot->per_km_shipping_charge;
            $minimum_shipping_charge = $module_wise_delivery_charge->pivot->minimum_shipping_charge;
            $maximum_shipping_charge = $module_wise_delivery_charge->pivot->maximum_shipping_charge;
        } else {
            $per_km_shipping_charge = (float)BusinessSetting::where(['key' => 'per_km_shipping_charge'])->first()->value;
            $minimum_shipping_charge = (float)BusinessSetting::where(['key' => 'minimum_shipping_charge'])->first()->value;
        }


        if ($order_type != 'take_away' && !$store->free_delivery &&  !isset($delivery_charge) &&  $store->self_delivery_system == 1) {
            $per_km_shipping_charge = $store->per_km_shipping_charge;
            $minimum_shipping_charge = $store->minimum_shipping_charge;
            $maximum_shipping_charge = $store->maximum_shipping_charge;
            $extra_charges = 0;
            $vehicle_id = null;
            $increased = 0;
        }

        if ($store->free_delivery || $free_delivery_by == 'vendor') {
            $per_km_shipping_charge = $store->per_km_shipping_charge;
            $minimum_shipping_charge = $store->minimum_shipping_charge;
            $maximum_shipping_charge = $store->maximum_shipping_charge;
            $extra_charges = 0;
            $increased = 0;
        }

        $original_delivery_charge = (($distance * $per_km_shipping_charge) > $minimum_shipping_charge) ? $distance * $per_km_shipping_charge  : $minimum_shipping_charge;

        if ($order_type == 'take_away') {
            $per_km_shipping_charge = 0;
            $minimum_shipping_charge = 0;
            $maximum_shipping_charge = 0;
            $extra_charges = 0;
            $distance_data = 0;
            $vehicle_id = null;
            $original_delivery_charge = 0;
            $increased = 0;
        }

        if ($maximum_shipping_charge  >= $minimum_shipping_charge  && $original_delivery_charge >  $maximum_shipping_charge) {
            $original_delivery_charge = $maximum_shipping_charge;
        } else {
            $original_delivery_charge = $original_delivery_charge;
        }

        if (!isset($delivery_charge)) {
            $delivery_charge = ($distance * $per_km_shipping_charge > $minimum_shipping_charge) ? $distance * $per_km_shipping_charge : $minimum_shipping_charge;
            if ($maximum_shipping_charge  >= $minimum_shipping_charge  && $delivery_charge >  $maximum_shipping_charge) {
                $delivery_charge = $maximum_shipping_charge;
            } else {
                $delivery_charge = $delivery_charge;
            }
        }
        $original_delivery_charge = $original_delivery_charge + $extra_charges;
        $delivery_charge = $delivery_charge + $extra_charges;


        if ($increased > 0) {
            if ($delivery_charge > 0) {
                $increased_fee = ($delivery_charge * $increased) / 100;
                $delivery_charge = $delivery_charge + $increased_fee;
            }
            if ($original_delivery_charge > 0) {
                $increased_fee = ($original_delivery_charge * $increased) / 100;
                $original_delivery_charge = $original_delivery_charge + $increased_fee;
            }
        }

        $total_addon_price = 0;
        $product_price = 0;
        $store_discount_amount = 0;
        $flash_sale_vendor_discount_amount = 0;
        $flash_sale_admin_discount_amount = 0;
        $store_discount_amount = 0;
        $product_data = [];

        $order_details = [];
        $order = new Order();
        $order->id = 100000 + Order::count() + 1;
        if (Order::find($order->id)) {
            $order->id = Order::orderBy('id', 'desc')->first()->id + 1;
        }



        //Added DM TIPS
        $dm_tips_manage_status = BusinessSetting::where('key', 'dm_tips_status')->first()->value;
        if ($dm_tips_manage_status == 1) {
            $order->dm_tips = $request->dm_tips ?? 0;
        } else {
            $order->dm_tips = 0;
        }

        //Added service charge
        $additional_charge_status = BusinessSetting::where('key', 'additional_charge_status')->first()->value;
        $additional_charge = BusinessSetting::where('key', 'additional_charge')->first()->value;
        if ($additional_charge_status == 1) {
            $order->additional_charge = $additional_charge ?? 0;
        } else {
            $order->additional_charge = 0;
        }

        $carts = Cart::where('user_id', $user->id)->where('is_guest', 0)->where('module_id', 5)
            ->get()->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                return $data;
            });

        foreach ($carts as $c) {
            $product = Item::find($c['item_id']);
            $product = Item::hydrate(
                DB::table('items')->where('id',  $c['item_id'])->get()->toArray()
            )->first();

            if ($product) {

                if (count(json_decode($product->variations, true)) > 0 && count($c['variation']) > 0) {
                    $variant_data = Helpers::variation_price($product, json_encode($c['variation']));
                    $price = $variant_data['price'];
                    $product_price += $price * $c['quantity'];
                } else {
                    $price = $product->price;
                    $product_price += $price * $c['quantity'];
                }
                $product->tax = $store->tax;
                // $product = Helpers::product_data_formatting($product, false, false, app()->getLocale());
            }
        }
        $order->discount_on_product_by = 'vendor';
        $store_discount = Helpers::get_store_discount($store);
        if (isset($store_discount)) {
            $order->discount_on_product_by = 'admin';
            if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
                $store_discount_amount = 0;
            }

            if ($store_discount['max_discount'] != 0 && $store_discount_amount > $store_discount['max_discount']) {
                $store_discount_amount = $store_discount['max_discount'];
            }
        }
        $coupon_discount_amount = $coupon ? CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount) : 0;


        $total_price = $product_price + $total_addon_price - $total_addon_price - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount  - $coupon_discount_amount;

        $tax = ($store->tax > 0) ? $store->tax : 0;
        $order->tax_status = 'excluded';

        $tax_included = BusinessSetting::where(['key' => 'tax_included'])->first() ?  BusinessSetting::where(['key' => 'tax_included'])->first()->value : 0;
        if ($tax_included ==  1) {
            $order->tax_status = 'included';
        }

        $total_tax_amount = Helpers::product_tax($total_price, $tax, $order->tax_status == 'included');

        $tax_a = $order->tax_status == 'included' ? 0 : $total_tax_amount;

        // prx($product_price );
        if ($store->minimum_order > $product_price + $total_addon_price) {

            $response['charges'] =  0;
            $response['error'] =  true;
            $response['msg'] = translate('messages.you_need_to_order_at_least') . $store->minimum_order . ' ' . Helpers::currency_code();
            return  $response;
        }

        $free_delivery_over = BusinessSetting::where('key', 'free_delivery_over')->first()->value;
        if (isset($free_delivery_over)) {
            if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount) {
                $order->delivery_charge = 0;
                $free_delivery_by = 'admin';
            }
        }

        if ($store->free_delivery) {
            $order->delivery_charge = 0;
            $free_delivery_by = 'vendor';
        }

        if ($coupon) {
            if ($coupon->coupon_type == 'free_delivery') {
                if ($coupon->min_purchase <= $product_price + $total_addon_price - $store_discount_amount - $flash_sale_admin_discount_amount - $flash_sale_vendor_discount_amount) {
                    $order->delivery_charge = 0;
                    $free_delivery_by = $coupon->created_by;
                }
            }
            $coupon->increment('total_uses');
        }
        $order->coupon_created_by = $coupon_created_by;
        $order->coupon_discount_amount = round($coupon_discount_amount, config('round_up_to_digit'));
        $order->coupon_discount_title = $coupon ? $coupon->title : '';

        $order->store_discount_amount = round($store_discount_amount, config('round_up_to_digit'));
        $order->tax_percentage = $tax;
        $order->total_tax_amount = round($total_tax_amount, config('round_up_to_digit'));

        $order->order_amount = round($total_price + $tax_a + $order->delivery_charge, config('round_up_to_digit'));
        $order->free_delivery_by = $free_delivery_by;

        $order->flash_admin_discount_amount = round($flash_sale_admin_discount_amount, config('round_up_to_digit'));
        $order->flash_store_discount_amount = round($flash_sale_vendor_discount_amount, config('round_up_to_digit'));

        //DM TIPS
        $order->order_amount = $order->order_amount + $order->dm_tips + $order->additional_charge;
        if ($order->order_amount > $store->delivery_charges_on) {
            $delivery_charge = 0;
        }

        $response['charges'] = round($delivery_charge, config('round_up_to_digit')) ?? 0;
        $response['msg'] = '';
        $response['error'] =  false;
        return  $response;
    }
}
if (!function_exists('_getIdsFrist')) {
    function _getIdsFrist($item_id)
    {
        try {
            $ids = DB::table('items')->where('id', $item_id)->first();
            if ($ids) {
                return $ids->store_ids;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return '';
    }
}
if (!function_exists('_getCatName')) {
    function _getCatName($cat_id)
    {
        try {
            $cat  = Category::where('id', $cat_id)->first();
            if ($cat) {
                return $cat->name;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return '';
    }
}
if (!function_exists('_trackStoreIds')) {
    function _trackStoreIds($action, $ids, $item_id, $item_name, $user, $old_ids)
    {
        try {
            $tracker = new Tracker();
            $tracker->action = $action;
            $tracker->ids = $ids;
            $tracker->old_ids = $old_ids;
            $tracker->item_id = $item_id;
            $tracker->item_name = $item_name;
            $tracker->user = $user;
            $tracker->save();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
if (!function_exists('_getProductsByCat')) {
    function _getProductsByCat($cid)
    {
        $allCats = Category::where('parent_id', $cid)->pluck('id')->toArray();
        array_push($allCats, $cid);
        if (Config::get('module.current_module_id') == 6) {
            $data =  DB::table('items')
                ->join('stores', function ($join) {
                    $join->on(DB::raw('FIND_IN_SET(stores.id, items.store_ids)'), '>', DB::raw('0'));
                })
                ->where('items.is_approved', 1)
                ->whereIn('stores.zone_id',  json_decode(session('zone_ids'), true))
                ->where(['stores.module_id' => session('moduleId'), 'stores.active' => 1, 'items.status' => 1])
                ->whereIn('items.category_id', $allCats)
                ->select('items.*', 'stores.zone_id', 'stores.active as store_open', 'stores.delivery_time')
                ->groupBy('items.id')
                ->get()->toArray();

            return $data;
            // prx($data); 
        } else {
            return DB::table('items')
                ->join('stores', 'items.store_id', 'stores.id')
                ->where('items.is_approved', 1)
                ->whereIn('stores.zone_id',  json_decode(session('zone_ids'), true))
                ->where(['stores.module_id' => session('moduleId'), 'stores.active' => 1,  'items.status' => 1])
                ->whereIn('items.category_id', $allCats)
                ->select('items.*', 'stores.zone_id', 'stores.active as store_open', 'stores.delivery_time')
                ->distinct('items.id')
                ->get()->toArray();
        }
    }
}

if (!function_exists('_userInfo')) {
    function _userInfo($id, $type)
    {
        if ($type == 'staff') {
            return VendorEmployee::find($id);
        }
    }
}
if (!function_exists('_randomAddCartMsg')) {
    function _randomAddCartMsg()
    {
        $array = [
            'Great choice! This item is now in your cart.',
            'Success! Your item is now riding in your cart.',
            'Cart +1! Your item is safely in your cart.',
            'Great choice! This item is now in your cart.',
        ];
        $randomIndex = array_rand($array);
        $randomValue = $array[$randomIndex];
        return $randomValue;
    }
}
if (!function_exists('_storeAccountType')) {
    function _storeAccountType()
    {
        $store = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        return (!$store || $store->account_type  == 'normal')  ? 'normal' : $store->account_type;
    }
}

if (!function_exists('_getStoreConfigByKey')) {
    function _getStoreConfigByKey($key)
    {
        $store_id = Helpers::get_store_id();
        $config = StoreConfig::where('store_id', $store_id)->first();

        return $config ?  $config[$key] : null;
    }
}
function _getFileTypeLabel($filename)
{
    $ext = trim(strtolower(explode('.', $filename)[count(explode('.', $filename)) - 1]));

    $labels = [
        'pdf' => 'PDF Document',
        'doc' => 'Word Document',
        'docx' => 'Word Document',
        'xls' => 'Excel Spreadsheet',
        'xlsx' => 'Excel Spreadsheet',
        'jpg' => 'Image (JPG)',
        'jpeg' => 'Image (JPEG)',
        'png' => 'Image (PNG)',
        'gif' => 'Image (GIF)',
        'zip' => 'ZIP Archive',
    ];

    return $labels[$ext] ?? strtoupper($ext) . ' File';
}
if (!function_exists('_termsAndConditionsUrl')) {
    function _termsAndConditionsUrl($type, $vId, $vendorType)
    {
        if ($type == 'admin_to_user') {
            $url   = route('user-terms-and-conditions');
        } elseif ($type == 'vendor_to_user') {

            $cond = DB::table('vendor_terms_conditions')->where('vendor_id', $vId)->first();
            if ($cond) {
                $url  = route('vendor-terms-conditions', [$vId]);
            } else {
                $url = '';
            }
        } elseif ($type == 'admin_to_vendor') {
            $url = route('store-terms-and-conditions', [$vendorType]);
        } else {
            return false;
        }
        return  $url;
    }
}
if (!function_exists('_vendorSocialMedia')) {
    function _vendorSocialMedia($key, $store_id)
    {
        $store = Store::find($store_id);
        return $store[$key];
    }
}
if (!function_exists('_getSpecialProduct')) {
    function _getSpecialProduct($zone_id)
    {
        $data['special_product'] = null;
        $homepageItemId = BusinessSetting::where('key', 'homepage_item')->first();
        $item = Item::where('id', $homepageItemId->value)->with('store')->first();
        if ($item) {
            $module = $item->module_id;
            if ($module == 5) {
                $item = DB::table('items')->join('stores', 'stores.id', 'items.store_id')->join('categories', 'categories.id', 'items.category_id')->where('items.id', $homepageItemId->value)->whereIn('stores.zone_id',  json_decode($zone_id, true))->where('items.status', 1)->where('stores.status', 1)->where('items.is_approved', 1)->where('items.module_id', 5)
                    ->select('items.*', 'stores.delivery_time', 'categories.slug as cat_slug')
                    ->first();
            } else {
                $item = Item::withoutGlobalScopes()
                    ->join('stores', function ($join) use ($zone_id) {
                        $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                        $join->whereIn('stores.zone_id',  json_decode($zone_id, true));
                    })
                    ->where('items.id', $homepageItemId->value)
                    ->where('items.status', 1)
                    ->join('categories', 'categories.id', 'items.category_id')
                    ->select('items.*')
                    ->first();
            }
        }
    }
}
if (!function_exists('_getPopularService')) {
    function _getPopularService($zone_id)
    {
        $items =  DB::table('service_requests')
            ->join('items', 'service_requests.item_id', 'items.id')
            ->join('stores', function ($join) use ($zone_id) {
                $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
                $join->whereIn('stores.zone_id',  json_decode($zone_id, true));
                $join->where(['stores.module_id' => 6, 'stores.active' => 1, 'items.status' => 1]);
            })
            ->join('categories', 'categories.id', 'items.category_id')
            ->whereNull('categories.added_by')
            ->select('categories.name as cat_name', 'categories.slug as cat_slug', 'items.*', 'stores.zone_id', 'stores.active as store_open', 'stores.delivery_time', 'service_requests.item_id', DB::raw('COUNT(service_requests.item_id) as total_requests'))
            ->groupBy('items.id')
            ->orderBy('total_requests', 'desc')
            ->take(8)
            ->get();

        return $items;
    }
}
if (!function_exists('_vendorTandC')) {
    function _vendorTandC($vId)
    {
        $cond = DB::table('vendor_terms_conditions')->where('vendor_id', $vId)->where('type', 'for_customer')->first();
        return  $cond ? $cond->terms_n_conditons  : null;
    }
}
if (!function_exists('_vendorTandCForQuotation')) {
    function _vendorTandCForQuotation($vId)
    {
        $cond = DB::table('vendor_terms_conditions')->where('vendor_id', $vId)->where('type', 'for_quotation')->first();
        return  $cond ? $cond->terms_n_conditons  : null;
    }
}
if (!function_exists('_itemExistInCart')) {
    function _itemExistInCart($prId, $variation)
    {

        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $is_guest = auth('web')->user() ? 0 : 1;
        $item_id = $prId;
        $item =  Item::find($item_id);
        $model = 'App\Models\Item';
        // $variation = $item->variations;

        // return json_encode($variation);

        // if($variation != '"[]"'){
        // $variation =  json_encode('[' . $variation .']' )   ;
        // return $variation;
        // } 

        $cart = Cart::where('item_id', $item_id)->where('variation', $variation)->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', 5)->first();
        if ($cart) {
            return $cart->id;
        } else {
            return false;
        }
    }
}

if (!function_exists('_cartCount')) {
    function _cartCount()
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $is_guest = auth('web')->user() ? 0 : 1;
        return  Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', 5)->count();
    }
}

if (!function_exists('_limitDesc')) {
    function _limitDesc($desc, $charLimit)
    {
        if (strlen($desc) > $charLimit) {
            return ucfirst(substr($desc, 0, $charLimit)) . '...';
        } else {
            return ucfirst($desc);
        }
    }
}

if (!function_exists('_generateOrderInvoiceId')) {
    function _generateOrderInvoiceId($store_id)
    {
        $store = Store::find($store_id);
        $store_serial = $store->bill_serial_number;
        $store_prefix = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $store->name)), 0, 3);

        if (date('m') > 3) { // march
            $year = date('y') . '-' . date('y') + 1;
        } else {
            $year = date('y') - 1 . '-' . date('y');
        }

        $store->bill_serial_number = (int) $store_serial + 1;
        $store->save();

        $invoice_id = $store_prefix . '_' . $year . '_' .  $store_serial;
        return $invoice_id;
    }
}
if (!function_exists('_quickAccessModules')) {
    function _quickAccessModules() {}
}
if (!function_exists('_receipt_serial_number')) {
    function _receipt_serial_number()
    {
        $store = Helpers::get_store_data();
        $data['receipt_serial_number'] = Helpers::generate_RR_serial_number($store, 'view');

        return  $data['receipt_serial_number'];
    }
}
if (!function_exists('_receipt_prefix')) {
    function _receipt_prefix()
    {
        $store = Helpers::get_store_data();
        $data['receipt_prefix'] = Helpers::generate_RR_prefix($store);
        return  $data['receipt_prefix'];
    }
}
if (!function_exists('_discountedPrice')) {
    function _discountedPrice($price, $discountValue, $discountType)
    {
        if ($discountValue == 0) {
            $discountedPrice = $price;
        } else {
            if ($discountType == 'percent') {
                $discountedPrice = $price - ($price * ($discountValue / 100));
            } else {
                $discountedPrice = $price - $discountValue;
            }
        }

        return round($discountedPrice, 1);
    }
}

if (!function_exists('_taxIncludedPrice')) {
    function _taxIncludedPrice($price, $taxValue, $type = 'ceil')
    {
        if ($taxValue == 0) {
            $taxIncludedPrice = $price;
        } else {
            $taxIncludedPrice = $price + ($price * ($taxValue / 100));
        }

        if ($type == 'ceil') {
            return ceil($taxIncludedPrice);
        } else {
            return $taxIncludedPrice;
        }
    }
}
if (!function_exists('_taxPrice')) {
    function _taxPrice($price, $taxValue, $type = 'actual')
    {
        if ($taxValue == 0) {
            $taxPrice = 0;
        } else {
            $taxPrice = $price * ($taxValue / 100);
        }
        if ($type == 'ceil') {
            return ceil($taxPrice);
        } else {
            return round($taxPrice, 1);
        }
    }
}
if (!function_exists('_taxCalcluation')) {
    function _taxCalcluation($price, $taxValue)
    {
        $price_excluding_gst = $price / (1 + $taxValue / 100);
        $gst_amount = $price - $price_excluding_gst;

        $data['price_excluding_gst'] = $price_excluding_gst;
        $data['gst_amount'] = $gst_amount;

        return $data;
    }
}

if (!function_exists('_itemExistInWishlist')) {
    function _itemExistInWishlist($prid)
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $wishlist = Wishlist::where('user_id', $user_id)->where('item_id', $prid)->whereNull('store_id')->first();
        return !empty($wishlist);
    }
}
if (!function_exists('_storeExistInWishlist')) {
    function _storeExistInWishlist($strId)
    {
        $user_id = auth('web')->user() ? auth('web')->user()->id : session()->get('guest_id');
        $wishlist = Wishlist::where('user_id', $user_id)->where('store_id', $strId)->whereNull('item_id')->first();
        return !empty($wishlist);
    }
}
if (!function_exists('_isStoreActive')) {
    function _isStoreActive($prId)
    {
        $storeStts = DB::table('items')->join('stores', 'stores.id', 'items.store_id')->where('items.id', $prId)->select('stores.active')->first();
        return $storeStts;
    }
}
if (!function_exists('_roundOff')) {
    function _roundOff($amount)
    {
        $decimal = $amount - floor($amount);
        if ($decimal < 0.5) {
            $data['remaining_amount'] = $decimal;
            $data['final_amount'] = floor($amount);
        } else {
            $data['remaining_amount'] = $decimal;
            $data['final_amount'] = ceil($amount);
        }
        return $data;
    }
}
if (!function_exists('_getCatSlugByItemId')) {
    function _getCatSlugByItemId($itemId)
    {
        $ct = DB::table('items')->join('categories', 'categories.id', 'items.category_id')->where('items.id', $itemId)->select('categories.slug as cat_slug')->first();
        return $ct ? $ct->cat_slug : '';
    }
}
if (!function_exists('_navSubCats')) {
    function _navSubCats($catId, $module_id)
    {
        // prx($module_id);
        if ($module_id == 5) {
            $cats = Category::withCount(['products', 'childes' => function ($query) {
                $query->where('categories.status', 1); // Fully qualified
            }])
                ->where(['categories.status' => 1, 'categories.parent_id' => $catId]) // Fully qualified
                ->where(['categories.module_id' => session('moduleId')]) // Fully qualified
                ->orderBy('priority', 'desc')
                ->get();
        } else {
            // $allCats = [];
            // array_push($allCats, $catId);
            $allCats = Category::where('parent_id', $catId)
                ->pluck('id')
                ->toArray();
            array_push($allCats, $catId);

            $cats = DB::table('items')->join('categories', 'categories.id', 'items.category_id')->whereIn('items.category_id', $allCats)->select('items.*', 'categories.slug as cat_slug')->get();
        }
        return $cats;
    }
}
if (!function_exists('_getServiceAddrInfo')) {
    function _getServiceAddrInfo($service_id)
    {
        $formatted_addr  = [];
        $service = ServiceRequest::find($service_id);
        if ($service) {

            $user = User::find($service->user_id);
            if ($service->address_id) {
                $address = CustomerAddress::find($service->address_id);
                $formatted_addr['address']  = $address->address;
                $formatted_addr['phone']  = $address->contact_person_number;
                $formatted_addr['name']  = $address->contact_person_name;
            } else if ($service->address) {
                $formatted_addr['address']  = $service->address;
                $formatted_addr['phone']  = $user->phone;
                $formatted_addr['name']  = $user->f_name . ' ' . $user->l_name;
            } else {
                $address = CustomerAddress::where('user_id', $user->id)->first();
                if ($address) {
                    $formatted_addr['address']  = $address->address;
                    $formatted_addr['phone']  = $address->contact_person_number;
                    $formatted_addr['name']  = $address->contact_person_name;
                } else {
                    $formatted_addr['address']  = '';
                    $formatted_addr['phone']  = '';
                    $formatted_addr['name']  = '';
                }
            }
        }

        return $formatted_addr;
    }
}

if (!function_exists('_isSubscription')) {
    function _isSubscription()
    {
        $store_id = Helpers::get_store_id();
        return DB::table('vendor_subscriptions')
            ->where('vendor_id', $store_id)
            ->where('id', '!=', 15) // Basic plan
            ->where('plan_expiry', '>', now())
            ->exists();
    }
}
if (!function_exists('_nearbyStoresOptimized')) {
    function _nearbyStoresOptimized($zone_id, $limit = 9, $offset = 1)
    {
        $zoneIds = json_decode($zone_id, true);
        if (!is_array($zoneIds)) {
            $zoneIds = [$zoneIds];
        }

        $userLat = session('latitude');
        $userLng = session('longitude');

        if (!$userLat || !$userLng) {
            return collect();
        }

        // Subscribed store IDs
        $subscribedStoreIds = DB::table('vendor_subscriptions')
            ->where('plan_expiry', '>', now())
            ->pluck('vendor_id')
            ->toArray();

        // Distance SQL (safe acos)
        $distanceSql = "
        (6371 * acos(
            LEAST(1, GREATEST(-1,
                cos(radians(?)) *
                cos(radians(stores.latitude)) *
                cos(radians(stores.longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(stores.latitude))
            ))
        ))
    ";

        return Store::select('stores.*')
            ->selectRaw("$distanceSql AS distance", [$userLat, $userLng, $userLat])
            ->selectRaw("
            CASE 
                WHEN stores.id IN (" . implode(',', $subscribedStoreIds ?: [0]) . ")
                THEN 1 ELSE 0 
            END AS subscribed
        ")
            ->leftJoin('store_enabled_modules', 'store_enabled_modules.store_id', '=', 'stores.id')
            ->where([
                'stores.active' => 1,
                'stores.status' => 1,
                'stores.module_id' => 6
            ])
            ->whereIn('stores.zone_id', $zoneIds)
            ->whereNotNull('stores.latitude')
            ->whereNotNull('stores.longitude')
            ->groupBy('stores.id')
            ->orderByDesc('subscribed') // subscribed first
            ->orderBy('distance')       // nearest first
            ->paginate($limit, ['*'], 'page', $offset);
    }
}

if (!function_exists('_nearbyStoresOld')) {
    function _nearbyStoresOld($zone_id, $limit = null, $paginate = 9)
    {
        $zoneIds = json_decode($zone_id, true);

        $userLat = session('latitude');
        $userLng = session('longitude');

        // Subscribed store IDs
        $activeStores = DB::table('stores as s')
            ->join('vendor_subscriptions as vs', 'vs.vendor_id', '=', 's.id')
            ->where('vs.plan_expiry', '>', now())
            ->select('s.id')
            ->get();

        $subscribedStoreIds = $activeStores->pluck('id')->map(fn($id) => (int) $id)->toArray();

        // --- Subscribed stores in random order ---
        $subscribedQuery = Store::select('stores.*')
            ->leftJoin('store_enabled_modules', 'store_enabled_modules.store_id', 'stores.id')
            ->where([
                'stores.active' => 1,
                'stores.status' => 1,
                'stores.module_id' => 6
            ])
            ->whereIn('stores.zone_id', $zoneIds)
            ->whereIn('stores.id', $subscribedStoreIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("(6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance", [$userLat, $userLng, $userLat])
            ->groupBy('stores.id')
            ->inRandomOrder()
            ->get();

        // --- Non-subscribed stores by distance ---
        $nonSubscribedQuery = Store::select('stores.*')
            ->leftJoin('store_enabled_modules', 'store_enabled_modules.store_id', 'stores.id')
            ->where([
                'stores.active' => 1,
                'stores.status' => 1,
                'stores.module_id' => 6
            ])
            ->whereIn('stores.zone_id', $zoneIds)
            ->whereNotIn('stores.id', $subscribedStoreIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("(6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance", [$userLat, $userLng, $userLat])
            ->groupBy('stores.id')
            ->orderBy('distance', 'asc')
            ->get();

        // --- Add subscribed flag ---
        $subscribedQuery->transform(function ($store) {
            $store->subscribed = true;
            return $store;
        });

        $nonSubscribedQuery->transform(function ($store) {
            $store->subscribed = false;
            return $store;
        });

        // --- Merge both ---
        $allStores = $subscribedQuery->concat($nonSubscribedQuery);

        // --- Paginate manually ---
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $paginate;

        $currentItems = $allStores->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginated = new LengthAwarePaginator(
            $currentItems,
            $allStores->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginated;
    }
}

if (!function_exists('_nearbyStoresMonthlyBillingWise')) {
    function _nearbyStoresMonthlyBillingWise($zone_id, $limit = null, $paginate = 8)
    {
        $zoneIds = json_decode($zone_id, true);

        $userLat = session('latitude');
        $userLng = session('longitude');

        // Get last month (billing month)
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd   = Carbon::now()->subMonth()->endOfMonth();

        $today = now();
        $lastMonthStart = $today->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $today->copy()->subMonth()->endOfMonth();
        $moduleStartGraceDate = $lastMonthEnd->copy()->subDay();

        // Paid last month
        $paidLastMonth = DB::table('manual_invoices')
            ->join('stores', 'stores.id', '=', 'manual_invoices.bill_to')
            ->where('manual_invoices.key_purpose', 'services_billing')
            ->where('manual_invoices.payment_status', 'Paid')
            ->whereBetween('manual_invoices.created_at', [$lastMonthStart, $lastMonthEnd])
            ->whereIn('stores.zone_id', $zoneIds)
            ->pluck('manual_invoices.bill_to')
            ->toArray();

        // Paid this month (only if after 9th)
        $paidThisMonth = [];
        if ($today->day > 9) {
            $thisMonthStart = $today->copy()->startOfMonth();
            $paidThisMonth = DB::table('manual_invoices')
                ->join('stores', 'stores.id', '=', 'manual_invoices.bill_to')
                ->where('manual_invoices.key_purpose', 'services_billing')
                ->where('manual_invoices.payment_status', 'Paid')
                ->whereBetween('manual_invoices.created_at', [$thisMonthStart, $today])
                ->whereIn('stores.zone_id', $zoneIds)
                ->pluck('manual_invoices.bill_to')
                ->toArray();
        }
        // Just activated modules after 2nd last day of prev month
        $newlyEnabledStores = DB::table('store_enabled_modules')
            ->join('stores', 'stores.id', '=', 'store_enabled_modules.store_id')
            ->whereDate('store_enabled_modules.created_at', '>=', $moduleStartGraceDate)
            ->whereIn('stores.zone_id', $zoneIds)
            ->pluck('store_enabled_modules.store_id')
            ->toArray();

        $paidStoreIds = array_unique(array_merge(
            $paidLastMonth,
            $paidThisMonth,
            $newlyEnabledStores
        ));
        $paidStoreIds = array_map('intval', $paidStoreIds); // ensure safe SQL usage

        // prx($paidStoreIds);

        $query = Store::select('stores.*')
            ->leftJoin('store_enabled_modules', 'store_enabled_modules.store_id', 'stores.id')
            ->where([
                'stores.active' => 1,
                'stores.status' => 1,
                'stores.module_id' => 6
            ])
            ->whereIn('zone_id', $zoneIds)
            ->where(function ($query) use ($paidStoreIds) {
                $query->whereIn('stores.id', $paidStoreIds)
                    ->orWhereNotIn('stores.id', $paidStoreIds);
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("( 6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )
            ) AS distance", [$userLat, $userLng, $userLat])->groupBy('stores.id');;

        // Order: paid first, then distance
        if (!empty($paidStoreIds)) {
            $query->orderByRaw("
        FIELD(stores.id, " . implode(',', $paidStoreIds) . ") DESC,
        distance ASC
    ");
        } else {
            $query->orderBy('distance', 'asc');
        }

        // Paid first
        if ($limit) {
            $data['nearby_stores'] = $query->take($limit)->paginate($paginate);
        } else {
            $data['nearby_stores'] = $query->paginate($paginate);
        }

        return $data['nearby_stores'];
    }
}
if (!function_exists('_selectedCity')) {
    function _selectedCity()
    {
        $city = session('customer_city') ?? 'Tirupati';
        return \Illuminate\Support\Str::slug($city);
    }
}

if (!function_exists('_navCats')) {
    function _navCats()
    {
        $cats = DB::table('categories')
            ->where(['position' => 0, 'status' => 1,  'parent_id' => 0, 'module_id' => 6])
            ->orderBy('priority', 'desc')->get();

        return $cats;
    }
}
if (!function_exists('_checkUser')) {
    function _checkUser($id, $type)
    {
        if ($type == 'vendor') {
            return Store::find($id);
        } else if ($type == 'user') {
            return User::find($id);
        }
    }
}
if (!function_exists('_getVrDetails')) {
    function _getVrDetails($vrTableId)
    {
        $variationDetails  = ItemVariationDetail::find($vrTableId);
        return json_decode($variationDetails);
    }
}
if (!function_exists('_getInvVrDetails')) {
    function _getInvVrDetails($vrTableId)
    {
        $variationDetails  = InvItemVariationDetail::find($vrTableId);
        return json_decode($variationDetails);
    }
}
if (!function_exists('_aboutText')) {
    function _aboutText()
    {
        return DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'about_us')->first()->value;
    }
}
if (!function_exists('_footerInfo')) {
    function _footerInfo($key)
    {

        return BusinessSetting::withoutGlobalScope('translate')->where('key', $key)->first()->value;
    }
}
function _getServiceProviders($item_id)
{
    $item = DB::table('items')->where('id', $item_id)->first();
    $store_ids = $item->store_ids;
    $data = Store::whereIn('id', explode(',', $store_ids))
        ->active()
        ->select('logo', 'slug') // Only select the logo field
        ->latest()
        ->get();
    return $data;
}
function getAddressFromLatLong($latitude, $longitude)
{


    return "Address not found";
}
if (!function_exists('_userAddrForInvoice')) {
    function _userAddrForInvoice($uid)
    {
        $user = User::find($uid);
        $latitude = $user->latitude;
        $longitude =  $user->longitude;

        $url = "https://nominatim.openstreetmap.org/reverse?lat={$latitude}&lon={$longitude}&format=json";

        // Set up HTTP context with User-Agent header
        $options = [
            'http' => [
                'header' => "User-Agent: MyApp/1.0 (your-email@example.com)"
            ]
        ];
        $context = stream_context_create($options);

        // Get the response with the custom context
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);

        if (isset($data['address'])) {
            return $data['display_name'];
        }

        return "Address not found";
    }
}
if (!function_exists('_getRemainingStock')) {
    function _getRemainingStock($item_id, $branch_id)
    {
        $brInvItem  = BranchInventoryItem::where(['inventory_item_id' => $item_id, 'branch_id' => $branch_id])->first();
        return $brInvItem ? $brInvItem->qty_left : 0;
    }
}
if (!function_exists('_social_media')) {
    function _social_media($key)
    {
        return SocialMedia::withoutGlobalScope('translate')->where('name', $key)->first()->link;
    }
}

if (!function_exists('_sendSMSToAdmin')) {
    function _sendSMSToAdmin($msg, $title = null, $url = null)
    {
        _inAppNotification($title, $msg, null, 0, $url, 'admin');
    }
}
if (!function_exists('_getInventoryItems')) {
    function _getInventoryItems()
    {
        $inventory_items = InventoryItem::where('store_id', Helpers::get_store_id())->get();

        return $inventory_items;
    }
}

if (!function_exists('_sendOrderSMSToAdmins')) {
    function _sendOrderSMSToAdmins($order, $user, $store)
    {
        $customer_name = $user ? $user->f_name . ' ' . $user->l_name : 'Customer';
        $store_name = $store ? $store->name : 'Store';
        $order_id = $order->id;
        $phoneArr = Admin::all()->pluck('phone')->toArray();

        $apikey = "PH73e7LuzUGqwSWbO8ta5A";
        $apisender = "MCHITI";
        $msg = "New Order Alert! You’ve received a new order from " . $customer_name . " to " . $store_name . " Order Id: " . $order_id . " ,Please check the MY CHITTI admin panel for details.";

        $num =  implode(',', $phoneArr);
        $ms = rawurlencode($msg); //This for encode your message content
        $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=' . $apikey . '&senderid=' . $apisender .
            '&channel=2&DCS=0&flashsms=0&number=' . $num . '&text=' . $ms . '&route=1';
        // return $url; 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        $data = json_decode(curl_exec($ch));

        $url = route('admin.order.list', ['all']);
        _inAppNotification("Recieved New Order", 'New Order Received! Please check for details.', null, 0, $url, 'admin');

        return $data;/* result of API call*/
    }
}
if (!function_exists('_isCommonDashboard')) {
    function _isCommonDashboard()
    {
        $host = request()->getHost();
        if ($host == 'staging.mychitti.net') {
            return request()->is([
                'admin/common-dashboard*',
                'admin/modules-billing*',
                'admin/services-billing*',
                'admin/account*',
                'admin/blog*',
                // 'admin/banner*',
                'admin/promotional-banner*',
                'admin/business-settings/pages*'
            ]);
        } else {
            return request()->is([
                'common-dashboard*',
                'modules-billing*',
                'services-billing*',
                'account*',
                'blog*',
                'banner*',
                'promotional-banner*',
                'business-settings/pages*'
            ]);
        }
    }
}
if (!function_exists('_sendSMS')) {
    function _sendSMS($phone, $msg)
    {
        // $phone = substr($phone, 3);
        // 2407145545136643741
        $apikey = "PH73e7LuzUGqwSWbO8ta5A";
        $apisender = "MCHITI";
        $num =  $phone;
        $ms = rawurlencode($msg); //This for encode your message content
        $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=' . $apikey . '&senderid=' . $apisender .
            '&channel=2&DCS=0&flashsms=0&number=' . $num . '&text=' . $ms . '&route=1';
        // return $url; 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        $data = json_decode(curl_exec($ch));

        return $data;/* result of API call*/
    }
}

if (!function_exists('_send_confirmation_sms')) {
    function _send_confirmation_sms($sms_type, $phone, $otp)
    {
        // $phone = substr($phone, 3);
        // 2407145545136643741
        $apikey = "PH73e7LuzUGqwSWbO8ta5A";
        $apisender = "MCHITI";
        if ($sms_type == 'mobile_verification') {
            $msg =  "Dear User , Your OTP for Mobile verification is " . $otp . " - Regards MY CHITTI APP.";
        } else if ($sms_type == 'job_msg') {
            $msg =  "Dear User , Your OTP for Mobile verification is " . $otp . " - Regards MY CHITTI APP.";
        } else {
            $msg =  "Dear User , Your OTP for Mobile verification is " . $otp . " - Regards MY CHITTI APP.";
        }
        // $num =  $phone;
        $num = substr(preg_replace('/\D/', '', $phone), -10);

        $ms = rawurlencode($msg); //This for encode your message content
        $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=' . $apikey . '&senderid=' . $apisender .
            '&channel=2&DCS=0&flashsms=0&number=' . $num . '&text=' . $ms . '&route=1';
        //echo $url;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        $data = json_decode(curl_exec($ch));

        return $data;/* result of API call*/
    }
}
if (!function_exists('_storeEmployees')) {
    function _storeEmployees()
    {
        return VendorEmployee::where('store_id', Helpers::get_store_id())->whereNot('terminate', 1)->get();
    }
}
if (!function_exists('_send_otp')) {
    function _send_otp($phone) {}
}
if (!function_exists('_verify_otp')) {
    function _verify_otp($phone, $otp)
    {
        $verify = DB::table('phone_otp')
            ->where('phone', $phone)
            ->where('otp', $otp)
            ->exists();

        if ($verify) {
            DB::table('phone_otp')
                ->where('phone', $phone)
                ->where('otp', $otp)
                ->delete();
        }

        return $verify;
    }
}


if (!function_exists('_actionLog')) {
    function _actionLog($data)
    {
      
        try {
            ActionLog::create([
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'action' => $data['action'],
                'model_type' => $data['model_type'] ?? null, 
                'model_id' =>  $data['model_id'] ?? 0,
                'description' =>  $data['description'],
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            // throw $th;
        }
    }
}
if (!function_exists('_send_notif_to_user')) {
    function _send_notif_to_user($action, $fcm_token) {}
}
if (!function_exists('_stock_alert_sms')) {
    function _stock_alert_sms($phone, $product)
    {
        $apikey = "PH73e7LuzUGqwSWbO8ta5A";
        $apisender = "MCHITI";
        $msg =  "Dear Vendor, Stock for " . $product . " is low. Please update accordingly. Regards, MY CHITTI APP";
        // $msg =  "Dear User , Your OTP for Mobile verification is 3455 - Regards MY CHITTI APP.";


        $num =  $phone;
        $ms = rawurlencode($msg); //This for encode your message content
        $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey=' . $apikey . '&senderid=' . $apisender .
            '&channel=2&DCS=0&flashsms=0&number=' . $num . '&text=' . $ms . '&route=1';
        //echo $url;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        $data = json_decode(curl_exec($ch));

        return $data;/* result of API call*/
    }
}



if (!function_exists('_employeeAttendance')) {
    function _employeeAttendance($empId)
    {
        return Attendance::where('employee_id', $empId)->where('date', date('Y-m-d'))->where('label', 'P')->first();
    }
}


if (!function_exists('_noResultsInThisLocation')) {

    function _noResultsInThisLocation()
    {
        $html = " <img style='max-width: 470px;margin: 0 auto;'
                            src='" . asset('public/assets/front/img/handshake.jpeg') . "' alt=''>
                        <p> Sorry for your Inconvenience <br> 
                             Don't See Your City? We're Expanding Soon! Stay Tuned.<br>
                             Now Available Locations- Tirupathi, Chittoor, Madanapalle.</p>";
        return $html;
    }
}
if (!function_exists('_inAppNotification')) {
    function _inAppNotification($title, $msg, $acceptnce_id = '', $to = null, $url = null, $user_typ = null)
    {
        $det = new InAppNotification;
        $det->title = $title;
        $det->message = $msg;
        $det->url = $url;
        $det->user_type = $user_typ;
        $det->reciever = $to;

        if ($user_typ == 'vendor') {
            $store = Store::with('vendor')->find($to);
            $fcm = $store?->vendor?->cm_firebase_token;
        } elseif ($user_typ == 'vendor_employee') {
            $employee = VendorEmployee::find($to);
            $fcm = $employee?->cm_firebase_token;
        }
        if (!empty($fcm)) {
            $data = [
                'title'       => $title,
                'description' => $msg,
                'order_id'    => '',
                'image'       => '',
                'type'        => 'block',
            ];

            Helpers::send_push_notif_to_device(
                $fcm,
                $data
            );
        }

        if ($det->save()) {
            return 'sent';
        } else {
            return false;
        };

        // calll here the job ProcessWhatsappNotification::dispatch($det->id);
    }
}
if (!function_exists('_sendMailToStaff')) {
    function _sendMailToStaff($title, $msg,  $to, $url = null)
    {
        // $emp = VendorEmployee::find($to);
        // $rec_name = $emp->f_name . ' ' . $emp->l_name;
        // $store_name = Store::find($emp->store_id)->name;
        // try {

        //     Mail::to($emp->email)->send(new EmployeeNotification($rec_name, $title, $msg,  $url, $store_name));
        // } catch (\Throwable $th) {
        //     //throw $th;
        // }
        return true;
    }
}

if (!function_exists('_topsearched')) {
    function _topsearched()
    {
        $html = '';

        $popular_searches = UserRecentSearch::where('trash', 0)
            ->select('text', 'url', DB::raw('COUNT(*) as total'))
            ->groupBy('text', 'url')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        foreach ($popular_searches as $search) {
            $html .= '<div class="chip" data-url="' . $search->url . '"><i class="fas fa-fire"></i> ' . $search->text . '</div>';
        }

        return $html;
    }
}
if (!function_exists('_invoiceUserAddress')) {
    function _invoiceUserAddress($title, $msg,  $to, $url = null)
    {
        $emp = VendorEmployee::find($to);
        $rec_name = $emp->f_name . ' ' . $emp->l_name;
        $store_name = Store::find($emp->store_id)->name;
        Mail::to($emp->email)->send(new EmployeeNotification($rec_name, $title, $msg,  $url, $store_name));
        return true;
    }
}
if (!function_exists('_sendMailToVendor')) {
    function _sendMailToVendor($title, $msg,  $to, $url = null)
    {
        try {
            $store = Store::find($to);
            // print_r(Mail::to($store->email)->send(new VendorNotification($store->name, $title, $msg,  $url)));
            //code...

        } catch (\Throwable $th) {
            throw $th;
        }
        return true;
    }
}
if (!function_exists('_serviceInvoiceStatus')) {
    function _serviceInvoiceStatus($id)
    {
        // print_r(ServiceInvoice::where('service_id',$id)->first());
        if (ServiceInvoice::where('service_id', $id)->exists()) {
            if (ServiceInvoice::where('service_id', $id)->first()->correction == 1) {
                return ServiceInvoice::where('service_id', $id)->first()->pdf;
            } else {
                return 'editable';
            }
        } else {
            return 'new';
        }
    }
}
if (!function_exists('_getServiceInvoice')) {
    function _getServiceInvoice($id)
    {
        if (ServiceInvoice::where('service_id', $id)->exists()) {
            return ServiceInvoice::where('service_id', $id)->first()->pdf;
        } else {
            return null;
        }
    }
}
if (!function_exists('_getServiceByCatId')) {
    function _getServiceByCatId($cat_id)
    {
        $data =  DB::table('items')
            ->join('stores', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(stores.id, items.store_ids)'), '>', DB::raw('0'));
            })
            ->where('items.is_approved', 1)
            ->whereIn('stores.zone_id',  json_decode(session('zone_ids'), true))
            ->where(['stores.active' => 1, 'items.status' => 1])
            ->where('items.category_id', $cat_id)
            ->select('items.*', 'stores.zone_id', 'stores.active as store_open', 'stores.delivery_time')
            ->groupBy('items.id')
            ->get()->toArray();

        return $data;
    }
}
if (!function_exists('_sendQuotationMail')) {
    function _sendQuotationMail($title, $template_id, $data, $email)
    {
        // Render the Blade view as a string
        $body = view('email-templates.quotation_' . $template_id, compact('data'))->render();

        // Mail::send([], [], function ($message) use ($email, $title, $body) {
        //     $message->to($email)
        //         ->subject($title)
        //         ->html($body); // Use 'html()' to set the HTML content of the email
        // });
    }
}
if (!function_exists('_getAdminName')) {
    function _getAdminName($id)
    {
        if ($id) {
            $admin = Admin::find($id);
            return $admin->f_name . ' ' .  $admin->l_name;
        } else {
            return $id;
        }
    }
}


if (!function_exists('_unreadNotificationCount')) {
    function _unreadNotificationCount()
    {
        if (auth('vendor')->check()) {
            $type = 'vendor';
        } else {
            $type = 'vendor_employee';
        }
        return InAppNotification::where('user_type', $type)->where('is_read', 0)->where('reciever', Helpers::get_loggedin_user()->id)->count();
    }
}
if (!function_exists('_getAccessToken')) {
    function _getAccessToken()
    {
        // Load the service account key JSON file
        $serviceAccountKeyPath = 'service-account-key.json';
        $serviceAccount = json_decode(file_get_contents($serviceAccountKeyPath), true);

        // Create a JWT token
        $now = time();
        $token = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $jwt = JWT::encode($token, $serviceAccount['private_key'], 'RS256');

        // Exchange JWT for access token
        $client = new Client();
        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        return $result['access_token'];
    }
}
if (!function_exists('_convertNumberToWords')) {
    function _convertNumberToWords($number)
    {
        $words = [
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        ];

        if ($number == 0) {
            return 'Zero rupees only';
        }

        $number = number_format($number, 2, '.', '');
        $integerPart = (int)$number;
        $decimalPart = (int)(($number - $integerPart) * 100);

        $result = '';

        // Handle Crores (if any)
        $crores = (int)($integerPart / 10000000);
        if ($crores > 0) {
            $result .= _convertLessThanThousand($crores, $words) . ' crore ';
            $integerPart = $integerPart % 10000000;
        }

        // Handle Lakhs (if any)
        $lakhs = (int)($integerPart / 100000);
        if ($lakhs > 0) {
            $result .= _convertLessThanThousand($lakhs, $words) . ' lakh ';
            $integerPart = $integerPart % 100000;
        }

        // Handle Thousands (if any)
        $thousands = (int)($integerPart / 1000);
        if ($thousands > 0) {
            $result .= _convertLessThanThousand($thousands, $words) . ' thousand ';
            $integerPart = $integerPart % 1000;
        }

        // Handle Hundreds (if any)
        $hundreds = (int)($integerPart / 100);
        if ($hundreds > 0) {
            $result .= _convertLessThanThousand($hundreds, $words) . ' hundred ';
            $integerPart = $integerPart % 100;
        }

        // Handle remaining two digits
        if ($integerPart > 0) {
            if ($result != '') {
                $result .= 'and ';
            }
            $result .= _convertLessThanThousand($integerPart, $words);
        }

        $result .= ' rupees';

        // Handle decimal part (paise)
        if ($decimalPart > 0) {
            $result .= ' and ' . _convertLessThanThousand($decimalPart, $words) . ' paise';
        }

        return ucfirst(trim($result)) . ' only';
    }

    // Helper function to convert numbers less than 1000
    function _convertLessThanThousand($number, $words)
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return $words[$number];
        }

        if ($number < 100) {
            return $words[($number - $number % 10)] . ($number % 10 ? ' ' . $words[$number % 10] : '');
        }

        return $words[(int)($number / 100)] . ' hundred' . ($number % 100 ? ' and ' . _convertLessThanThousand($number % 100, $words) : '');
    }
}
