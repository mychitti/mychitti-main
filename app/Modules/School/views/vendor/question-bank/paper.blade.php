<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $examTitle }} — {{ $subject?->name }}</title>
    <style>
        body { font-family: 'Times New Roman', Georgia, serif; color: #111; margin: 0; background: #f1f5f9; }
        .sheet { width: 760px; margin: 20px auto; background: #fff; padding: 38px 46px; box-shadow: 0 4px 16px rgba(0,0,0,.1); }
        .hd { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 8px; }
        .hd h2 { margin: 0 0 2px; font-size: 20px; }
        .hd .sub { font-size: 13px; color: #444; }
        .meta { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin: 12px 0 18px; }
        .meta .line { flex: 1; }
        .meta .r { text-align: right; }
        ol.qs { padding-left: 26px; }
        ol.qs > li { margin-bottom: 14px; font-size: 14px; line-height: 1.5; }
        .qmeta { float: right; font-weight: bold; font-size: 12px; color: #555; }
        .opts { list-style: upper-alpha; margin: 6px 0 0 4px; padding-left: 22px; font-size: 13px; }
        .opts li { margin-bottom: 2px; }
        .ans { color: #15803d; font-size: 12px; margin-top: 4px; font-style: italic; }
        .keyhd { margin-top: 30px; border-top: 2px dashed #999; padding-top: 14px; font-size: 15px; font-weight: bold; }
        .no-print { text-align: center; margin: 14px 0; }
        .btn { padding: 8px 18px; border: none; border-radius: 6px; background: #4f46e5; color: #fff; font-weight: 600; cursor: pointer; }
        @media print { .no-print { display: none; } body { background: #fff; } .sheet { box-shadow: none; margin: 0; width: auto; } }
    </style>
</head>
<body>
    <div class="no-print"><button class="btn" onclick="window.print()">🖨 Print Paper</button></div>
    <div class="sheet">
        <div class="hd">
            <h2>{{ $store?->name ?? 'School' }}</h2>
            <div class="sub">{{ $examTitle }} &nbsp;·&nbsp; {{ $class?->name }} &nbsp;·&nbsp; {{ $subject?->name }}</div>
        </div>
        <div class="meta">
            <div class="line">Name: ____________________</div>
            <div class="line" style="text-align:center;">@if($duration)Time: {{ $duration }}@endif</div>
            <div class="line r">Max Marks: {{ rtrim(rtrim(number_format($totalMarks,2),'0'),'.') }}</div>
        </div>

        @if($questions->isEmpty())
            <p style="text-align:center;color:#888;">No questions found in the bank for this selection.</p>
        @else
            <ol class="qs">
                @foreach($questions as $q)
                    <li>
                        <span class="qmeta">[{{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }}]</span>
                        {{ $q->question_text }}
                        @if($q->question_type === 'MCQ' && is_array($q->options) && count($q->options))
                            <ol class="opts">
                                @foreach($q->options as $opt)<li>{{ $opt }}</li>@endforeach
                            </ol>
                        @endif
                        @if($showAnswers && $q->answer)
                            <div class="ans">Ans: {{ $q->answer }}</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</body>
</html>
