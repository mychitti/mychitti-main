@extends('layouts.vendor.app')
@section('title', 'Clinical Terms')

@section('content')
<div class="content container-fluid">

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
        <div>
            <h1 class="page-header-title mb-0">
                <span class="page-header-icon"><i class="tio-label" style="font-size:22px;"></i></span>
                Clinical Terms
            </h1>
            <span class="text-muted" style="font-size:12px;">
                What the diagnosis and treatment boxes offer during a consultation.
                @if ($categoryLabel)
                    Your category is <b>{{ $categoryLabel }}</b>.
                @else
                    No hospital category chosen yet — you are seeing the shared list only.
                @endif
            </span>
        </div>
        <a href="{{ route('vendor.opd.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="tio-back-ui"></i> OPD Register
        </a>
    </div>

    @if (!$category)
        <div class="alert alert-warning" style="font-size:13px;">
            Pick a hospital category under <b>Hospital Settings</b> to be offered terms that match
            your practice. Until then only the shared list is shown.
        </div>
    @endif

    <div class="row" style="row-gap:16px;">
        @foreach ($lists as $type => $list)
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">{{ ucfirst($type) }}</h5>
                    </div>

                    <div class="card-body">
                        <div class="text-muted mb-2" style="font-size:12px;">
                            From MyChitti — switch off anything you never use.
                        </div>
                        @if ($list['catalogue']->isEmpty())
                            <div class="text-muted" style="font-size:13px;">Nothing offered for your category yet.</div>
                        @else
                            <div class="d-flex flex-wrap" style="gap:6px;">
                                @foreach ($list['catalogue'] as $name)
                                    @php($isHidden = $list['hidden']->has(mb_strtolower(trim($name))))
                                    <form method="post" action="{{ route('vendor.opd.terms.update') }}" class="mb-0">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $type }}">
                                        <input type="hidden" name="name" value="{{ $name }}">
                                        <input type="hidden" name="action" value="{{ $isHidden ? 'show' : 'hide' }}">
                                        <button class="btn btn-sm {{ $isHidden ? 'btn-outline-secondary' : 'btn-soft-primary' }}"
                                                style="font-size:12px;{{ $isHidden ? 'text-decoration:line-through;opacity:.6;' : '' }}"
                                                title="{{ $isHidden ? 'Offer this again' : 'Stop offering this' }}">
                                            {{ $name }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif

                        <hr class="my-3">

                        <div class="text-muted mb-2" style="font-size:12px;">
                            Added by your doctors — anything typed during a consultation joins this list.
                        </div>
                        @if ($list['own']->isEmpty())
                            <div class="text-muted" style="font-size:13px;">Nothing yet.</div>
                        @else
                            <div class="d-flex flex-wrap" style="gap:6px;">
                                @foreach ($list['own'] as $name)
                                    <form method="post" action="{{ route('vendor.opd.terms.update') }}" class="mb-0"
                                          onsubmit="return confirm('Remove &quot;{{ $name }}&quot; from your list? Visits already recorded against it keep their text.')">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $type }}">
                                        <input type="hidden" name="name" value="{{ $name }}">
                                        <input type="hidden" name="action" value="hide">
                                        <button class="btn btn-sm btn-soft-success" style="font-size:12px;"
                                                title="Remove from your list">
                                            {{ $name }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="card-footer text-muted" style="font-size:11px;">
                        Hiding only affects your hospital. Nothing already written on a past visit changes.
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
