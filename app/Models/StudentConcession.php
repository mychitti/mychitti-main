<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class StudentConcession extends Model
{
    use BranchScoped;

    protected $table = 'student_concessions';

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'fee_concession_id',
        'academic_session_id', 'note', 'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scheme()
    {
        return $this->belongsTo(FeeConcession::class, 'fee_concession_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Total concession a student qualifies for against a gross fee total.
     * Returns ['amount' => float, 'schemes' => [['name','amount'], ...]].
     */
    public static function concessionFor($student, $sessionId, float $grossTotal): array
    {
        $rows = self::where('store_id', $student->store_id)
            ->where('student_id', $student->id)->where('status', 1)
            ->where(function ($q) use ($sessionId) {
                $q->whereNull('academic_session_id');
                if ($sessionId) $q->orWhere('academic_session_id', $sessionId);
            })
            ->with('scheme')->get();

        $total = 0;
        $schemes = [];
        foreach ($rows as $r) {
            $s = $r->scheme;
            if (!$s || !$s->is_active) continue;
            if ($s->type === 'percent') {
                $amt = round($grossTotal * $s->value / 100, 2);
                if ($s->max_amount > 0) $amt = min($amt, $s->max_amount);
            } else {
                $amt = (float) $s->value;
            }
            if ($amt <= 0) continue;
            $total += $amt;
            $schemes[] = ['name' => $s->name, 'amount' => round($amt, 2)];
        }
        $total = min(round($total, 2), $grossTotal);
        return ['amount' => $total, 'schemes' => $schemes];
    }
}
