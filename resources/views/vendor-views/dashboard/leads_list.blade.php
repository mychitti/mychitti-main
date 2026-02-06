 <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table  table-striped">
     <thead class="bg-white">
         <tr>
             <th>S No</th>
             <th>Date</th>
             <th>Service</th>
             <th>User</th>
             <th>Status</th>
             <th>Action</th>
         </tr>
     </thead>
     <tbody id="activityTableBody">
         @foreach ($data['leads'] as $key => $lead)
         <tr>
             <td>
                 <div class="sno-cell">
                     <span class="sno-indicator"></span>
                     {{ $key + 1 }}
                 </div>
             </td>
             <td>{{$lead->created_at}}</td> 
             <td>
                 <a class="media align-items-center"
                     href="{{ $lead->serviceRequest?->item ?  route('vendor.item.view', [$lead->serviceRequest?->item?->id]) : 'javascript:;' }}">
                     <img class="avatar avatar-lg mr-3 onerror-image"
                         src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                             $lead->serviceRequest?->item?->image ?? '',
                             asset('storage/app/public/product') . '/' . $lead->serviceRequest?->item?->image ?? '',
                             asset('public/assets/admin/img/160x160/img2.jpg'),
                             'product/',
                         ) }}"
                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                         alt="{{ $lead->serviceRequest?->item?->name }} image">
                     <div title="{{ $lead->serviceRequest?->item?->name }}" class="media-body">
                         <h5 class="text-hover-primary mb-0">
                             {{ Str::limit($lead->serviceRequest?->item?->name, 20, '...')  ?? 'Deleted'}}
                         </h5>
                     </div>
                 </a>
             </td>
             <td class="">

                 @php
                     $user = $lead->serviceRequest?->user;
                 @endphp
                 @if ($user)
                     <b>{{ $user->f_name . ' ' . $user->l_name }}</b> <br> <i class="tio-call"></i>{{ $user->phone }}
                 @endif
             </td>

             <td>
                 @if ($lead->current_status == 'Completed')
                     <span class="status-badge status-completed">{{ $lead->current_status }}</span>
                 @elseif($lead->current_status == 'Cancelled')
                     <span class="status-badge status-cancelled">{{ $lead->current_status }}</span>
                 @else
                     <span class="status-badge status-pending">{{ $lead->current_status }}</span>
                 @endif
             </td>
             <td>
                 <div class="btn--container justify-content-center">
                     <a href='{{route('vendor.service.lead-details', [$lead->service_request_id]) }}' class="btn action-btn btn--warning btn-outline-warning "
                         title="{{ translate('messages.view') }}"><i class="tio-visible"></i>
                     </a>
                 </div>
             </td>
         </tr>
         @endforeach

         {{-- <tr>
             <td>
                 <div class="sno-cell">
                     <span class="sno-indicator"></span>
                     2
                 </div>
             </td>
             <td>02-10-2025</td>
             <td>Sale</td>
             <td class="type-income">Income</td>
             <td>₹5,000</td>
             <td><span class="status-badge status-pending">Pending</span></td>
         </tr> --}}
     </tbody>
 </table>
