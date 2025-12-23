  <!-- Table -->
  <div class="table-responsive datatable-custom">
      <table id="columnSearchDatatable"
          class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
          data-hs-datatables-options='{
                        "order": [],
                        "orderCellsTop": true,
                        "paging":false

                    }'>
          <thead class="thead-light">
              <tr>
                  <th class="border-0">{{ translate('sl') }}</th>
                  <th class="border-0">Id</th>
                  <th class="border-0">Service</th>
                  <th class="border-0">User</th>
                  <th class="border-0">Category</th>
                  <th class="border-0">Requested At</th>
                  <th class="border-0">Status</th>
                  <th class="text-uppercase border-0">Action</th>
              </tr>
          </thead>

          <tbody id="set-rows">
              @foreach ($product as $key => $lead)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $lead->id }}</td>

                  <td>
                      <a class="media align-items-center" href="javascript:;" style="cursor:default;">
                          <img class="avatar avatar-lg mr-3 onerror-image"
                              src="{{ \App\CentralLogics\Helpers::onerror_image_helper($lead->image, asset('storage/app/public/product/') . '/' . $lead->image, asset('public/assets/admin/img/160x160/img2.jpg'), 'product/') }}"
                              data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                              alt="{{ $lead->item_name }} image">
                          <div class="media-body">
                              <h5 class="text-hover-primary mb-0">
                                  {{ Str::limit($lead->item_name, 20, '...') }}
                              </h5>
                          </div>
                      </a>
                  </td>
                  <td>
                      <span class="d-block font-size-sm text-body">

                          {{ $lead->f_name }}

                      </span>

                  </td>
                  <td>
                      <span class="d-block font-size-sm text-body">
                          {{ $lead->category_name }}
                      </span>

                  </td>
                  <td>
                      <div>
                          {{ $lead->created_at }}
                      </div>
                  </td>
                  <td>
                      @if (isset($lead->additional_status) && $lead->additional_status == 'missed')
                      <b class="text-danger"> Missed </b> <i class="tio-info-outined"
                          data-toggle="tooltip" data-placement="left" data-html="true"
                          title="<b class='text-danger' >You missed this lead.</b><br> Leads are available for maximum {{ \App\CentralLogics\Helpers::get_lead_exp_time() }}"></i>
                      @else
                      @if ($lead->current_status == 'Confirmed')
                      @if ($lead->assigned_status == 'Unassigned')
                      <a style="width:fit-content; padding: 2px 8px !important" type="button"
                          data-toggle="modal" data-target="#assignModal{{ $key }}"
                          class="btn action-btn btn--danger btn-outline-danger"
                          title="{{ translate('messages.view') }}">Unassigned
                      </a>
                      @else
                      <a style="width:fit-content; padding: 2px 8px !important" type="button"
                          data-toggle="modal" data-target="#assignModal{{ $key }}"
                          class="btn action-btn btn--primary btn-outline-primary"
                          title="{{ translate('messages.view') }}">Assigned
                          {{ $lead->assigned_type == 'vendor' ? ' to self' : '' }}
                      </a>
                      @if ($lead->assigned_type == 'staff' && isset($lead->assigned_to))
                      <a style="width:fit-content; padding: 2px 8px !important "
                          href="{{ route('vendor.track-location', [$lead->assigned_to]) }}"
                          target="_blank"
                          class="btn action-btn btn--primary btn-outline-primary mt-1"
                          title="{{ translate('messages.view') }}">Track Location
                      </a>
                      @endif
                      @endif
                      @else
                      {{ $lead->current_status == 'Confirmation Request Sent' ? $lead->current_status : $lead->current_status }}
                      @endif
                      @endif

                  </td>


                  @php
                  $status = $lead->current_status;
                  $invoiceStatus = _serviceInvoiceStatus($lead->id);
                  $isCompleted = $status === 'Completed';
                  $isConfirmed = $status === 'Confirmed';
                  $isCancelled = str_starts_with($status, 'Cancelled');
                  $canViewDetails = $isConfirmed || $isCancelled || $isCompleted;
                  $isAcceptedReq = _acceptedReq($lead->id);
                  $canAccept =
                  !isset($lead->additional_status) || $lead->additional_status !== 'missed';
                  $currentServiceStatus = _getCurrentServiceStatus($lead->id);
                  @endphp

                  <td class="text-center">
                      <div class="dropdown">
                          <button class="btn p-1 dropdown-toggle" type="button" data-toggle="dropdown"
                              aria-expanded="false">
                              <img style="width: 24px; filter: contrast(0)"
                                  src="{{ asset('storage/app/public/util/10025520.png') }}"
                                  alt="action" />
                          </button>
                          <div class="dropdown-menu">

                              @if ($isCompleted)
                              @if (in_array($invoiceStatus, ['new', 'editable']))
                              <a href="{{ route('vendor.business-settings.generate-bill', [$lead->id]) }}"
                                  class="dropdown-item {{ $invoiceStatus === 'new' ? 'text-primary' : 'text-danger' }}"
                                  title="Bill Management">
                                  <i class="fas fa-file-invoice"></i>
                                  {{ $invoiceStatus === 'new' ? 'Generate' : 'Edit' }} Bill
                              </a>
                              @else
                              <a target="_blank"
                                  href="{{ asset('storage/app/public/invoice/' . $invoiceStatus) }}"
                                  class="dropdown-item text-success" title="View Bill">
                                  <i class="tio-document-outlined"></i>
                                  View Bill
                              </a>
                              @endif
                              @endif

                              @if ($canViewDetails)
                              <a href="{{ route('vendor.service.lead-details', [$lead->id]) }}"
                                  class="dropdown-item text-warning" title="View Details">
                                  <i class="tio-visible-outlined"></i>
                                  View Details
                              </a>
                              @endif

                              @if ($isConfirmed)
                              <a onclick="cancelLead({{ $lead->id }}, {{ $lead->acc_id }})"
                                  class="dropdown-item text-danger" title="Cancel Lead"
                                  style="cursor: pointer;">
                                  <i class="fas fa-times"></i>
                                  Cancel
                              </a>
                              <a href="{{route('vendor.lead.convert-to-task', [$lead->id])}}"
                                  class="dropdown-item text-primary" title="Convert to Task"
                                  style="cursor: pointer;">
                                  <i class="tio-image-rotate-right"></i>
                                  Convert to Task
                              </a>
                              <a href="{{route('vendor.lead.convert-to-order', [$lead->id])}}"
                                  class="dropdown-item text-primary" title="Convert to Order"
                                  style="cursor: pointer;">
                                  <i class="tio-image-rotate-right"></i>
                                  Convert to Order
                              </a>
                              @endif

                              @if (!$canViewDetails)
                              @if ($isAcceptedReq)
                              <a href="#" class="dropdown-item text-primary"
                                  data-toggle="modal"
                                  data-target="#exampleModal33-{{ $lead->id }}"
                                  title="Contact Details">
                                  <i class="fas fa-user"></i>
                                  User Details
                              </a>
                              @elseif ($canAccept)
                              @if ($currentServiceStatus && $currentServiceStatus === 'Confirmation Request Sent')
                              <span class="dropdown-item text-muted">
                                  <i class="fas fa-clock"></i>
                                  {{ $currentServiceStatus }}
                              </span>
                              @endif
                              <a href="{{ route('vendor.service.accept', [$lead->id]) }}"
                                  class="dropdown-item text-success" title="Accept Request">
                                  <i class="fas fa-check"></i>
                                  Accept
                              </a>
                              @endif
                              @endif

                          </div>
                      </div>
                  </td>

                  <div class="modal fade" id="assignModal{{ $key }}" tabindex="-1"
                      role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                      <div class="modal-dialog" role="document">
                          <div class="modal-content">
                              <div class="indicator"></div>
                              <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLabel">Assign Staff to Service
                                  </h5>
                                  <button type="button" class="close" data-dismiss="modal"
                                      aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                  </button>
                              </div>
                              <div class="modal-body">
                                  @if ($lead->assigned_status == 'Unassigned' || $lead->assigned_type == 'vendor')
                                  @if ($lead->assigned_type == 'vendor')
                                  <b>Assigned To : </b>
                                  Self (Vendor)
                                  @endif
                                  <form action="{{ route('vendor.service.save-assignment') }}"
                                      method="post">
                                      @csrf
                                      <input type="hidden" name="service_id"
                                          value="{{ $lead->id }}" hidden>
                                      <input type="hidden" name="id"
                                          value="{{ $lead->acc_id }}" hidden>

                                      <div class="form-group">
                                          <div class="custom-file">
                                              <label class="form-label" for="staff_id">{{$lead->assigned_status == 'assigned' ? 'Reassign' : 'Assign'}}
                                                  To</label><br>

                                              <select name="staff_id" id="staff_id"
                                                  class="js-select2-custom form-control">
                                                  <option></option>
                                                  <option value="vendor">Self (Vendor)</option>
                                                  @foreach ($allStaff as $staff)
                                                  <option value="{{ $staff->id }}">
                                                      {{ $staff->f_name . ' ' . $staff->l_name }}
                                                  </option>
                                                  @endforeach
                                              </select>
                                          </div>
                                      </div>
                                      <div class="form-group mb-0">
                                          <input class="btn btn--primary text-white" type="submit"
                                              value="Assign">
                                      </div>
                                  </form>
                                  @else
                                  <span style="font-size: 17px">
                                      <b>Assigned To : </b>
                                      @if ($lead->assigned_type == 'vendor')
                                      Self
                                      @else
                                      @php
                                      $empInfo = _getWhereOne('vendor_employees', [
                                      'id' => $lead->assigned_to,
                                      ]);
                                      @endphp

                                      @if ($empInfo)
                                      <span>
                                          {{ $empInfo->f_name . ' ' . $empInfo->l_name . ' #' . $lead->assigned_to }}
                                          ({{ !$lead->accepted_by_staff ? 'Acceptance Pending' : ($lead->accepted_by_staff == 2 ? 'Rejected' : 'Accepted') }})
                                      </span>
                                      @endif
                                      @endif
                                      @endif
                              </div>
                          </div>
                      </div>
                  </div>


                  @php
                  $user_details = _getUserDetails($lead->uid);

                  @endphp
                  @if ($user_details)
                  <!--modal -->
                  <div class="modal fade" id="exampleModal33-{{ $lead->id }}" tabindex="-1"
                      role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                      <div class="modal-dialog" role="document">
                          <div class="modal-content">
                              <div class="modal-header">
                                  <h5 class="modal-title" id="exampleModalLabel">
                                      Requested
                                      for : {{ $lead->item_name }} </h5>
                                  <button type="button" class="close" data-dismiss="modal"
                                      aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                  </button>

                              </div>
                              <div class="modal-body">
                                  <div class="form-group">


                                      <ul class="list-unstyled">

                                          <li>
                                              <strong>Name:</strong>
                                              <span>{{ $user_details->f_name . ' ' . $user_details->l_name }}</span>
                                          </li>

                                          <li>
                                              <strong>Email:</strong>
                                              <a
                                                  href="mailto:{{ $user_details->email }}">{{ $user_details->email }}</a>
                                          </li>

                                          <li>
                                              <strong>Mobile:</strong>
                                              <a href="javascript:;" style="cursor:default;"
                                                  class="textToCopy">{{ $user_details->phone }}</a>
                                              <button
                                                  class="copy-btn bg-transparent outline-none border-0">
                                                  <i class="tio-copy"></i>
                                              </button>
                                          </li>

                                          <li>
                                              @if (!_getCurrentServiceStatus($lead->id))
                                              <form
                                                  action="{{ route('vendor.service.send-confirmation-notification', ['id' => $lead->id]) }}">
                                                  @csrf
                                                  <input type="hidden" name="id"
                                                      value="{{ $lead->id }}">
                                                  <label for="lead_price"
                                                      class="form-label">Visiting
                                                      Charges</label>
                                                  <input type="number" name="price"
                                                      id="lead_price" class="form-control mb-1"
                                                      placeholder="Visiting Charges">
                                                  <button type="submit"
                                                      class="btn btn--primary">Send
                                                      Confirmation Request</button>
                                              </form>
                                              @else
                                              <h4 class="text--primary">
                                                  {{ _getCurrentServiceStatus($lead->id) }}
                                              </h4>
                                              @endif

                                              @if (_getCurrentServiceStatus($lead->id) == 'Confirmed')
                                              <a href="{{ route('vendor.service.cancel', [$lead->id]) }}"
                                                  class="btn btn-outline-danger">Cancel</a>
                                              @endif
                                          </li>

                                      </ul>

                                  </div>
                              </div>
                              <div class="modal-footer">
                                  <button id="reset_btn" type="reset" data-dismiss="modal"
                                      class="btn btn-secondary">{{ translate('Close') }}
                                  </button>
                              </div>
                          </div>
                      </div>
                  </div>
                  @endif
              </tr>

              @endforeach
          </tbody>
      </table>
      @if (count($product))
      <hr>
      @else
      <div class="page-area">
      </div>
      <div class="empty--data">
          <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
          <h5>
              {{ translate('no_data_found') }}
          </h5>
      </div>
      @endif
  </div>