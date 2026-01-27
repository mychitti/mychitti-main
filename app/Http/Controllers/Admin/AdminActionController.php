<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\AdminAction;
use App\Models\SecureFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class AdminActionController extends Controller
{
    public function proceed_action(Request $request)
    {
        $otp = implode('', $request->otp);
        $adminId =auth('admin')->id();
        $action = AdminAction::where('requested_by', $adminId)
            ->where('otp', $otp)
            ->where('action_type', $request->action_type)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
        if (!$action) {
            return response()->json([
                'status' => false,
                'msg' => 'Invalid or expired OTP'
            ]);
        }
             // verified 
        $payload = json_decode($action->action_payload, true);

        switch ($action->action_type) {

            case 'retail_recharge_wallet':
                $action->otp = NULL;
                $action->status = 'verified';
                $action->save();
                return  VendorWalletController::recharge_proceed($payload);
                break;

            default:
                throw new \Exception('Unknown action type');
        }
    }
}
