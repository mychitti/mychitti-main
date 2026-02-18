 @extends('layouts.vendor.app')

 @section('title', 'AI Chat')

 @section('content')
     <div class="content container-fluid">

         <div class="page-header">
             <h4 class="page-title">AI Assistant</h4>
         </div>

         <div class="card">
             <div class="card-body">

                 <div id="chat-box"
                     style="height:420px;overflow-y:auto;border:1px solid #e5e5e5;padding:15px;border-radius:6px;margin-bottom:15px;background:#fafafa">
                     <div class="text-muted text-center">Loading chat…</div>
                 </div>

                 <form id="chat-form" enctype="multipart/form-data">
                     @csrf 

                     <div class="input-group mb-2">

                         <textarea class="form-control" id="message" name="message" placeholder="Type your message..." rows="2"></textarea>

                         <div class="input-group-append">
                             <button class="btn btn-primary" type="submit">Send</button>
                         </div>
                     </div>

                     <div class="d-flex align-items-center gap-2">

                         <!-- Image & PDF -->
                         <input type="file" id="fileInput" name="file" accept="image/*,.pdf" style="display:none">

                         <button type="button" class="btn btn-sm btn-secondary" id="attachFileBtn">
                             📎 Attach
                         </button>

                         <!-- Voice -->
                         <button type="button" class="btn btn-sm btn-info" id="startRecord">
                             🎤 Record
                         </button>

                         <button type="button" class="btn btn-sm btn-warning d-none" id="stopRecord">
                             ⏹ Stop
                         </button>

                         <span class="ml-2 text-muted" id="recordStatus"></span>

                     </div>

                 </form>


                 <div class="mt-3 text-right">
                     <button class="btn btn-sm btn-danger" id="clear-memory">
                         Clear memory
                     </button>
                 </div>

             </div>
         </div>

     </div>
 @endsection

 @push('script_2')
 <script>
let mediaRecorder;
let audioChunks = [];
let recordedBlob = null;

/* file picker */
$('#attachFileBtn').on('click', function () {
    $('#fileInput').click();
});

/* voice recording */

$('#startRecord').on('click', async function () {

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Voice recording not supported in this browser.');
        return;
    }

    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

    audioChunks = [];
    recordedBlob = null;

    mediaRecorder = new MediaRecorder(stream);

    mediaRecorder.ondataavailable = e => audioChunks.push(e.data);

    mediaRecorder.onstop = e => {
        recordedBlob = new Blob(audioChunks, { type: 'audio/webm' });
        $('#recordStatus').text('Voice recorded. Ready to send.');
    };

    mediaRecorder.start();

    $('#startRecord').addClass('d-none');
    $('#stopRecord').removeClass('d-none');
    $('#recordStatus').text('Recording...');
});

$('#stopRecord').on('click', function () {

    mediaRecorder.stop();

    $('#stopRecord').addClass('d-none');
    $('#startRecord').removeClass('d-none');
});

/* override submit to support file & voice */

$('#chat-form').off('submit').on('submit', function(e){
    e.preventDefault();

    let formData = new FormData();

    let message = $('#message').val();

    formData.append('_token', "{{ csrf_token() }}");
    formData.append('message', message);

    const file = $('#fileInput')[0].files[0];
    if(file){
        formData.append('file', file);
    }

    if(recordedBlob){
        formData.append('voice', recordedBlob, 'voice.webm');
    }

    if(!message && !file && !recordedBlob){
        return;
    }

    if(message){
        renderMessage('user', message);
    }else if(file){
        renderMessage('user', '[File sent]');
    }else if(recordedBlob){
        renderMessage('user', '[Voice message sent]');
    }

    $('#message').val('');
    $('#fileInput').val('');
    recordedBlob = null;
    $('#recordStatus').text('');

    $.ajax({
        url: "{{ route('vendor.ai-chat.send') }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(res){
            if(res.success){
                renderMessage('assistant', res.message);
            }else{
                alert('Something went wrong.');
            }
        },
        error: function(){
            alert('Upload failed.');
        }
    });
});
</script>


    
 @endpush
 