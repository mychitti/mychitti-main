  <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
      <div class="container tab_inner">

          <div class="af-container-p9x2">
              <h2>User profile</h2>
              <p class="af-subtitle-p9x2">Manage your details, keep your profile up to date and control your
                  account settings.</p>

              <div class="af-profile-box-p9x2">
                  <div class="af-avatar-section-p9x2">
                      <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($user_details->image, asset('storage/app/public/profile/') . '/' . $user_details->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                          class="af-avatar-img-p9x2" alt="Avatar" />
                      <h3>{{ ucwords($user_details->f_name . ' ' .  $user_details->l_name) }}</h3>
                      <p class="af-phone-p9x2">{{ $user_details->phone }}</p>
                  </div>    

                  <div class="af-info-section-p9x2">
                      <h4>General information</h4>
                      <div class="af-input-group-p9x2">
                          <div>
                              <label>First name</label>
                              {{ $user_details->f_name }}
                          </div>
                          <div>
                              <label>Last name</label>
                              {{ $user_details->l_name }}
                          </div>
                      </div>
                  </div>
              </div>

              <div class="af-security-box-p9x2">
                  <h4>Contact Information</h4>
                  <div class="af-security-inputs-p9x2">
                      <div>
                          <label>Email</label>
                          {{ $user_details->email }}
                      </div>
                      <div> 
                          <label>Phone number</label>
                          {{ $user_details->phone }}
                      </div>
                      <div>
                          <label>GST number</label>
                          {{ $user_details->gst }}
                      </div>
                  </div>

              </div>
              <div class="af-btn-row-p9x2 mt-2">
                  <button class="af-action-btn-p9x2" data-bs-toggle="collapse" href="#collapseExample" role="button"
                      aria-expanded="false" aria-controls="collapseExample"> <i class="fa fa-edit"></i> Edit
                      Information</button>
              </div>
          </div>

          <div class="collapse" id="collapseExample">
              <div class="card card-body">
                  <form class="formSubmit profile_form" action="{{ route('update-profile') }}" method="post">
                      @csrf
                      <div class="row ">
                          <div class="col-md-12  row">
                              <div class="row col-lg-8">
                                  <div class="col-md-12 col-lg-12">
                                      <div class="form-item w-100">
                                          <label class="form-label my-3">First Name<sup>*</sup></label>
                                          <input type="text" name="f_name" required
                                              value="{{ $user_details->f_name }}" class="form-control">
                                      </div>
                                  </div>
                                  <div class="col-md-12 col-lg-12">
                                      <div class="form-item w-100">
                                          <label class="form-label my-3">Last Name<sup>*</sup></label>
                                          <input type="text" name="l_name" required
                                              value="{{ $user_details->l_name }}" class="form-control">
                                      </div>
                                  </div>

                                  <div class="col-md-12 col-lg-12">
                                      <div class="form-item w-100">
                                          <label class="form-label my-3">Email<sup>*</sup></label>
                                          <input type="text" name="email" required
                                              value="{{ $user_details->email }}" class="form-control">
                                      </div>
                                  </div>
                                    <div class="col-md-12 col-lg-12">
                                      <div class="form-item w-100">
                                          <label class="form-label my-3">GST Number <i>(Optional)</i></label>
                                          <input value="{{ $user_details->gst }}" type="text" name="gst_number"
                                              class="form-control">
                                      </div>
                                  </div>
                                  <div class="col-md-12 col-lg-12">
                                      <div class="form-item w-100">
                                          <label class="form-label my-3">Mobile</label>
                                          <input disabled value="{{ $user_details->phone }}" type="text"
                                              class="form-control">
                                      </div>
                                  </div>
                                
                              </div>
                              <div class="rounded p-2 col-lg-4 d-flex flex-column position-relative align-items-center"
                                  style="height: fit-content;">
                                  <img class="rounded" style="width: 200px; height:200px;margin-bottom:8px;"
                                      src="{{ \App\CentralLogics\Helpers::onerror_image_helper($user_details->image, asset('storage/app/public/profile/') . '/' . $user_details->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/') }}"
                                      alt="profile">
                                  <input type="file" name="image" style="width: 200px;"
                                      class="form-control bg-white">
                              </div>
                              <div class="mt-4 d-flex justify-content-between">
                                  <button type="submit" class="btn border-secondary text-primary">Update</button>
                                  <button type="button" id="deleteAccount" class="btn btn-outline-danger btn-sm">Delete
                                      Account</button>
                              </div>
                          </div>
                      </div>
                  </form>
              </div>
          </div>



      </div>
  </div>
