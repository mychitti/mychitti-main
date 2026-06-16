@php $dashUser = auth('web')->user(); @endphp
<div class="left_nav nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
    <img class="profile_img"
        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($dashUser->image ?? null, asset('storage/app/public/profile/') . '/' . ($dashUser->image ?? ''), asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
        alt="profile">
    <a href="{{ route('dashboard', ['profile']) }}"
        class="text-center nav-link {{ Request::is('dashboard/profile') || Request::is('dashboard') ? 'active' : '' }}">Profile</a>
    <a href="{{ route('dashboard', ['address']) }}"
        class="text-center nav-link {{ Request::is('dashboard/address') ? 'active' : '' }}">Address</a>
    <a href="{{ route('dashboard', ['bookings']) }}"
        class="text-center nav-link {{ Request::is('dashboard/bookings') ? 'active' : '' }}">Bookings</a>
    <a href="{{ route('dashboard', ['coupons']) }}"
        class="text-center nav-link {{ Request::is('dashboard/coupons') ? 'active' : '' }}">Coupons</a>
    <a href="{{ route('dashboard', ['favourites']) }}"
        class="text-center nav-link {{ Request::is('dashboard/favourites') ? 'active' : '' }}">Favourites</a>
    <a href="{{ route('school.portal.index') }}"
        class="text-center nav-link {{ Request::is('my-school*') ? 'active' : '' }}">My School</a>
    <button class="nav-link" type="button" data-bs-toggle="modal" data-bs-target="#dashLogoutModal">Logout</button>
</div>

<div class="modal fade" id="dashLogoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p class="f-4">Are you sure you want to logout?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('user.logout') }}" class="btn btn-primary">Yes</a>
            </div>
        </div>
    </div>
</div>
