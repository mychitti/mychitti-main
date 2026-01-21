<?php

namespace App\Http\Controllers\Admin;

use App\Traits\Processor;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use App\Models\DataSetting;


class McvendorSettingsController extends Controller
{
    use Processor;

    public function mcvendor_index(Request $request, $tab = 'business')
    {
        if (!Helpers::module_permission_check('settings')) {
            Toastr::error(translate('messages.access_denied'));
            return back();
        }
        $type = $request->type;

        if ($tab == 'business') {
            return view('admin-views.mcvendor-settings.mcvendor-index');
        } 
        else if ($tab == 'privacy-policy') {
            $privacy_policy_for_mc_vendor = DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'privacy_policy_for_mc_vendor')->first();
            return view('admin-views.mcvendor-settings.privacy-policy', compact('privacy_policy_for_mc_vendor'));
        }
        else if ($tab == 'terms-and-conditions') {
        $vendorhub_terms_and_conditions = DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'vendorhub_terms_and_conditions')->first();
            return view('admin-views.mcvendor-settings.terms-and-conditions', compact('vendorhub_terms_and_conditions'));
        }
    }
    public function mcvendor_setup(Request $request)
    {

        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $curr_logo = BusinessSetting::where(['key' => 'mcvendor_logo'])->first();
        if ($request->has('mcvendor_logo')) {
            if ($curr_logo) {
                $image_name = Helpers::update('business/', $curr_logo->value, 'png', $request->file('mcvendor_logo'));
            } else {
                $image_name = Helpers::upload('business/', 'png', $request->file('mcvendor_logo'));
            }
        } else {
            $image_name = $curr_logo['value'];
        }
        DB::table('business_settings')->updateOrInsert(['key' => 'mcvendor_logo'], [
            'value' => $image_name
        ]);
        $curr_logo = BusinessSetting::where(['key' => 'mcvendor_footer_logo'])->first();
        if ($request->has('mcvendor_footer_logo')) {
            if ($curr_logo) {
                $image_name = Helpers::update('business/', $curr_logo->value, 'png', $request->file('mcvendor_footer_logo'));
            } else {
                $image_name = Helpers::upload('business/', 'png', $request->file('mcvendor_footer_logo'));
            }
        } else {
            $image_name = $curr_logo['value'];
        }
        DB::table('business_settings')->updateOrInsert(['key' => 'mcvendor_footer_logo'], [
            'value' => $image_name
        ]);


        Toastr::success("Updated Successfully");
        return back();
    }
}
