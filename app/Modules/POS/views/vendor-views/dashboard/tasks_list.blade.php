 <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table  table-striped">
     <thead class="bg-white">
         <tr>
             <th>S No</th>
             <th>Title</th>
             <th>Progress</th>
             <th>Duration</th>
             <th>Status</th>
             <th>Action</th>
         </tr>
     </thead>
     <tbody id="activityTableBody">
         @foreach ($tasks as $key => $value)
         <tr>
             <td>
                 <div class="sno-cell">
                     <span class="sno-indicator"></span>
                     {{ $key + 1 }}
                 </div>
             </td>
             <td>
                 <a class="media align-items-center"
                     href="{{ $value->id ?  route('vendor.task.detail', [$value->id]) : 'javascript:;' }}">
                     
                     <div title="{{ $value->title }}" class="media-body">
                         <h5 class="text-hover-primary mb-0">
                             {{ Str::limit($value->title, 20, '...')  ?? ''}}
                         </h5>
                     </div>
                 </a>
             </td>
             <td class="">
             {{$value->progress}}%

             
             </td>
             <td class="">
             {{$value->time_count}} {{$value->time_unit}}

             
             </td>

             <td>
                 @if ($value->status == 'Completed')
                     <span class="status-badge status-completed">{{ $value->status }}</span>
                 @elseif($value->status == 'New')
                     <span class="status-badge status-new">{{ $value->status }}</span>
                 @elseif($value->status == 'Cancelled')
                     <span class="status-badge status-cancelled">{{ $value->status }}</span>
                 @else
                     <span class="status-badge status-pending">{{ $value->status }}</span>
                 @endif
             </td>
             <td>
                 <div class="btn--container ">
                     <a href='{{route('vendor.task.detail', [$value->id]) }}' class="btn action-btn btn--warning btn-outline-warning "
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
