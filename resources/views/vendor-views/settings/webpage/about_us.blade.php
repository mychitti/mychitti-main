  <style>
        .form-row {
            margin-top: 6px;
        }

        .ck.ck-reset {
            width: 100% !important;
        }
    </style>
      <form class="w-100 p-0" id="about_us-form" action="{{ route('vendor.business-settings.about-us.save') }}" method="post">
                @csrf

                <div class="col-md-12">
                    <div class="form-row ">
                        <textarea placeholder="Start Typing ..."  id="editor" class="form-control" name="content" >{!! $about_us ?? '' !!}</textarea>
                    </div>
                    <input type="hidden" class="upload_url" value="{{route('vendor.business-settings.image-upload')}}">
                    <button class="btn btn-primary my-2">Update</button>
                </div>
            </form> 
