<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchLeadRounds extends Command
{
    protected $signature = 'leads:dispatch-rounds';
    protected $description = 'Advance leads to the next batch when the current round expires with no acceptance';

    public function handle(): int
    {
        $roundTimeout = (int)(Helpers::get_settings('leads_dispatch_round_timeout') ?: 5);

        $leads = DB::table('service_requests')
            ->where('dispatch_round', '<', 3)
            ->where('dispatch_round_expires_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('accepted')->orWhere('accepted', 0);
            })
            ->where('status', '!=', 'cancelled')
            ->where('expired', 0)
            ->get();

        foreach ($leads as $lead) {
            $nextRound = $lead->dispatch_round + 1;
            $nextStores = $nextRound === 2 ? $lead->round_2_stores : $lead->round_3_stores;

            if (empty($nextStores)) {
                // No stores for next round — still advance to prevent infinite loop
                DB::table('service_requests')->where('id', $lead->id)->update([
                    'dispatch_round'            => $nextRound,
                    'dispatch_round_expires_at' => now()->addMinutes($roundTimeout),
                ]);
                continue;
            }

            $newStoreIds = array_filter(explode(',', $nextStores));

            // Append new stores to sent_to (don't replace — existing batch can still accept)
            $existingSentTo = array_filter(explode(',', $lead->sent_to ?? ''));
            $merged = array_unique(array_merge($existingSentTo, $newStoreIds));

            DB::table('service_requests')->where('id', $lead->id)->update([
                'sent_to'                   => implode(',', $merged),
                'dispatch_round'            => $nextRound,
                'dispatch_round_expires_at' => now()->addMinutes($roundTimeout),
            ]);

            // Notify new batch
            $isAppointment = !empty($lead->preferred_doctor_id);
            $title = $isAppointment ? 'New Appointment Request' : 'New Service Enquiry';
            $itemName = DB::table('items')->where('id', $lead->item_id)->value('name') ?? 'a service';
            $catId    = DB::table('items')->where('id', $lead->item_id)->value('category_id');
            $userName = DB::table('users')->where('id', $lead->user_id)->value('f_name') ?? 'a customer';
            $msg  = "Hello! You have received a new " . ($isAppointment ? 'APPOINTMENT request' : 'ENQUIRY')
                . " from {$userName} for {$itemName}. Please visit the My Chitti Vendor App. Thank you, My Chitti Team.";
            $url  = route('vendor.service.leads_list');

            foreach ($newStoreIds as $storeId) {
                $store = DB::table('stores')->where('id', $storeId)->first();
                if (!$store) continue;
                _sendSMS($store->phone, $msg);
                _inAppNotification($title, $msg, null, $store->id, $url, 'vendor');
                // WhatsApp-subscribed stores auto-accept + get the "accepted" template + confirmation;
                // everyone else just gets the normal new-lead WhatsApp.
                if (!_autoAcceptLeadForStore($store->id, $lead->id)) {
                    \App\Services\WhatsAppService::sendLeadNotification($store->id, $itemName, $userName);
                }
                // Nudge stores that can't afford to accept (low wallet, no lead subscription).
                _remindLeadWalletRecharge($store, $catId);
            }

            Log::info("Lead #{$lead->id}: advanced to round {$nextRound}, notified " . count($newStoreIds) . " stores.");
        }

        return self::SUCCESS;
    }
}
