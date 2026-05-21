 <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table table-striped">
     <thead class="bg-white">
         <tr>
             <th>S No</th>
             <th>Project Manager</th>
             <th>Title</th>
             <th>Progress</th>
             <th>Deadline</th>
             <th>Priority</th>
             <th>Status</th>
             <th>Action</th>
         </tr>
     </thead>
     <tbody id="activityTableBody">
         @foreach ($projects as $key => $value)
             <tr>
                 <td>
                     <div class="sno-cell">
                         <span class="sno-indicator"></span>
                         {{ $key + 1 }}
                     </div>
                 </td>
                 <td>{{ $value->projectManager?->f_name }}</td>
                 <td>
                     <a class="media align-items-center"
                         href="{{ $value->id ? route('vendor.project.details', [$value->id]) : 'javascript:;' }}">

                         <div title="{{ $value->title }}" class="media-body">
                             <h5 class="text-hover-primary mb-0">
                                 {{ Str::limit($value->project_title, 20, '...') ?? '' }}
                             </h5>
                         </div>
                     </a>
                 </td>
                 <td class="">
                     {{ $value->prog_percent }}%
                 </td>
                 <td class="">
                     {{ $value->end_date }}
                 </td>
                 <td>
                 @if ($value->priority == 'low')
                     <span class="status-badge status-new">{{ $value->priority }}</span>
                 @elseif($value->status == 'high')
                     <span class="status-badge status-cancelled">{{ $value->priority }}</span>
                 @else
                     <span class="status-badge status-pending">{{ $value->priority }}</span>
                 @endif
                 </td>

                 <td>
                     @if ($value->progress_status == 'Completed')
                         <span class="status-badge status-completed">{{ $value->progress_status }}</span>
                     @elseif($value->progress_status == 'New')
                         <span class="status-badge status-new">{{ $value->progress_status }}</span>
                     @elseif($value->progress_status == 'Cancelled')
                         <span class="status-badge status-cancelled">{{ $value->progress_status }}</span>
                     @else
                         <span class="status-badge status-pending">{{ $value->progress_status }}</span>
                     @endif
                 </td>
                 <td>
                     <div class="btn--container justify-content-center">
                         <a href='{{ route('vendor.project.details', [$value->id]) }}'
                             class="btn action-btn btn--warning btn-outline-warning "
                             title="{{ translate('messages.view') }}"><i class="tio-visible"></i>
                         </a>
                     </div>
                 </td>
             </tr>
         @endforeach

     </tbody>
 </table>
