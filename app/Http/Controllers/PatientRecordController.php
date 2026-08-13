<?php

namespace App\Http\Controllers;

use App\Models\LabOrder;
use App\Models\OpdVisit;
use App\Models\Prescription;
use App\Models\RadiologyStudy;
use App\Services\HmisWhatsAppShare;
use Illuminate\Support\Facades\DB;

/**
 * The page a patient opens from the WhatsApp link — their own consultation summary, prescription,
 * medicine instructions or lab report.
 *
 * Public by necessity (a patient has no login here), so the token IS the credential: 48 hex
 * characters, single-record, single-send, expiring. The record is re-read from the token's own
 * store/patient/record ids rather than anything in the request, so a valid token can never be
 * pointed at a different patient's record.
 */
class PatientRecordController extends Controller
{
    public function show($token)
    {
        HmisWhatsAppShare::ensureTable();

        $share = DB::table('wa_patient_shares')->where('token', (string) $token)->first();

        if (!$share || $share->revoked_at) {
            return response()->view('patient-record.unavailable', [
                'reason' => 'This link is no longer valid.',
            ], 404);
        }
        if ($share->expires_at && now()->greaterThan($share->expires_at)) {
            return response()->view('patient-record.unavailable', [
                'reason' => 'This link has expired. Please ask the hospital to send it again.',
            ], 410);
        }

        $record = $this->record($share);
        if (!$record) {
            return response()->view('patient-record.unavailable', [
                'reason' => 'This record is no longer available.',
            ], 404);
        }

        DB::table('wa_patient_shares')->where('id', $share->id)->update([
            'views'           => DB::raw('views + 1'),
            'first_viewed_at' => $share->first_viewed_at ?: now(),
            'last_viewed_at'  => now(),
            'updated_at'      => now(),
        ]);

        $store = DB::table('stores')->where('id', $share->store_id)
            ->first(['name', 'address', 'phone', 'logo']);

        return response()
            ->view('patient-record.show', [
                'kind'    => $share->kind,
                'record'  => $record,
                'store'   => $store,
                'expires' => $share->expires_at,
            ])
            // Medical detail behind a shared link has no business in a proxy cache or a search index.
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Load the one record this token points at, scoped to the store that shared it. */
    protected function record($share)
    {
        $storeId = (int) $share->store_id;
        $id      = (int) $share->record_id;

        switch ($share->kind) {
            // Same record at two points in its life: the slip sent at the counter and the summary
            // sent after the doctor has written it up. The page shows whichever fields are filled.
            case 'visit_registered':
            case 'treatment':
                return OpdVisit::with('patient', 'doctorProfile.employee')
                    ->where('store_id', $storeId)->find($id);

            case 'prescription':
            case 'medicines':
                return Prescription::with('patient', 'doctorProfile.employee', 'items')
                    ->where('store_id', $storeId)->find($id);

            case 'lab':
                return LabOrder::with('patient', 'doctorProfile.employee', 'items', 'results')
                    ->where('store_id', $storeId)->find($id);

            case 'radiology':
                return RadiologyStudy::with('patient', 'doctorProfile.employee')
                    ->where('store_id', $storeId)->find($id);
        }

        return null;
    }
}
