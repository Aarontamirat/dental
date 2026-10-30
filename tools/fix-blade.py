from pathlib import Path
for name in ['appointments', 'contact']:
    path = Path('resources/views/public') / f'{name}.blade.php'
    text = path.read_text(encoding='utf-8').replace('@if($loop->first)open@endif', '@if($loop->first) open @endif')
    path.write_text(text, encoding='utf-8')
