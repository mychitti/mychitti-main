  
  
  {{-- @foreach ($service_categories2 as $key => $ct)
      @if ($key < 5)
          <li class="submenu-list__item has-submenu active">
              <div class="submenu-list__item-wrapper">
                  <div class="submenu-list__item-icon">
                      <img  loading="lazy" style="width:50px"
                          data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                          src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ct['image'], asset('storage/app/public/category/') . '/' . $ct['image'], asset('public/assets/admin/img/160x160/img1.jpg'), 'category/') }}"
                          alt="First slide">
                  </div>
                  <a href="#" class="submenu-list__item-link text-dark">
                      <span class="submenu-list__item-title">{{ $ct['name'] }}</span>
                      <span class="submenu-list__item-subtile">{{ $ct['items_count'] }} services in this
                          category -></span>
                  </a>

              </div>
              <div class="submenu-content">
                  <div class="submenu-content__title"><span>{{ $ct['name'] }}</span><a href="{{route("category.listing", [$ct["slug"]])}}" class="p-2">View All</a></div>
                  <ul class="submenu-content__list events">
                      @foreach ($ct['cat_items'] as $key2 => $ctitem)
                          <li class="submenu-content__list-item">
                              <div class="submenu-content__link-wrapper">
                                  <div class="submenu-content__link-img">
                                      <img loading="lazy" style="height: 160px; width:160px; aspect-ratio:1/1; object-fit:cover;"
                                          data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                          src="{{ \App\CentralLogics\Helpers::onerror_image_helper($ctitem->image, asset('storage/app/public/product/') . '/' . $ctitem->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'product/') }}"
                                          alt="{{ $ctitem->name }}">
                                  </div>
                                  <div class="submenu-content__info">

                                      <div class="submenu-content__link-title">
                                          <a href="{{ route('product.details', [$ctitem->cat_slug, $ctitem->slug]) }}">
                                              {{ $ctitem->name }}</a>
                                      </div>
                                      <div class="submenu-content__link-text">{{ $ctitem->store_count }} Providers in
                                          {{ $ctitem->zone_name }}</div>
                                      <div class="submenu-content__link-address">

                                          <a href="{{ route('product.details', [$ctitem->cat_slug, $ctitem->slug]) }}">
                                              <span>Explore</span></a>
                                      </div>

                                      <a href="#" class="submenu-content__url">
                                          <span>Explore More</span>
                                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="14"
                                              viewBox="0 0 16 14" fill="none">
                                              <path
                                                  d="M0.5 6.99996H15.5M15.5 6.99996L9.66667 1.16663M15.5 6.99996L9.66667 12.8333"
                                                  stroke="white" stroke-linecap="round" stroke-linejoin="round" />
                                          </svg>
                                      </a>
                                  </div>
                              </div>
                          </li>
                      @endforeach

                  </ul>
              </div>
          </li>
      @endif
  @endforeach --}}
