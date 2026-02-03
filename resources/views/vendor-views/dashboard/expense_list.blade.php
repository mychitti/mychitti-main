
 <table class="table table-striped">
     <thead>
         <tr>
             <th>S No</th>
             <th>Date</th>
             <th>Description</th>
             <th>Type</th>
             <th>Amount</th>
             <th>Status</th>
         </tr>
     </thead>
     <tbody id="activityTableBodyexp">
@foreach($expenses as $key => $exp)

         <tr>
             <td>
                 <div class="sno-cell">
                     <span class="sno-indicator"></span>
                     {{$key + 1}}
                 </div>
             </td>
             <td>{{$exp->voucher_date}}</td>
             <td>{{$exp->narration}}</td>
             <td class="type-expense">{{$exp->voucher_type}}</td>
             <td>{{_price($exp->total_amount)}}</td>
             <td>
             @if($exp->status == 'approved')
             <span class="status-badge status-completed">{{$exp->status}}</span>
             @else
             <span class="status-badge status-pending">{{$exp->status}}</span>
             @endif
             </td>
         </tr>
@endforeach
      
     </tbody>
 </table>
