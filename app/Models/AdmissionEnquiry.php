<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class AdmissionEnquiry extends Model
{
    use BranchScoped;

    protected $table = 'admission_enquiries';

    protected $fillable = [
        'store_id', 'branch_id', 'enquiry_no', 'enquiry_date',
        'student_name', 'dob', 'gender', 'seeking_class_id',
        'guardian_name', 'guardian_phone', 'phone', 'email',
        'previous_school', 'source', 'status', 'follow_up_date',
        'remarks', 'converted_student_id',
    ];

    protected $casts = [
        'enquiry_date'   => 'date',
        'dob'            => 'date',
        'follow_up_date' => 'date',
    ];

    public const STATUSES = [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'visited'   => 'Visited',
        'admitted'  => 'Admitted',
        'rejected'  => 'Rejected',
    ];

    public const SOURCES = ['Walk-in', 'Phone', 'Website', 'Referral', 'Social Media', 'Advertisement', 'Other'];

    public function seekingClass()
    {
        return $this->belongsTo(SchoolClass::class, 'seeking_class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return [
            'new'       => 'badge-soft-info',
            'contacted' => 'badge-soft-warning',
            'visited'   => 'badge-soft-warning',
            'admitted'  => 'badge-soft-success',
            'rejected'  => 'badge-soft-danger',
        ][$this->status] ?? 'badge-soft-info';
    }
}
