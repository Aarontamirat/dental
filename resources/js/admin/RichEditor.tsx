import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Italic, List, ListOrdered, Heading2, Undo, Redo } from 'lucide-react';

export function RichEditor({ value, onChange, id }: { value: string; onChange: (value: string) => void; id: string }) {
  const editor = useEditor({ extensions: [StarterKit.configure({ link: { openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } } })], content: value, onUpdate: ({ editor }) => onChange(editor.getHTML()), editorProps: { attributes: { id, role: 'textbox', 'aria-multiline': 'true', 'aria-label': 'Rich text content', class: 'rich-content' } } });
  const actions = [
    { title: 'Bold', icon: Bold, action: () => editor?.chain().focus().toggleBold().run(), active: editor?.isActive('bold') },
    { title: 'Italic', icon: Italic, action: () => editor?.chain().focus().toggleItalic().run(), active: editor?.isActive('italic') },
    { title: 'Heading', icon: Heading2, action: () => editor?.chain().focus().toggleHeading({ level: 2 }).run(), active: editor?.isActive('heading') },
    { title: 'Bullet list', icon: List, action: () => editor?.chain().focus().toggleBulletList().run(), active: editor?.isActive('bulletList') },
    { title: 'Numbered list', icon: ListOrdered, action: () => editor?.chain().focus().toggleOrderedList().run(), active: editor?.isActive('orderedList') },
    { title: 'Undo', icon: Undo, action: () => editor?.chain().focus().undo().run() },
    { title: 'Redo', icon: Redo, action: () => editor?.chain().focus().redo().run() },
  ];
  return <div className="rich-editor"><div className="rich-toolbar" role="toolbar" aria-label="Text formatting">{actions.map(({ title, icon: Icon, action, active }) => <button type="button" key={title} title={title} aria-label={title} aria-pressed={active ?? false} onClick={action}><Icon size={16} /></button>)}</div><EditorContent editor={editor} /></div>;
}
