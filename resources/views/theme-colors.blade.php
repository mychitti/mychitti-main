  @php($primary_color = \App\Models\BusinessSetting::where('key', 'primary_color')->first())
  @php($secondary_color = \App\Models\BusinessSetting::where('key', 'secondary_color')->first())
  @php($primary_btn_hover = \App\Models\BusinessSetting::where('key', 'primary_btn_hover')->first())
  <style>
      :root {
          --primary-clr: {{ $primary_color ? $primary_color->value : '#754BFF' }};
          --primary: {{ $primary_color ? $primary_color->value : '#754BFF' }};
          --primary-light-theme: {{ $secondary_color ? $secondary_color->value : '#A099FF' }};
          --primary-dark: {{ $primary_btn_hover ? $primary_btn_hover->value : '#6e44fa' }};
      }
  </style>
