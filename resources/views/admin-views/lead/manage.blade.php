@extends('layouts.admin.app')

@section('title',translate('Lead List'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  .cardTop {

    background: #FAC24F;
    border-radius: 20px;
    display: flex;
    overflow: hidden;
    align-items: flex-end;

  }


  .timeline {

    width: 100%;
    background: #ECF1F524;
    mix-blend-mode: normal;
    backdrop-filter: blur(15px);

    overflow: hidden;

    border-radius: 10px;




    label {

      font-family: Open Sans;
      font-style: normal;
      font-weight: normal;
      font-size: 16px;
      line-height: 22px;
      /* identical to box height */
      margin-left: 66px;
      margin-top: 10px;

      color: #FFFFFF;

    }

    .box {
      width: 100%;
      background: #fbfcfd;


      .container {

        width: 100%;
        display: flex;

        .lines {
          margin-left: 40px;
          margin-top: 6px;


          .dot {
            width: 14px;
            height: 14px;
            background: #D1D6E6;
            border-radius: 7px;
          }

          .line {
            height: 103px;
            width: 2px;
            background: #D1D6E6;
            margin-left: 5.3px;
          }
        }

        .cards {

          margin-left: 12px;

          .card {
            padding-top: 25px;
            background: #FFFFFF;
            box-shadow: 0 2px 2px 0 #eeeeee40;
            border-radius: 10px;

            box-shadow: 0px 16px 15px -10px rgba(105, 96, 215, 0.0944602);
            margin-bottom: 10px;

            &.mid {

              height: 71px;
            }

            h4 {

              font-family: Open Sans;
              font-style: normal;
              font-weight: bold;
              font-size: 14px;
              line-height: 19px;
              margin-left: 25px;
              margin-bottom: 5px;




              color: #2B2862;

            }

            p {

              font-family: Open Sans;
              font-style: normal;
              font-weight: normal;
              font-size: 16px;
              line-height: 22px;

              color: #2B2862;
              margin-left: 25px;
            }
          }
        }

      }


    }
  }
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  crossorigin="anonymous"></script>
<script>
  $(document).on('click', '.lead_approval', function() {
    console.log('fds');
    var status = $(this).attr('data-id');
    $.ajax({
      url: '{{ url('
      admin / lead / lead_approval ') }}',
      type: "POST",
      data: {
        _token: $('[name="_token"]').val(),
        lead_id: $('#lead_id').val(),
        approval: status
      },
      success: function(resp) {
        if (resp.status) {
          if (status == 'accept') {
            $('.approval-status').html('<h3 class="text-success p-3">Accepted</h3>')
          } else {
            $('.approval-status').html('<h3 class="text-danger p-3">Rejected</h3>')
          }
        }

      },
    });
  })
</script>
@endpush

@section('content')
<div class="content container-fluid">
  <!-- Page Header -->
  <div class="page-header">
    <h1 class="page-header-title"><i class="tio-filter-list"></i> {{translate('messages.Vendor')}} <span class="badge badge-soft-dark ml-2" id="itemCount">4</span></h1>
    <div class="page-header-select-wrapper">

      {{-- <div class="select-item">
                    <select name="module_id" class="form-control js-select2-custom"
                            onchange="set_filter('{{url()->full()}}',this.value,'module_id')" title="{{translate('messages.select')}} {{translate('messages.modules')}}">
      <option value="" {{!request('module_id') ? 'selected':''}}>{{translate('messages.all')}} {{translate('messages.modules')}}</option>
      @foreach (\App\Models\Module::notParcel()->get() as $module)
      <option value="{{$module->id}}" {{request('module_id') == $module->id?'selected':''}}>
        {{$module['module_name']}}
      </option>
      @endforeach
      </select>
    </div> --}}

  </div>
</div>
<!-- End Page Header -->







@if (session()->has('msg'))
<div class="alert alert-success" role="alert">
  {{ session('msg') }}
</div>
@endif
<div class="row g-2">
  <div class="col-md-12">
    <div class="card h-100">
      <div class="row">
        <h4 class="m-3 col-7">Manage Lead</h4>
        <div class="col-4 row d-flex justify-content-end approval-status">
          @if($lead->approval == 'accept')
          <h3 class="text-success p-3">Accepted</h3>
          @elseif($lead->approval == 'reject')
          <h3 class="text-success p-3">Accepted</h3>
          @else
          <button type="button" data-id="accept" class="lead_approval btn mx-2 btn--primary btn-outline-primary">Accept</button>
          <button type="button" data-id="reject" class="lead_approval btn  btn--danger btn-outline-danger">Reject</button>
          @endif
        </div>

      </div>
      <div class="card-body">

        <form class="w-100" action="{{route('admin.lead.save-info')}}" method="post">
          @csrf
          <input type="hidden" id="lead_id" name="lead_id" value="{{$lead->id}}">
          <div class="row">
            <div class="form-row col-6">

              <div class="col">
                <label for="exampleInputEmail1">Follow Up Date</label>
                <input value="{{explode(' ', $lead->follow_up_date)[0]}}" name="follow-up-date" type="date" class="form-control">
              </div>
            </div>
            <div class="form-row col-6">

              <div class="col">
                <label for="inputState">Status</label>
                <select name="status" id="inputState" class="form-control">
                  <option value="New" {{ ($lead->status == 'New') ?'selected':''}}>New</option>
                  <option value="Completed" {{ ($lead->status == 'Completed') ?'selected':''}}>Completed</option>
                  <option value="Follow Ups" {{ ($lead->status == 'Follow Ups') ?'selected':''}}>Follow Ups</option>
                  <option value="Hold" {{ ($lead->status == 'Hold') ?'selected':''}}>Hold</option>
                  <option value="Not Interested" {{ ($lead->status == 'Not Interested') ?'selected':''}}>Not Interested</option>
                  <option value="Closed" {{ ($lead->status == 'Closed') ?'selected':''}}>Closed</option>
                </select>

              </div>
            </div>
       
          <div class="form-row col-6">

            <div class="col">
              <label for="exampleInputEmail1">Price</label>
              <input type="number" value="{{$lead->price}}" name="price" class="form-control">
            </div>
          </div>
          <div class="form-row col-6">

            <div class="col">
              <label for="exampleInputEmail1">City</label>
              <select class="form-control js-select2-custom" name="zone">
                <option value="">-- select city --</option>
                @foreach( $zones as $zone)
                <option {{$lead->zone == $zone->id ? 'selected':''}} value="{{$zone->id}}">{{$zone->name}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row col-6">

            <div class="col">
              <label for="exampleInputEmail1">Project Requirements</label>
              <textarea class="form-control" name="requirements" id="exampleFormControlTextarea1" rows="3">{{$lead->requirements}}</textarea>
            </div>
          </div>
          <div class="form-row col-6">

            <div class="col">
              <label for="exampleInputEmail1">Remarks</label>
              <textarea class="form-control" name="remarks" id="exampleFormControlTextarea1" rows="3">{{$lead->remarks}}</textarea>
            </div>
          </div>
      </div>
      <div class="form-row">

        <div class="col my-2">
          <button class="btn  btn--primary btn-outline-primary">Save</button>
        </div>
      </div>

      </form>

    </div>
  </div>
</div>

<div class="col-md-6">
  <div class="card h-100">
    <h4 class="m-3">Client Details</h4>
    <div class="card-body">

      <table class="table ">

        <tbody>
          <tr>
            <th scope="row">Client Name</th>
            <td>{{$lead->client_name}}</td>
          </tr>
          <tr>
            <th scope="row">Email</th>
            <td>{{$lead->client_email}}</td>
          </tr>
          <tr>
            <th scope="row">Mobile</th>
            <td>{{$lead->client_mobile}}</td>
          </tr>
          <tr>
            <th scope="row">Channel</th>
            <td>{{$lead->channel}}</td>
          </tr>
          <tr>
            <th scope="row">Service</th>
            <td>{{$serviceName}}</td>
          </tr>
          <tr>
            <th scope="row">Message</th>
            <td>{{$lead->client_message}}</td>
          </tr>
          <tr>
            <th scope="row">Lead Date</th>
            <td>{{$lead->lead_date}}</td>
          </tr>

        </tbody>
      </table>

    </div>
  </div>
</div>
<div class="col-md-6">
  <div class="card h-100">
    <h4 class="m-3">Previous Interactions</h4>
    <div class="card-body d-flex flex-wrap">
      <div class="timeline">


        <div class="box">
          <div class="container">


            <div class="cards w-100">
              @if(count($previous_leads)-1)
              @foreach($previous_leads as $cl)
              @if($cl->id != $lead->id)
              <a href="{{ route('admin.lead.manage', [$cl->id]) }}">
                <div class="card">

                  <h4>{{$cl->lead_date}}</h4>
                  <p>{{$cl->client_message}}</p>
                </div>
              </a>
              @endif
              @endforeach
              @else
              <p class="my-3">

                No Previous Interactions
              </p>
              @endif
            </div>



          </div>

        </div>
      </div>



    </div>
  </div>
</div>




@endsection

@push('script_2')

@endpush