<div class="modal fade " id="searchbarModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                @php($store_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
                <img style="width:150px" class="navbar-brand-logo initial--36 onerror-image onerror-image d-none d-sm-block "
                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                    src="{{ \App\CentralLogics\Helpers::onerror_image_helper($store_logo, asset('storage/app/public/business/') . '/' . $store_logo, asset('public/assets/admin/img/160x160/img1.jpg'), 'business/') }}"
                    alt="Logo">
                <div class="position-relative search_inp_grp">
                    <input onkeyup="searchBar('autocomplete3', this)" id="mainSearchbar"
                        class="form-control border-2 border-secondary  py-2 px-4 rounded-pill new_searchbar"
                        placeholder="Search"  autocomplete="off">
                    <div id="autocomplete3" style="z-index: 2;" class="w-100 border bg-white position-absolute">

                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body container">
                <h4 class="recent_search_title" style="display:none">Recent Searches </h4>
                <div id="recent-searches" class="recent-search-chips">
                    <!-- Pills will be injected here -->
                </div>
                <h4 class="mt-5">Trending Now</h4>
                <div class="recent-search-chips">
                {!! _topsearched()!!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-grey" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
