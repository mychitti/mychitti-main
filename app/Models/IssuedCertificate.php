<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class IssuedCertificate extends Model
{
    use BranchScoped;

    protected $table = 'issued_certificates';

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'type', 'design', 'serial_no',
        'issue_date', 'reason', 'body', 'issued_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public const TYPES = [
        'tc'        => 'Transfer Certificate',
        'bonafide'  => 'Bonafide Certificate',
        'character' => 'Character Certificate',
    ];

    public const DESIGNS = [
        'classic' => 'Classic — Double Border',
        'elegant' => 'Elegant — Ornate Gold',
        'modern'  => 'Modern — Colour Header',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
