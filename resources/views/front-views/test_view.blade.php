<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
.editor-toolbar {
    border: 1px solid #ccc;
    padding: 8px;
    background: #f8f8f8;
}

.editor-toolbar button {
    margin-right: 6px;
    padding: 6px 10px;
    cursor: pointer;
}

#tiptap-editor {
    border: 1px solid #ccc;
    min-height: 300px;
    padding: 12px;
}

.ProseMirror:focus {
    outline: none;
}

</style>
</head>

<body>
    {{-- <form method="post" action="{{route('send-test-notification')}}">
    @csrf
    <input type="email" name="email" id="">
    <input type="hidden" name="type" value="{{$type}}">
    <button class="btn">send notification</button>
    </form> --}}
   <form method="POST" action="{{ route('home') }}" enctype="multipart/form-data">
    @csrf

    <!-- Toolbar -->
    <div class="editor-toolbar">
        <button type="button" onclick="cmd('bold')"><b>B</b></button>
        <button type="button" onclick="cmd('italic')"><i>I</i></button>
        <button type="button" onclick="cmd('underline')"><u>U</u></button>

        <button type="button" onclick="cmd('h2')">H2</button>
        <button type="button" onclick="cmd('h3')">H3</button>

        <button type="button" onclick="cmd('ul')">• List</button>
        <button type="button" onclick="cmd('ol')">1. List</button>

        <button type="button" onclick="addLink()">Link</button>
        <button type="button" onclick="selectImage()">Image</button>
    </div>

    <!-- Editor -->
    <div id="tiptap-editor"></div>

    <!-- Hidden input -->
    <input type="hidden" name="content" id="content">

    <br>
    <button type="submit" onclick="saveContent()">Save</button>
</form>

  <script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core'
import StarterKit from 'https://esm.sh/@tiptap/starter-kit'
import Underline from 'https://esm.sh/@tiptap/extension-underline'
import Link from 'https://esm.sh/@tiptap/extension-link'
import Image from 'https://esm.sh/@tiptap/extension-image'

window.editor = new Editor({
  element: document.querySelector('#tiptap-editor'),
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [2, 3],
      },
    }),
    Underline,
    Link.configure({
      openOnClick: false,
    }),
    Image,
  ],
  content: '<p>Start typing...</p>',
})

/* COMMANDS */
window.cmd = function(type) {
  editor.chain().focus()

  if (type === 'bold') editor.toggleBold().run()
  if (type === 'italic') editor.toggleItalic().run()
  if (type === 'underline') editor.toggleUnderline().run()
  if (type === 'h2') editor.toggleHeading({ level: 2 }).run()
  if (type === 'h3') editor.toggleHeading({ level: 3 }).run()
  if (type === 'ul') editor.toggleBulletList().run()
  if (type === 'ol') editor.toggleOrderedList().run()
}

window.addLink = function() {
  const url = prompt('Enter URL')
  if (url) {
    editor.chain().focus().setLink({ href: url }).run()
  }
}

window.saveContent = function() {
  document.getElementById('content').value = editor.getHTML()
}

window.selectImage = function() {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = uploadImage
  input.click()
}

async function uploadImage(e) {
  const file = e.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)
  formData.append('_token', '{{ csrf_token() }}')

  const res = await fetch('{{ route("home") }}', {
    method: 'POST',
    body: formData,
  })

  const data = await res.json()

  editor.chain().focus().setImage({ src: data.url }).run()
}
</script>




</body>

</html>
