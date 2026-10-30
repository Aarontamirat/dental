"""Download replaceable demonstration photography and self-hosted Google fonts."""
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import urllib.request
import re
import json
import time

def fetch(url):
    for attempt in range(6):
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'})
            with urllib.request.urlopen(req, timeout=45) as response:
                return response.read()
        except Exception:
            if attempt == 5:
                raise
            time.sleep(1)

ROOT = Path(__file__).resolve().parents[1] / 'public'
PHOTOS = {
    'hero': ('photo-1606811971618-4486d14f3f99', 'Dental consultation demonstration photograph'),
    'clinic': ('photo-1629909613654-28e377c37b09', 'Modern dental clinic demonstration photograph'),
    'care': ('photo-1609840114035-3c981b782dfe', 'Dental care demonstration photograph'),
    'smile': ('photo-1606811841689-23dfddce3e95', 'Dental care stock photograph'),
    'team-one': ('photo-1559839734-2b71ea197ec2', 'Stock portrait for demonstration profile'),
    'team-two': ('photo-1612349317150-e413f6a5b16d', 'Stock portrait for demonstration profile'),
}

def download_photo(item):
    name, (photo, _) = item
    url = f'https://images.unsplash.com/{photo}?auto=format&fit=crop&w=1400&q=85&fm=webp'
    target = ROOT / 'images' / f'{name}.webp'
    data = target.read_bytes() if target.exists() else fetch(url)
    target.write_bytes(data)
    return {'name': name, 'source': f'https://unsplash.com/photos/{photo}', 'asset_url': url, 'bytes': len(data), 'use': PHOTOS[name][1]}

def download_fonts():
    url = 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&family=Noto+Sans+Ethiopic:wght@400;500;600;700&display=swap'
    css = fetch(url).decode()
    urls = list(dict.fromkeys(re.findall(r'url\((https://[^)]+)\)', css)))
    for i, font_url in enumerate(urls):
        ext = '.woff2' if '.woff2' in font_url else '.ttf'
        path = f'/fonts/clinic-font-{i}{ext}'
        (ROOT / path.lstrip('/')).write_bytes(fetch(font_url))
        css = css.replace(font_url, path)
    (ROOT / 'fonts' / 'fonts.css').write_text(css, encoding='utf-8')

if __name__ == '__main__':
    (ROOT / 'images').mkdir(parents=True, exist_ok=True)
    (ROOT / 'fonts').mkdir(parents=True, exist_ok=True)
    with ThreadPoolExecutor(max_workers=4) as pool:
        photos = list(pool.map(download_photo, PHOTOS.items()))
    (ROOT / 'images' / 'SOURCES.json').write_text(json.dumps(photos, indent=2), encoding='utf-8')
    download_fonts()
    print('Downloaded local demonstration photographs and fonts.')
