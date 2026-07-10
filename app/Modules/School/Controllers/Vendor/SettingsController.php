<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\SchoolNotificationPreference;
use App\Models\StoreConfig;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    private function ensureSchema(): void
    {
        $cfg = (new StoreConfig)->getTable();
        if (!Schema::hasColumn($cfg, 'admission_no_prefix')) {
            DB::statement("ALTER TABLE `{$cfg}`
                ADD COLUMN `admission_no_prefix` VARCHAR(20) NULL,
                ADD COLUMN `admission_no_padding` INT NULL,
                ADD COLUMN `admission_no_serial` INT NULL");
        }
        if (!Schema::hasColumn($cfg, 'school_serial_scope')) {
            DB::statement("ALTER TABLE `{$cfg}` ADD COLUMN `school_serial_scope` VARCHAR(10) NULL");
        }
        if (!Schema::hasColumn($cfg, 'school_template_id')) {
            DB::statement("ALTER TABLE `{$cfg}` ADD COLUMN `school_template_id` VARCHAR(10) NULL");
        }
    }

    public function index()
    {
        $this->ensureSchema();
        $store  = \App\Models\Store::find(Helpers::get_store_id());
        $config = StoreConfig::where('store_id', Helpers::get_store_id())->first();

        $schoolTemplate = $config?->template_id;
        if (!$schoolTemplate) {
            $schoolTemplate = ($config?->school_template_id == '2') ? 19 : 18;
        }

        return view('school::vendor.settings.index', [
            'prefix'         => $config?->admission_no_prefix ?? 'ADM',
            'padding'        => (int) ($config?->admission_no_padding ?? 4),
            'serial'         => (int) ($config?->admission_no_serial ?? 1),
            'serialScope'    => $config?->school_serial_scope ?: 'store',
            'schoolTemplate' => (string) $schoolTemplate, 
            'webpageUrl'     => $this->storeWebpageUrl($store),
        ]);
    }

    public function save(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'prefix'           => 'required|string|max:20',
            'padding'          => 'required|integer|min:1|max:10',
            'serial'           => 'required|integer|min:1',
            'serial_scope'     => 'nullable|in:store,branch',
            'school_template'  => 'nullable|in:1,2,18,19',
        ]);

        $tplId = $request->school_template ?: 18;
        $legacyTplId = '1';
        if ($tplId == 2 || $tplId == 19) {
            $tplId = 19;
            $legacyTplId = '2';
        } else {
            $tplId = 18;
            $legacyTplId = '1';
        }

        StoreConfig::updateOrInsert(
            ['store_id' => Helpers::get_store_id()],
            [
                'admission_no_prefix'  => strtoupper($request->prefix),
                'admission_no_padding' => (int) $request->padding,
                'admission_no_serial'  => (int) $request->serial,
                'school_serial_scope'  => $request->serial_scope ?: 'store',
                'template_id'          => $tplId,
                'school_template_id'   => $legacyTplId,
            ]
        );
        Toastr::success('School settings saved.');
        return back();
    }

    /** Save only the public webpage template choice (used from the Webpage → Templates tab). */
    public function saveTemplate(Request $request)
    {
        $this->ensureSchema();
        $request->validate(['school_template' => 'required|in:1,2,18,19']);

        $tplId = $request->school_template;
        $legacyTplId = '1';
        if ($tplId == 2 || $tplId == 19) {
            $tplId = 19;
            $legacyTplId = '2';
        } else {
            $tplId = 18;
            $legacyTplId = '1';
        }

        StoreConfig::updateOrInsert(
            ['store_id' => Helpers::get_store_id()],
            [
                'template_id' => $tplId,
                'school_template_id' => $legacyTplId
            ]
        );
        Toastr::success('Website template updated.');
        return back();
    }
 
    public function notificationPreferences()
    {
        $storeId = Helpers::get_store_id();
        $prefs   = SchoolNotificationPreference::getPreferences($storeId);
        $actions = SchoolNotificationPreference::ACTIONS;

        return view('school::vendor.settings.notification_preferences', compact('prefs', 'actions'));
    }

    public function saveNotificationPreferences(Request $request)
    {
        SchoolNotificationPreference::ensureTable();
        $storeId    = Helpers::get_store_id();
        $validKeys  = array_keys(SchoolNotificationPreference::ACTIONS);

        foreach ($validKeys as $key) {
            SchoolNotificationPreference::updateOrCreate(
                ['store_id' => $storeId, 'action_key' => $key],
                [
                    'whatsapp'          => $request->boolean("prefs.{$key}.whatsapp"),
                    'sms'               => $request->boolean("prefs.{$key}.sms"),
                    'push_notification' => $request->boolean("prefs.{$key}.push_notification"),
                    'updated_at'        => now(),
                ]
            );
        }

        Toastr::success('Notification preferences saved.');
        return back();
    }

    /** Public webpage URL of the store ({city}/store/{slug}) for preview links. */
    private function storeWebpageUrl($store): ?string
    {
        if (!$store || empty($store->slug)) return null;
        $city = 'city';
        if ($store->zone_id) {
            $zone = DB::table('zones')->where('id', $store->zone_id)->value('name');
            if ($zone) $city = strtolower(str_replace(' ', '-', trim(explode(',', $zone)[0])));
        }
        return url($city . '/store/' . $store->slug);
    }
}
