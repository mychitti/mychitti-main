{{-- Which language the patient's copy prints in.

     The list is whatever the hospital ticked under Hospital Settings → Prescription Languages, so
     a clinic that writes English and Telugu sees two options, not twenty-three. English is always
     present — it is what anything untranslated falls back to.

     Hidden entirely when the hospital has enabled nothing beyond English: a dropdown with one
     option is noise on a screen a doctor uses forty times a day.

     $selected  the prescription's stored language (null on a new one) --}}
@php
    $rxLangs    = \App\Models\Prescription::enabledLanguages(\App\CentralLogics\Helpers::get_store_id());
    $rxSelected = old('language', $selected ?? 'en');
@endphp

@if (count($rxLangs) > 1)
    <div class="rx-lang">
        <label>Print in</label>
        <select name="language" class="form-control">
            @foreach ($rxLangs as $code => $label)
                <option value="{{ $code }}" {{ $rxSelected === $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
@else
    <input type="hidden" name="language" value="en">
@endif
