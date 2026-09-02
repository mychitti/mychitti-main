<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Who in a store may do what with WhatsApp.
 *
 * The module shipped with one coarse feature row — `whatsapp` / `access` — carrying a NULL
 * master_module, which means it never appeared in the role grid for anyone to tick. The routes
 * carried no guard either, so in practice every employee could disconnect the number, run a bulk
 * send or buy tokens. This splits that single row into one feature per screen and one action per
 * thing worth withholding, all under the `whatsapp` master module so the role editor groups them.
 *
 * The rows are seeded here rather than in a migration, the same way the OPD and lab permissions
 * are, so a store gets them on the next page load without a deploy step.
 */
class WhatsAppPermissions
{
    /** Groups the features under one heading in the role editor. */
    const MODULE = 'whatsapp';

    /**
     * feature name => [display name, [action => label]].
     *
     * Action names are shared across the whole platform — `permission_action_label()` renders one
     * label for each, everywhere — so these avoid the ones another module has already given a
     * domain meaning to. `send` is Radiology's "Send Report", hence `reply`, `broadcast` and
     * `send_note` rather than three flavours of send.
     */
    const FEATURES = [
        // Deliberately not a module-wide "access" switch. The role editor's WhatsApp module
        // checkbox already is one — untick it and update() syncs every permission below away —
        // so a second master gate inside the grid would only be a second thing to forget. This
        // row is the Dashboard screen and nothing else.
        'whatsapp' => ['WhatsApp Dashboard', [
            'dashboard' => 'Open the WhatsApp dashboard',
        ]],
        'whatsapp_inbox' => ['WhatsApp Chats', [
            'list'    => 'Read chats',
            'reply'   => 'Reply to a customer',
            'forward' => 'Forward a chat to staff',
        ]],
        'whatsapp_templates' => ['WhatsApp Templates', [
            'list'   => 'View templates',
            'add'    => 'Create a template',
            'edit'   => 'Edit, trash, restore, send a test',
            'delete' => 'Delete at Meta',
        ]],
        'whatsapp_bulk' => ['WhatsApp Bulk Message', [
            'list'      => 'View the composer and history',
            'broadcast' => 'Send and stop a broadcast',
            'import'    => 'Import the customer book',
            'export'    => 'Export a run',
        ]],
        'whatsapp_campaigns' => ['WhatsApp Campaigns', [
            'list'          => 'View campaigns',
            'add'           => 'Create a campaign',
            'status_change' => 'Start, pause, cancel, run now',
            'delete'        => 'Delete a campaign',
            'export'        => 'Export recipients',
        ]],
        'whatsapp_automation' => ['WhatsApp Automation', [
            'list' => 'View automation, bot and knowledge',
            'edit' => 'Change automation, bot and knowledge',
        ]],
        'whatsapp_complaints' => ['WhatsApp Feedback & Complaints', [
            'list'          => 'View complaints',
            'status_change' => 'Resolve a complaint',
        ]],
        'whatsapp_logs' => ['WhatsApp Message Log', [
            'list' => 'View the message log',
        ]],
        'whatsapp_connection' => ['WhatsApp Connection & Numbers', [
            'list'   => 'View connection and numbers',
            'edit'   => 'Connect and manage numbers',
            'delete' => 'Disconnect the number',
        ]],
        'whatsapp_billing' => ['WhatsApp Billing & Plan', [
            'list' => 'View the plan and usage',
            'pay'  => 'Subscribe, cancel, buy slots and tokens',
        ]],
        'whatsapp_note' => ['WhatsApp Direct Note', [
            'send_note' => 'Send a one-off note to a customer',
        ]],
    ];

    /** Every feature name here, for the menu gate. */
    public static function featureNames(): array
    {
        return array_keys(self::FEATURES);
    }

    /**
     * Create any missing feature and action rows.
     *
     * Cheap on the common path: one sentinel lookup and nothing else once the module is seeded.
     * Called from the sidebar partial and the role editor, so it lands on the first panel page a
     * store opens.
     */
    public static function ensure(): void
    {
        if (!Schema::hasTable('features') || !Schema::hasTable('feature_permissions')) {
            return;
        }

        try {
            // The last feature in the map, so a run interrupted halfway is retried rather than
            // being mistaken for a finished one.
            $sentinel = DB::table('features')->where('name', 'whatsapp_note')->exists();
            if ($sentinel) {
                return;
            }

            self::renameLegacyAccessAction();

            $featureIds     = [];
            $createdFeature = false;
            $createdActions = [];

            foreach (self::FEATURES as $name => [$label, $actions]) {
                $featureId = DB::table('features')->where('name', $name)->value('id');

                if (!$featureId) {
                    $featureId = DB::table('features')->insertGetId([
                        'name'          => $name,
                        'display_name'  => $label,
                        'master_module' => self::MODULE,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    $createdFeature = true;
                } else {
                    // The legacy `whatsapp` row carried a NULL master_module, which kept it out of
                    // the role grid entirely. Adopt it into the module rather than leaving a
                    // second, invisible copy of the same feature behind.
                    DB::table('features')->where('id', $featureId)->update([
                        'display_name'  => $label,
                        'master_module' => self::MODULE,
                        'updated_at'    => now(),
                    ]);
                }

                $featureIds[] = $featureId;

                foreach ($actions as $action => $actionLabel) {
                    $existing = DB::table('feature_permissions')
                        ->where('feature_id', $featureId)
                        ->where('action', $action)
                        ->value('id');

                    if ($existing) {
                        // free = 1 is what lets hasPermission() reach the role check at all: with
                        // it clear, a master_module that is not a paid submodule fails the
                        // subscription gate and denies the owner their own WhatsApp.
                        DB::table('feature_permissions')->where('id', $existing)
                            ->update(['display_name' => $actionLabel, 'free' => 1, 'updated_at' => now()]);
                        continue;
                    }

                    $createdActions[] = DB::table('feature_permissions')->insertGetId([
                        'feature_id'   => $featureId,
                        'action'       => $action,
                        'display_name' => $actionLabel,
                        'free'         => 1,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            if (!$createdActions) {
                return;
            }

            // A brand new feature means this is the first run: nobody has ever been able to grant
            // these, because the grid never showed them. Handing every existing role what it
            // already has in practice keeps staff working on deploy day and leaves the owner to
            // take away what they do not want — the opposite order locks a receptionist out of
            // the inbox with no warning.
            //
            // Later runs grant only genuinely new actions, so an owner's untick is never undone.
            self::grantToExistingRoles(
                $createdFeature
                    ? DB::table('feature_permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all()
                    : $createdActions
            );
        } catch (\Throwable $e) {
            Log::warning('WhatsApp permission seed failed: ' . $e->getMessage());
        }
    }

    /**
     * Carry the original `whatsapp` / `access` row over to `dashboard`.
     *
     * Renamed in place rather than replaced: it is the row the module shipped with, and any grant
     * pointing at it survives the change. Creating a second row and abandoning the first would
     * leave an `access` action in the grid that guards nothing.
     */
    protected static function renameLegacyAccessAction(): void
    {
        $featureId = DB::table('features')->where('name', 'whatsapp')->value('id');
        if (!$featureId) {
            return;
        }

        $legacy = DB::table('feature_permissions')
            ->where('feature_id', $featureId)->where('action', 'access')->value('id');

        $current = DB::table('feature_permissions')
            ->where('feature_id', $featureId)->where('action', 'dashboard')->exists();

        if (!$legacy || $current) {
            return;
        }

        DB::table('feature_permissions')->where('id', $legacy)->update([
            'action'     => 'dashboard',
            'updated_at' => now(),
        ]);
    }

    /**
     * Give every existing role the listed permissions, and put WhatsApp on its module list.
     *
     * The module list matters as much as the grant: the role editor syncs permissions per selected
     * module, so a role saved with WhatsApp unticked would have these wiped again the next time
     * anybody edited it.
     */
    protected static function grantToExistingRoles(array $permissionIds): void
    {
        if (empty($permissionIds) || !Schema::hasTable('role_feature_permissions')) {
            return;
        }

        $roles = DB::table('employee_roles')->select('id', 'modules')->get();
        if ($roles->isEmpty()) {
            return;
        }

        $held = DB::table('role_feature_permissions')
            ->whereIn('feature_permission_id', $permissionIds)
            ->get(['role_id', 'feature_permission_id'])
            ->groupBy('role_id');

        $rows = [];

        foreach ($roles as $role) {
            $already = isset($held[$role->id])
                ? $held[$role->id]->pluck('feature_permission_id')->all()
                : [];

            foreach (array_diff($permissionIds, $already) as $permissionId) {
                $rows[] = [
                    'role_id'               => $role->id,
                    'feature_permission_id' => $permissionId,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];
            }

            $modules = json_decode((string) $role->modules, true);
            $modules = is_array($modules) ? $modules : [];

            if (!in_array(self::MODULE, $modules, true)) {
                $modules[] = self::MODULE;
                DB::table('employee_roles')->where('id', $role->id)
                    ->update(['modules' => json_encode(array_values($modules))]);
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('role_feature_permissions')->insert($chunk);
        }
    }
}
