import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'

window.editor = new Editor({
  element: document.querySelector('#tiptap-editor'),
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3, 4, 5, 6],
      },
    }),
  ],
  content: '',
})
