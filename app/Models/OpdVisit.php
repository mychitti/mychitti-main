<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdVisit extends Model
{ 
    protected $fillable = [
        'store_id', 'patient_id', 'doctor_profile_id', 'appointment_id', 'service_request_id',
        'visit_date', 'visit_time', 'token_number', 'visit_type', 'chief_complaint', 'diagnosis', 'treatment',
        'willing_treatment', 'treatment_plan',
        'bp_systolic', 'bp_diastolic', 'temperature', 'weight',
        'height', 'spo2', 'pulse_rate', 'respiratory_rate', 'notes', 'recorded_by', 'status',
        'consultation_receipt_id', 'consultation_visit_no',
        'cancelled_at', 'cancel_reason', 'cancelled_by',
        // Label → value rows captured for this visit (dental intake). Overrides the patient's
        // standing values when the bill is built — see DentalIntakeController::mergedFor().
        'custom_info',
    ];

    protected $casts = [
        'visit_date'       => 'date',
        'cancelled_at'     => 'datetime',
        'bp_systolic'      => 'integer',
        'bp_diastolic'     => 'integer',
        'temperature'      => 'float',
        'weight'           => 'float',
        'height'           => 'float',
        'spo2'             => 'integer',
        'pulse_rate'       => 'integer',
        'respiratory_rate' => 'integer',
    ];

    const VISIT_TYPES = [
        'new'      => 'New Visit',
        'followup' => 'Follow-Up',
        'emergency'=> 'Emergency',
        'review'   => 'Review',
    ];

    const STATUS_CANCELLED = 'cancelled';

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * A finished consultation — the OP receipt has been generated and handed over.
     *
     * There is no "completed" value in the status column; the register has always derived the
     * badge from the receipt, so the lock derives it the same way rather than inventing a second
     * source of truth that could disagree with the badge the desk is looking at.
     *
     * Once this is true the visit is a document, not a draft: the patient is holding a receipt
     * that was printed from this record, and a later edit would make the reprint disagree with
     * their copy silently. Everything that writes to the visit checks this.
     */
    public function getIsCompletedAttribute(): bool
    {
        return !empty($this->consultation_receipt_id);
    }

    /** Whether the clinical record may still be written to. */
    public function getIsEditableAttribute(): bool
    {
        return !$this->is_cancelled && !$this->is_completed;
    }

    /**
     * Visits that actually happened — everything counted, charted or exported.
     *
     * Rows created before the status column was written carry NULL rather than 'visited', so
     * a plain `!=` comparison would silently drop the whole of a hospital's older register.
     */
    public function scopeNotCancelled($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', self::STATUS_CANCELLED);
        });
    }

    // Diagnosis and treatment are stored as a comma-joined string so they stay readable in
    // exports and receipts; the UI works with them as tag lists.
    /**
     * Complaints are stored in chief_complaint as a comma-separated list, exactly like diagnosis
     * and treatment. Free text written before the field became a chip list still reads back — it
     * simply comes through as one entry.
     */
    public function getComplaintListAttribute(): array
    {
        return self::splitTerms($this->chief_complaint);
    }

    public function getDiagnosisListAttribute(): array
    {
        return self::splitTerms($this->diagnosis);
    }

    public function getTreatmentListAttribute(): array
    {
        return self::splitTerms($this->treatment);
    }

    /**
     * A course of treatment rarely happens in one sitting: a dressing today, a review on Friday,
     * a second sitting next month. Each advised term therefore carries its own row —
     *
     *   term => ['status' => 'pending'|'upcoming'|'in_progress'|'completed',
     *            'date' => 'Y-m-d', 'time' => 'H:i', 'amount' => float, 'discount' => float,
     *            'paid' => bool, 'appointment_id' => int|null]
     *
     * A term with no row is pending and unpriced. `appointment_id` is set once the sitting has
     * been booked as a real follow-up — the treatment then moves with that appointment instead of
     * being a date written only on this visit. Kept as JSON on the visit rather than its own
     * table because it is only ever read and written with the visit it belongs to.
     */
    public function getTreatmentPlanMapAttribute(): array
    {
        $decoded = json_decode((string) $this->treatment_plan, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * appointment id => the advised treatments booked into it.
     *
     * One follow-up usually covers several sittings at once ("come Friday, we'll do the scaling
     * and the filling"), so the Next Visit list reads this to say what each booking is for.
     */
    public function getTreatmentsByAppointmentAttribute(): array
    {
        $map = [];
        foreach ($this->treatment_plan_map as $term => $row) {
            $appointmentId = (int) ($row['appointment_id'] ?? 0);
            if ($appointmentId) {
                $map[$appointmentId][] = $term;
            }
        }

        return $map;
    }

    /**
     * What the planned treatments come to, after their own discounts, and how much of that has
     * been collected. Paid is tracked per treatment rather than per visit: a course spread over
     * three sittings is usually paid for as it goes.
     */
    public function getTreatmentPlanTotalsAttribute(): array
    {
        $gross = $discount = $paid = 0.0;
        foreach ($this->treatment_plan_map as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $less   = (float) ($row['discount'] ?? 0);
            $gross    += $amount;
            $discount += $less;
            if (!empty($row['paid'])) {
                $paid += max($amount - $less, 0);
            }
        }
        $net = max($gross - $discount, 0);

        return [
            'gross'    => $gross,
            'discount' => $discount,
            'net'      => $net,
            'paid'     => $paid,
            'due'      => max($net - $paid, 0),
        ];
    }

    /**
     * What the patient actually agreed to. Kept apart from treatment — which is what the doctor
     * advised — because the gap between the two is the thing a follow-up needs to see: a plan
     * declined on cost or fear reads very differently from one never offered.
     */
    public function getWillingTreatmentListAttribute(): array
    {
        return self::splitTerms($this->willing_treatment);
    }

    public static function splitTerms($value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn($term) => trim($term))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recorder()
    {
        return $this->belongsTo(VendorEmployee::class, 'recorded_by');
    }
}
