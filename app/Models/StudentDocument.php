<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    protected $table = 'student_documents';

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'doc_type', 'title', 'file', 'uploaded_by',
    ];

    public const TYPES = [
        'tc'           => 'Transfer Certificate (TC)',
        'marksheet'    => 'Marksheet / Report Card',
        'birth_cert'   => 'Birth Certificate',
        'aadhaar'      => 'Aadhaar / ID Proof',
        'caste_cert'   => 'Caste / Category Certificate',
        'photo'        => 'Photograph',
        'medical'      => 'Medical Record',
        'other'        => 'Other',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->doc_type] ?? ucfirst((string) $this->doc_type);
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo((string) $this->file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }
}
