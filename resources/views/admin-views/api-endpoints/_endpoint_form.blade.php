{{-- Shared add/edit endpoint form body. $endpoint is null when adding. --}}
@php
    $endpoint = $endpoint ?? null;
    $uid = $endpoint ? 'e' . $endpoint->id : 'new';
    $paramRows = $endpoint ? $endpoint->param_list : [];
    $headerRows = $endpoint ? $endpoint->header_list : [];
@endphp

<div class="row api-form">
    <div class="col-4 col-md-2 form-group">
        <label>{{ translate('Method') }}</label>
        <select name="method" class="form-control form-control-sm">
            @foreach (\App\Models\ApiEndpoint::METHODS as $m)
                <option value="{{ $m }}" {{ ($endpoint->method ?? 'GET') == $m ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-8 col-md-6 form-group">
        <label>{{ translate('Endpoint') }} <span class="text-danger">*</span></label>
        <input type="text" name="endpoint" class="form-control form-control-sm" required
            value="{{ $endpoint->endpoint ?? '' }}" placeholder="/api/v1/stores/{id}">
    </div>
    <div class="col-md-4 form-group">
        <label>{{ translate('Name') }}</label>
        <input type="text" name="name" class="form-control form-control-sm" value="{{ $endpoint->name ?? '' }}"
            placeholder="{{ translate('Get store details') }}">
    </div>

    <div class="col-md-3 form-group">
        <label>{{ translate('Folder / Group') }}</label>
        <input type="text" name="folder" class="form-control form-control-sm" value="{{ $endpoint->folder ?? '' }}"
            placeholder="{{ translate('Stores') }}">
    </div>
    <div class="col-md-9 form-group">
        <label>{{ translate('Description') }}</label>
        <textarea name="description" rows="1" class="form-control form-control-sm">{{ $endpoint->description ?? '' }}</textarea>
    </div>

    {{-- Params --}}
    <div class="col-md-6 form-group">
        <label class="d-flex justify-content-between align-items-center">
            <span>{{ translate('Params') }}</span>
            <button type="button" class="btn btn-xs btn-outline-primary kv-add" data-target="params-{{ $uid }}">
                <i class="tio-add"></i>
            </button>
        </label>
        <div id="params-{{ $uid }}" class="kv-wrap">
            @forelse ($paramRows as $row)
                <div class="kv-row">
                    <input type="text" name="param_key[]" class="form-control form-control-sm"
                        value="{{ $row['key'] }}" placeholder="{{ translate('key') }}">
                    <input type="text" name="param_value[]" class="form-control form-control-sm"
                        value="{{ $row['value'] }}" placeholder="{{ translate('value') }}">
                    <input type="text" name="param_note[]" class="form-control form-control-sm"
                        value="{{ $row['note'] }}" placeholder="{{ translate('note') }}">
                    <button type="button" class="btn btn-xs btn-outline-danger kv-remove"><i
                            class="tio-clear"></i></button>
                </div>
            @empty
                <div class="kv-row">
                    <input type="text" name="param_key[]" class="form-control form-control-sm"
                        placeholder="{{ translate('key') }}">
                    <input type="text" name="param_value[]" class="form-control form-control-sm"
                        placeholder="{{ translate('value') }}">
                    <input type="text" name="param_note[]" class="form-control form-control-sm"
                        placeholder="{{ translate('note') }}">
                    <button type="button" class="btn btn-xs btn-outline-danger kv-remove"><i
                            class="tio-clear"></i></button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Headers --}}
    <div class="col-md-6 form-group">
        <label class="d-flex justify-content-between align-items-center">
            <span>{{ translate('Headers') }}</span>
            <button type="button" class="btn btn-xs btn-outline-primary kv-add" data-target="headers-{{ $uid }}">
                <i class="tio-add"></i>
            </button>
        </label>
        <div id="headers-{{ $uid }}" class="kv-wrap">
            @forelse ($headerRows as $row)
                <div class="kv-row">
                    <input type="text" name="header_key[]" class="form-control form-control-sm"
                        value="{{ $row['key'] }}" placeholder="Authorization">
                    <input type="text" name="header_value[]" class="form-control form-control-sm"
                        value="{{ $row['value'] }}" placeholder="Bearer {token}">
                    <input type="text" name="header_note[]" class="form-control form-control-sm"
                        value="{{ $row['note'] }}" placeholder="{{ translate('note') }}">
                    <button type="button" class="btn btn-xs btn-outline-danger kv-remove"><i
                            class="tio-clear"></i></button>
                </div>
            @empty
                <div class="kv-row">
                    <input type="text" name="header_key[]" class="form-control form-control-sm"
                        placeholder="Authorization">
                    <input type="text" name="header_value[]" class="form-control form-control-sm"
                        placeholder="Bearer {token}">
                    <input type="text" name="header_note[]" class="form-control form-control-sm"
                        placeholder="{{ translate('note') }}">
                    <button type="button" class="btn btn-xs btn-outline-danger kv-remove"><i
                            class="tio-clear"></i></button>
                </div>
            @endforelse
        </div>
    </div>

    <div class="col-md-6 form-group">
        <label>{{ translate('Request Body') }}</label>
        <textarea name="request_body" rows="3" class="form-control form-control-sm api-mono">{{ $endpoint->request_body ?? '' }}</textarea>
    </div>
    <div class="col-md-6 form-group">
        <label>{{ translate('Response Sample') }}</label>
        <textarea name="response_sample" rows="3" class="form-control form-control-sm api-mono">{{ $endpoint->response_sample ?? '' }}</textarea>
    </div>

    <div class="col-md-8 form-group">
        <label>{{ translate('Note — where is this used?') }}</label>
        <textarea name="usage_note" rows="2" class="form-control form-control-sm"
            placeholder="{{ translate('e.g. User app home screen, on pull-to-refresh') }}">{{ $endpoint->usage_note ?? '' }}</textarea>
    </div>

    <div class="col-md-4 form-group">
        <label>{{ translate('Screenshots') }} <span class="api-help">({{ translate('optional') }})</span></label>
        <input type="file" name="images[]" class="form-control form-control-sm" multiple accept="image/*">

        @if ($endpoint && count($endpoint->image_list))
            <div class="d-flex flex-wrap mt-2" style="gap:6px;">
                @foreach ($endpoint->image_list as $img)
                    <a href="{{ asset('storage/app/public/api_endpoints/' . $img['stored_name']) }}" target="_blank"
                        rel="noopener">
                        <img src="{{ asset('storage/app/public/api_endpoints/' . $img['stored_name']) }}"
                            class="api-shot-sm" alt="{{ $img['file_name'] ?? '' }}">
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
