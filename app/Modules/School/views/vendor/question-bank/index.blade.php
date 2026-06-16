@extends('layouts.vendor.app')
@section('title', 'Question Bank')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <h1 class="page-header-title mb-0"><i class="tio-folder-bookmarked mr-1"></i> Question Bank</h1>
    </div>

    {{-- Summary --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--indigo">
            <div class="sch-stat-label">Total Questions</div><div class="sch-stat-value">{{ $counts['total'] }}</div></div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--green">
            <div class="sch-stat-label">Easy</div><div class="sch-stat-value">{{ $counts['Easy'] }}</div></div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--amber">
            <div class="sch-stat-label">Medium</div><div class="sch-stat-value">{{ $counts['Medium'] }}</div></div></div>
        <div class="col-sm-6 col-xl-3 mb-3"><div class="sch-stat sch-stat--sky">
            <div class="sch-stat-label">Hard</div><div class="sch-stat-value">{{ $counts['Hard'] }}</div></div></div>
    </div>

    <div class="row">
        {{-- Add / edit question --}}
        <div class="col-lg-5 mb-3">
            <div class="card"><div class="card-header py-3"><h6 class="mb-0">Add / Edit Question</h6></div>
            <form action="{{ route('vendor.school.question-bank.save') }}" method="POST">
                @csrf <input type="hidden" name="id" id="q_id">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-6"><label class="input-label">Class *</label>
                            <select name="school_class_id" id="q_class" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                            </select></div>
                        <div class="form-group col-6"><label class="input-label">Subject *</label>
                            <select name="subject_id" id="q_subject" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6"><label class="input-label">Type *</label>
                            <select name="question_type" id="q_type" class="form-control" onchange="qTypeUI()">
                                @foreach(\App\Models\QuestionBankItem::TYPES as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select></div>
                        <div class="form-group col-3"><label class="input-label">Level *</label>
                            <select name="difficulty" id="q_diff" class="form-control">
                                @foreach(\App\Models\QuestionBankItem::LEVELS as $l)<option value="{{ $l }}" @selected($l==='Medium')>{{ $l }}</option>@endforeach
                            </select></div>
                        <div class="form-group col-3"><label class="input-label">Marks *</label>
                            <input type="number" step="0.5" min="0" name="marks" id="q_marks" class="form-control" value="1" required></div>
                    </div>
                    <div class="form-group"><label class="input-label">Chapter / Topic</label>
                        <input name="chapter" id="q_chapter" class="form-control" maxlength="150" placeholder="optional"></div>
                    <div class="form-group"><label class="input-label">Question *</label>
                        <textarea name="question_text" id="q_text" class="form-control" rows="3" required></textarea></div>

                    <div id="q_mcq">
                        <label class="input-label">Options (MCQ)</label>
                        @for($i = 0; $i < 4; $i++)
                            <input name="options[]" class="form-control form-control-sm mb-2 q-opt" placeholder="Option {{ chr(65 + $i) }}">
                        @endfor
                    </div>
                    <div class="form-group"><label class="input-label" id="q_ans_lbl">Correct Answer</label>
                        <input name="answer" id="q_answer" class="form-control" placeholder="answer / model answer"></div>
                </div>
                <div class="card-footer text-right">
                    <button type="button" class="btn btn-white btn-sm" onclick="qReset()">Clear</button>
                    @if(hasPermission("question_bank","add"))<button class="btn btn--primary btn-sm"><i class="tio-save"></i> Save Question</button>@endif
                </div>
            </form></div>

            {{-- Paper generator --}}
            <div class="card mt-3"><div class="card-header py-3"><h6 class="mb-0"><i class="tio-print mr-1"></i> Generate Question Paper</h6></div>
            <form action="{{ route('vendor.school.question-bank.paper') }}" method="GET" target="_blank">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-6"><label class="input-label">Class *</label>
                            <select name="school_class_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                            </select></div>
                        <div class="form-group col-6"><label class="input-label">Subject *</label>
                            <select name="subject_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-4"><label class="input-label">No. of Qs</label>
                            <input type="number" min="1" name="count" class="form-control" value="10"></div>
                        <div class="form-group col-4"><label class="input-label">Difficulty</label>
                            <select name="difficulty" class="form-control"><option value="">Any</option>
                                @foreach(\App\Models\QuestionBankItem::LEVELS as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
                            </select></div>
                        <div class="form-group col-4"><label class="input-label">Duration</label>
                            <input name="duration" class="form-control" placeholder="e.g. 2 hrs"></div>
                    </div>
                    <div class="form-group"><label class="input-label">Paper Title</label>
                        <input name="title" class="form-control" value="Question Paper"></div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="pp_key" name="answer_key" value="1">
                        <label class="custom-control-label" for="pp_key">Include answer key</label>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button class="btn btn-outline-primary btn-sm"><i class="tio-print"></i> Generate</button>
                </div>
            </form></div>
        </div>

        {{-- List --}}
        <div class="col-lg-7 mb-3">
            @if(hasPermission("question_bank","view"))<div class="card"><div class="card-header py-3">
                <form method="GET" class="form-row w-100 align-items-end" style="gap:6px 0;">
                    <div class="col-6 col-md-3 px-1"><select name="class_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Classes</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected((string)$classId===(string)$c->id)>{{ $c->name }}</option>@endforeach
                    </select></div>
                    <div class="col-6 col-md-3 px-1"><select name="subject_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Subjects</option>@foreach($subjects as $s)<option value="{{ $s->id }}" @selected((string)$subjectId===(string)$s->id)>{{ $s->name }}</option>@endforeach
                    </select></div>
                    <div class="col-6 col-md-3 px-1"><select name="difficulty" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">Any Level</option>@foreach(\App\Models\QuestionBankItem::LEVELS as $l)<option value="{{ $l }}" @selected($difficulty===$l)>{{ $l }}</option>@endforeach
                    </select></div>
                    <div class="col-6 col-md-3 px-1"><input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search…"></div>
                </form>
            </div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Question</th><th>Class / Subject</th><th>Type</th><th class="text-center">Marks</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($questions as $q)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($q->question_text, 90) }}
                                <br><span class="badge badge-soft-{{ ['Easy'=>'success','Medium'=>'warning','Hard'=>'danger'][$q->difficulty] ?? 'secondary' }}">{{ $q->difficulty }}</span>
                                @if($q->chapter)<small class="text-muted">· {{ $q->chapter }}</small>@endif</td>
                            <td><small>{{ $q->schoolClass?->name ?? '—' }}<br>{{ $q->subject?->name ?? '—' }}</small></td>
                            <td><small>{{ $q->question_type }}</small></td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }}</td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-white" onclick='qEdit(@json($q))'><i class="tio-edit"></i></button>
                                @if(hasPermission("question_bank","delete"))<a href="{{ route('vendor.school.question-bank.delete', $q->id) }}" class="btn btn-sm btn-white text-danger" onclick="return confirm('Delete this question?')"><i class="tio-delete"></i></a>@endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">No questions yet. Add some to build your bank.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
            @if(hasPermission("question_bank","view") && count($questions))<div class="mt-3 px-2">{!! $questions->links() !!}</div>@endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function qTypeUI() {
    var t = document.getElementById('q_type').value;
    document.getElementById('q_mcq').style.display = t === 'MCQ' ? 'block' : 'none';
    document.getElementById('q_ans_lbl').textContent = t === 'MCQ' ? 'Correct Option (text)' : 'Answer / Model Answer';
}
function qEdit(q) {
    document.getElementById('q_id').value = q.id;
    document.getElementById('q_class').value = q.school_class_id;
    document.getElementById('q_subject').value = q.subject_id;
    document.getElementById('q_type').value = q.question_type;
    document.getElementById('q_diff').value = q.difficulty;
    document.getElementById('q_marks').value = q.marks;
    document.getElementById('q_chapter').value = q.chapter ?? '';
    document.getElementById('q_text').value = q.question_text ?? '';
    document.getElementById('q_answer').value = q.answer ?? '';
    var opts = q.options || [];
    document.querySelectorAll('.q-opt').forEach((el, i) => el.value = opts[i] ?? '');
    qTypeUI();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function qReset() {
    ['q_id','q_chapter','q_text','q_answer'].forEach(id => document.getElementById(id).value = '');
    document.querySelectorAll('.q-opt').forEach(el => el.value = '');
    document.getElementById('q_marks').value = 1;
    qTypeUI();
}
qTypeUI();
</script>
@endpush
