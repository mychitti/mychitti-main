@foreach ($ads as $ad)
<a href="{{ route('front.ads.detail', $ad->id) }}" class="ad-card spotlight-ad-card" data-ad-id="{{ $ad->id }}">
    <div class="ad-card__img">
        <img loading="lazy"
            src="{{ asset('storage/app/public/notification') . '/' . $ad->image }}"
            onerror="this.src='{{ asset('assets/admin/img/160x160/img1.jpg') }}'"
            alt="{{ $ad->title }}">
    </div>
    <div class="ad-card__body">
        <p class="ad-card__title text-truncate">{{ $ad->title }}</p>
        @if($ad->description)
        <p class="ad-card__desc two-line-ellipsis">{{ $ad->description }}</p>
        @endif
    </div>
</a>
@endforeach
