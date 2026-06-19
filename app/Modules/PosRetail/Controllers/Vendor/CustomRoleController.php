<?php

namespace App\Modules\PosRetail\Controllers\Vendor;

use App\Http\Controllers\Vendor\CustomRoleController as BaseCustomRoleController;
use Illuminate\Http\Request;

class CustomRoleController extends BaseCustomRoleController
{
    // Ensure the Retail POS billing permission exists before the role grid renders,
    // so the "Retail POS" section + its actions are grantable without first opening New Sale.
    public function create(Request $request)
    {
        RetailPosController::seedPermissions();
        return parent::create($request);
    }

    public function edit($id)
    {
        RetailPosController::seedPermissions();
        return parent::edit($id);
    }
}
