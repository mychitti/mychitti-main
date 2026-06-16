<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\QuestionBankItem;
use App\Models\SchoolClass;
use App\Models\Store;
use App\Models\Subject;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; 

class QuestionBankController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('question_bank')) {
            DB::statement("CREATE TABLE IF NOT EXISTS question_bank (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                school_class_id BIGINT UNSIGNED NULL, subject_id BIGINT UNSIGNED NULL,
                chapter VARCHAR(150) NULL, question_type VARCHAR(30) NOT NULL DEFAULT 'MCQ',
                difficulty VARCHAR(10) NOT NULL DEFAULT 'Medium', marks DECIMAL(6,2) NOT NULL DEFAULT 1,
                question_text TEXT NULL, options LONGTEXT NULL, answer TEXT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1, created_by VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, school_class_id, subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $classId    = $request->query('class_id');
        $subjectId  = $request->query('subject_id');
        $type       = $request->query('type');
        $difficulty = $request->query('difficulty');
        $search     = trim($request->query('search', ''));

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get();
        $subjects = Subject::where('store_id', $store_id)->orderBy('name')->get();

        $base = QuestionBankItem::where('store_id', $store_id);
        $counts = [
            'total'  => (clone $base)->count(),
            'Easy'   => (clone $base)->where('difficulty', 'Easy')->count(),
            'Medium' => (clone $base)->where('difficulty', 'Medium')->count(),
            'Hard'   => (clone $base)->where('difficulty', 'Hard')->count(),
        ];

        $questions = QuestionBankItem::where('store_id', $store_id)
            ->when($classId, fn($q) => $q->where('school_class_id', $classId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($type, fn($q) => $q->where('question_type', $type))
            ->when($difficulty, fn($q) => $q->where('difficulty', $difficulty))
            ->when($search, fn($q) => $q->where('question_text', 'like', "%$search%"))
            ->with(['schoolClass', 'subject'])
            ->orderByDesc('id')->paginate(config('default_pagination'))->withQueryString();

        return view('school::vendor.question-bank.index', compact(
            'classes', 'subjects', 'questions', 'counts',
            'classId', 'subjectId', 'type', 'difficulty', 'search'
        ));
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $request->validate([
            'school_class_id' => 'required|integer',
            'subject_id'      => 'required|integer',
            'question_type'   => 'required|string|max:30',
            'difficulty'      => 'required|in:Easy,Medium,Hard',
            'marks'           => 'required|numeric|min:0',
            'question_text'   => 'required|string',
        ]);
        $store_id = Helpers::get_store_id();

        $options = null;
        if ($request->question_type === 'MCQ') {
            $options = collect($request->input('options', []))->map(fn($o) => trim((string) $o))->filter()->values()->all();
        }

        QuestionBankItem::updateOrCreate(
            ['id' => $request->id, 'store_id' => $store_id],
            [
                'store_id'        => $store_id,
                'school_class_id' => $request->school_class_id,
                'subject_id'      => $request->subject_id,
                'chapter'         => $request->chapter,
                'question_type'   => $request->question_type,
                'difficulty'      => $request->difficulty,
                'marks'           => (float) $request->marks,
                'question_text'   => $request->question_text,
                'options'         => $options,
                'answer'          => $request->answer,
                'status'          => 1,
                'created_by'      => $this->currentUserName(),
            ]
        );
        Toastr::success('Question saved.');
        return back();
    }

    public function delete($id)
    {
        QuestionBankItem::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Question removed.');
        return back();
    }

    /** Generate a printable question paper from the bank. */
    public function paper(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();

        $request->validate([
            'school_class_id' => 'required|integer',
            'subject_id'      => 'required|integer',
        ]);

        $class   = SchoolClass::where('store_id', $store_id)->find($request->school_class_id);
        $subject = Subject::where('store_id', $store_id)->find($request->subject_id);
        $store   = Store::withoutGlobalScopes()->find($store_id);

        $q = QuestionBankItem::where('store_id', $store_id)->where('status', 1)
            ->where('school_class_id', $request->school_class_id)
            ->where('subject_id', $request->subject_id)
            ->when($request->chapter, fn($w) => $w->where('chapter', $request->chapter))
            ->when($request->difficulty, fn($w) => $w->where('difficulty', $request->difficulty));

        $limit = (int) ($request->count ?: 10);
        $questions = $q->inRandomOrder()->limit(max(1, $limit))->get();

        $totalMarks  = $questions->sum('marks');
        $showAnswers = $request->boolean('answer_key');
        $examTitle   = $request->title ?: 'Question Paper';
        $duration    = $request->duration;

        return view('school::vendor.question-bank.paper', compact(
            'questions', 'class', 'subject', 'store', 'totalMarks', 'showAnswers', 'examTitle', 'duration'
        ));
    }

    private function currentUserName(): string
    {
        if (auth('vendor_employee')->check()) {
            $u = auth('vendor_employee')->user();
            return trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: 'Staff';
        }
        $v = auth('vendor')->user();
        return trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? '')) ?: 'Admin';
    }
}
