@extends('layouts.admin.app')

@section('title', translate('Doc AI Validation'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-shield-outlined"></i> {{ translate('Vendor Document AI Validation') }}</h1>
            <form method="get" class="mb-0">
                <select name="type" class="form-control form-control-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">{{ translate('All types') }}</option>
                    @foreach ($docTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <p class="text-muted" style="font-size:13px;">
            {{ translate('When a vendor uploads an ID proof, GST certificate or other document, the AI reads it and compares it against the number they typed in the form. The rules you add below are applied on every check.') }}
        </p>

        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">{{ translate('Validation Rules') }} ({{ $rules->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Rule') }}</th>
                                        <th>{{ translate('Applies To') }}</th>
                                        <th class="text-center">{{ translate('On Failure') }}</th>
                                        <th class="text-center">{{ translate('Status') }}</th>
                                        <th class="text-right">{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rules as $rule)
                                        <tr>
                                            <td style="max-width:260px;">
                                                <b>{{ $rule->title }}</b>
                                                <small class="text-muted d-block text-truncate">{{ \Illuminate\Support\Str::limit($rule->rule, 90) }}</small>
                                            </td>
                                            <td><span class="badge badge-soft-info">{{ \App\Models\DocValidationRule::typeLabel($rule->doc_type) }}</span></td>
                                            <td class="text-center">
                                                <span class="badge badge-soft-{{ $rule->severity === 'block' ? 'danger' : 'warning' }}">
                                                    {{ $rule->severity === 'block' ? translate('Block') : translate('Review') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.business-settings.third-party.doc-validation.toggle') }}" method="post" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $rule->id }}">
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                                            title="{{ $rule->active ? translate('Pause') : translate('Resume') }}">
                                                        <span class="badge badge-soft-{{ $rule->active ? 'success' : 'secondary' }}">{{ $rule->active ? translate('In use') : translate('Paused') }}</span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary dv-edit"
                                                        data-id="{{ $rule->id }}" data-type="{{ $rule->doc_type }}"
                                                        data-severity="{{ $rule->severity }}"
                                                        data-title="{{ $rule->title }}" data-rule="{{ $rule->rule }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.business-settings.third-party.doc-validation.delete') }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('{{ translate('Delete this rule?') }}');">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $rule->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="tio-delete"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                {{ translate('No rules yet. The AI still checks the document type and number match — add rules on the right to teach it more.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Recent Checks') }}</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ translate('Store') }}</th>
                                        <th>{{ translate('Document') }}</th>
                                        <th>{{ translate('Entered / Found') }}</th>
                                        <th class="text-center">{{ translate('Verdict') }}</th>
                                        <th>{{ translate('When') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logs as $log)
                                        <tr>
                                            <td>
                                                @if ($log->store)
                                                    <a href="{{ route('admin.store.view', ['store' => $log->store_id, 'tab' => 'documents']) }}">{{ $log->store->name }}</a>
                                                @else
                                                    <span class="text-muted">{{ translate('Signup') }}</span>
                                                @endif
                                                <small class="text-muted d-block">{{ str_replace('_', ' ', $log->source) }}</small>
                                            </td>
                                            <td>
                                                {{ \App\Models\DocValidationRule::typeLabel($log->doc_type) }}
                                                <small class="text-muted d-block text-truncate" style="max-width:160px;">{{ $log->file_name }}</small>
                                            </td>
                                            <td style="font-size:12px;">
                                                <span class="text-muted">{{ $log->expected_number ?: '—' }}</span><br>
                                                <b>{{ $log->extracted_number ?: '—' }}</b>
                                            </td>
                                            <td class="text-center">
                                                @php($badge = ['pass' => 'success', 'fail' => 'danger', 'review' => 'warning'][$log->verdict] ?? 'secondary')
                                                <span class="badge badge-soft-{{ $badge }}" title="{{ $log->summary }}">{{ ucfirst($log->verdict) }}</span>
                                            </td>
                                            <td class="text-nowrap" style="font-size:12px;">{{ $log->created_at ? $log->created_at->diffForHumans() : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">{{ translate('No documents have been checked yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Settings') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.third-party.doc-validation.settings') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="toggle-switch d-flex align-items-center mb-0">
                                    <input type="checkbox" name="status" value="1" class="toggle-switch-input" {{ $settings['status'] ? 'checked' : '' }}>
                                    <span class="toggle-switch-label mr-2"><span class="toggle-switch-indicator"></span></span>
                                    <span class="toggle-switch-content"><span class="d-block">{{ translate('Enable AI document validation') }}</span></span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Check documents uploaded from') }}</label>
                                <div class="pl-1">
                                    <label class="d-block mb-1"><input type="checkbox" name="source_registration" value="1" {{ $settings['sources']['registration'] ? 'checked' : '' }}> {{ translate('Vendor registration form') }}</label>
                                    <label class="d-block mb-1"><input type="checkbox" name="source_vendor_panel" value="1" {{ $settings['sources']['vendor_panel'] ? 'checked' : '' }}> {{ translate('Vendor panel — business settings') }}</label>
                                    <label class="d-block mb-0"><input type="checkbox" name="source_admin_panel" value="1" {{ $settings['sources']['admin_panel'] ? 'checked' : '' }}> {{ translate('Admin panel — store add / edit') }}</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('When a document fails') }}</label>
                                <select name="mode" class="form-control">
                                    <option value="block" {{ $settings['mode'] === 'block' ? 'selected' : '' }}>{{ translate('Reject the upload and show the reason') }}</option>
                                    <option value="warn" {{ $settings['mode'] === 'warn' ? 'selected' : '' }}>{{ translate('Allow it, but warn and log for review') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('When the AI cannot be reached') }}</label>
                                <select name="on_error" class="form-control">
                                    <option value="allow" {{ $settings['on_error'] === 'allow' ? 'selected' : '' }}>{{ translate('Allow the upload (recommended)') }}</option>
                                    <option value="block" {{ $settings['on_error'] === 'block' ? 'selected' : '' }}>{{ translate('Block the upload') }}</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-7 form-group">
                                    <label class="form-label">{{ translate('Model') }}</label>
                                    <input type="text" name="model" class="form-control" value="{{ $settings['model'] }}" required>
                                </div>
                                <div class="col-5 form-group">
                                    <label class="form-label">{{ translate('Effort') }}</label>
                                    <select name="effort" class="form-control">
                                        @foreach (['low', 'medium', 'high'] as $level)
                                            <option value="{{ $level }}" {{ $settings['effort'] === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn--primary">{{ translate('Save Settings') }}</button>
                        </form>
                    </div>
                </div>

                {{-- Test console: runs a real document through the live checker with the rules as
                     they stand, so a rule can be proved before a vendor meets it. --}}
                <div class="card mb-3">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Test a Document') }}</h5></div>
                    <div class="card-body">
                        <form id="dvTestForm" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('Document Type') }}</label>
                                <select name="doc_type" class="form-control" required>
                                    <option value="id_doc">{{ translate('ID Proof') }}</option>
                                    <option value="gst_doc">{{ translate('GST Certificate') }}</option>
                                    <option value="fssai_doc">{{ translate('FSSAI Licence') }}</option>
                                    <option value="other">{{ translate('Other Document') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Number the vendor would type') }}</label>
                                <input type="text" name="expected_number" class="form-control" maxlength="100"
                                       placeholder="{{ translate('e.g. 27AAECS1234F1Z5') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Document file') }} <small class="text-muted">(JPG, PNG, WEBP or PDF)</small></label>
                                <input type="file" name="test_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                            </div>
                            <button type="submit" class="btn btn--primary" id="dvTestBtn">{{ translate('Run Check') }}</button>
                        </form>
                        <div id="dvTestResult" class="mt-3" style="display:none;"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header py-2"><h5 class="card-title mb-0">{{ translate('Add Rule') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.third-party.doc-validation.store') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ translate('Applies To') }}</label>
                                <select name="doc_type" class="form-control" required>
                                    @foreach ($docTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Rule Name') }}</label>
                                <input type="text" name="title" class="form-control" maxlength="200" required
                                       placeholder="{{ translate('e.g. GSTIN format') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('Rule') }}</label>
                                <textarea name="rule" class="form-control" rows="4" maxlength="5000" required
                                          placeholder="{{ translate('e.g. A GST certificate must show a 15-character GSTIN whose first two digits are a valid Indian state code and whose 13th character is a digit.') }}"></textarea>
                                <small class="text-muted">{{ translate('Write it as an instruction in plain English. It is sent to the AI word for word.') }}</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ translate('If the document breaks this rule') }}</label>
                                <select name="severity" class="form-control" required>
                                    <option value="block">{{ translate('Fail the document') }}</option>
                                    <option value="warn">{{ translate('Send it for manual review') }}</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn--primary">{{ translate('Add Rule') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dvEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.business-settings.third-party.doc-validation.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" id="dvEditId">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Edit Rule') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Applies To') }}</label>
                            <select name="doc_type" id="dvEditType" class="form-control" required>
                                @foreach ($docTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Rule Name') }}</label>
                            <input type="text" name="title" id="dvEditTitle" class="form-control" maxlength="200" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Rule') }}</label>
                            <textarea name="rule" id="dvEditRule" class="form-control" rows="5" maxlength="5000" required></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('If the document breaks this rule') }}</label>
                            <select name="severity" id="dvEditSeverity" class="form-control" required>
                                <option value="block">{{ translate('Fail the document') }}</option>
                                <option value="warn">{{ translate('Send it for manual review') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).on('click', '.dv-edit', function() {
            $('#dvEditId').val($(this).data('id'));
            $('#dvEditType').val($(this).data('type'));
            $('#dvEditSeverity').val($(this).data('severity'));
            $('#dvEditTitle').val($(this).data('title'));
            $('#dvEditRule').val($(this).data('rule'));
            $('#dvEditModal').modal('show');
        });

        $('#dvTestForm').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#dvTestBtn'),
                $out = $('#dvTestResult');
            $btn.prop('disabled', true).text('{{ translate('Checking…') }}');
            $out.hide();

            $.ajax({
                url: "{{ route('admin.business-settings.third-party.doc-validation.preview') }}",
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(res) {
                    $out.html(renderResult(res.result)).show();
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('The check could not run.') }}';
                    $out.html('<div class="alert alert-danger mb-0">' + msg + '</div>').show();
                },
                complete: function() {
                    $btn.prop('disabled', false).text('{{ translate('Run Check') }}');
                }
            });
        });

        function renderResult(r) {
            var tone = {
                    pass: 'success',
                    fail: 'danger',
                    review: 'warning'
                }[r.verdict] || 'secondary',
                html = '<div class="alert alert-' + tone + '"><b>' + r.verdict.toUpperCase() + '</b> — ' +
                escapeHtml(r.message) + '</div>';

            if (r.extracted && Object.keys(r.extracted).length) {
                html += '<table class="table table-sm mb-2">';
                $.each(r.extracted, function(k, v) {
                    if (v) {
                        html += '<tr><td class="text-muted" style="width:45%;">' + escapeHtml(k.replace(/_/g,
                            ' ')) + '</td><td>' + escapeHtml(v) + '</td></tr>';
                    }
                });
                html += '</table>';
            }

            if (r.issues && r.issues.length) {
                html += '<ul class="pl-3 mb-0" style="font-size:13px;">';
                r.issues.forEach(function(i) {
                    html += '<li><span class="badge badge-soft-' + (i.severity === 'block' ? 'danger' :
                        'warning') + '">' + escapeHtml(i.severity) + '</span> ' + escapeHtml(i.detail) +
                        '</li>';
                });
                html += '</ul>';
            }

            return html;
        }

        function escapeHtml(s) {
            return $('<div>').text(s == null ? '' : s).html();
        }
    </script>
@endpush
