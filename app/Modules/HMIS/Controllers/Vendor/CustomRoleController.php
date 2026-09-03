<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\Http\Controllers\Vendor\CustomRoleController as BaseCustomRoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomRoleController extends BaseCustomRoleController
{ 
    public function create(Request $request)
    {
        $this->ensureHMISPermissions();
        return parent::create($request);
    }

    public function store(Request $request)
    {
        $this->ensureHMISPermissions();
        return parent::store($request);
    }

    public function edit($id)
    {
        $this->ensureHMISPermissions();
        return parent::edit($id);
    }

    public function update(Request $request, $id)
    {
        $this->ensureHMISPermissions();
        return parent::update($request, $id);
    }

    private function ensureHMISPermissions(): void
    {
        try {
            app(PreOpController::class)->ensurePermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed PreOp permissions: ' . $e->getMessage());
        }

        try {
            app(RadiologyController::class)->ensurePermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed Radiology permissions: ' . $e->getMessage());
        }

        try {
            app(NursingStationController::class)->ensurePermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed NursingStation permissions: ' . $e->getMessage());
        }

        try {
            app(LabController::class)->ensureLabPermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed Lab permissions: ' . $e->getMessage());
        }

        try {
            app(BasicPharmacyController::class)->ensurePharmacyPermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed BasicPharmacy permissions: ' . $e->getMessage());
        }

        try {
            HospitalBillController::ensurePermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed HospitalBill permissions: ' . $e->getMessage());
        }

        try {
            AppointmentController::ensurePermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed Appointment permissions: ' . $e->getMessage());
        }

        try {
            HospitalActivityLogController::ensurePermission();
        } catch (\Throwable $e) {
            Log::error('Failed to seed HospitalActivityLog permissions: ' . $e->getMessage());
        }

        try {
            \App\Http\Controllers\Front\FrontController::ensureHospitalFeatures();
        } catch (\Throwable $e) {
            Log::error('Failed to seed base HMIS permissions: ' . $e->getMessage());
        }
    }
}
