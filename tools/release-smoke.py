"""Verify a built release using fresh SQLite data and a separate public document root."""
from pathlib import Path
import json
import os
import secrets
import shutil
import subprocess
import tempfile
import time
import urllib.error
import urllib.request
import zipfile

root = Path(__file__).resolve().parents[1]
archives = sorted((root / '.tools/releases').glob('dental-clinic-*.zip'))
if not archives:
    raise SystemExit('Build a release first with tools/package-release.py.')
php = shutil.which('php')
if not php:
    raise SystemExit('PHP is required.')

with tempfile.TemporaryDirectory(prefix='release-smoke-', dir=root / '.tools') as temporary:
    scratch = Path(temporary).resolve()
    assert scratch.is_relative_to((root / '.tools').resolve())
    app = scratch / 'clinic'
    app.mkdir()
    with zipfile.ZipFile(archives[-1]) as archive:
        for name in archive.namelist():
            assert (app / name).resolve().is_relative_to(app.resolve())
        archive.extractall(app)
    public = scratch / 'public-html'
    shutil.copytree(app / 'public', public)
    index = (public / 'index.php').read_text(encoding='utf-8')
    index = index.replace("__DIR__.'/../", "'" + app.as_posix() + '/')
    (public / 'index.php').write_text(index, encoding='utf-8')
    database = scratch / 'fresh.sqlite'
    database.touch()
    env = os.environ.copy()
    for name in ['APP_ENV', 'APP_KEY', 'DB_CONNECTION', 'DB_DATABASE', 'APP_PUBLIC_PATH']:
        env.pop(name, None)
    (app / '.env').write_text('\n'.join([
        'APP_ENV=local', 'APP_DEBUG=false', 'APP_URL=http://127.0.0.1:8022',
        'APP_KEY=', 'DB_CONNECTION=sqlite', f'DB_DATABASE="{database.as_posix()}"',
        f'APP_PUBLIC_PATH="{public.as_posix()}"', 'SESSION_DRIVER=database',
        'SESSION_ENCRYPT=true', 'CACHE_STORE=file', 'QUEUE_CONNECTION=sync',
        'MAIL_MAILER=log', 'LOG_LEVEL=warning', 'SESSION_SECURE_COOKIE=false',
    ]) + '\n', encoding='utf-8')
    credentials = scratch / 'private-admin.json'
    credentials.write_text(json.dumps({'name': 'Release Test Administrator', 'email': 'release@clinic.test', 'password': 'Qa!' + secrets.token_hex(20) + 'Aa'}), encoding='utf-8')
    commands = [
        ['package:discover'], ['key:generate', '--force'], ['migrate', '--force'],
        ['db:seed', '--class=DemoSeeder', '--force'], ['clinic:admin-file', str(credentials)],
        ['config:cache'], ['route:cache'], ['view:cache'], ['clinic:check'],
    ]
    for command in commands:
        result = subprocess.run([php, 'artisan', *command], cwd=app, env=env, capture_output=True, text=True)
        if result.returncode:
            print(result.stdout, result.stderr)
            raise SystemExit(f'Release command failed: {command[0]}')
        print(f'PASS release {command[0]}')
    assert not credentials.exists(), 'Credential file was not removed.'
    invalid = public / 'disallowed-admin.json'
    invalid.write_text('{}', encoding='utf-8')
    result = subprocess.run([php, 'artisan', 'clinic:admin-file', str(invalid)], cwd=app, env=env, capture_output=True, text=True)
    assert result.returncode != 0 and 'outside public' in result.stdout
    invalid.unlink()
    print('PASS separate public root restricts credential setup')
    router = scratch / 'router.php'
    router.write_text("<?php $file=" + "'" + public.as_posix() + "'" + ".parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); if(is_file($file)) return false; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['PHP_SELF']='/index.php'; $_SERVER['SCRIPT_FILENAME']='" + public.as_posix() + "/index.php'; unset($_SERVER['PATH_INFO']); require '" + public.as_posix() + "/index.php';", encoding='utf-8')
    server = subprocess.Popen([php, '-S', '127.0.0.1:8022', '-t', str(public), str(router)], cwd=app, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    try:
        for _ in range(30):
            try:
                urllib.request.urlopen('http://127.0.0.1:8022/up', timeout=2)
                break
            except urllib.error.URLError:
                if server.poll() is not None:
                    raise SystemExit('Release smoke server failed to start; check port 8022.')
                time.sleep(.2)
        def status(path):
            try:
                return urllib.request.urlopen('http://127.0.0.1:8022' + path, timeout=10).status
            except urllib.error.HTTPError as error:
                return error.code
        for path in ['/', '/services/general-dentistry', '/appointments', '/contact', '/login', '/sitemap.xml', '/robots.txt', '/fonts/fonts.css']:
            assert status(path) == 200, path
        manifest = json.loads((public / 'build/manifest.json').read_text())
        for entry in manifest.values():
            assert status('/build/' + entry['file']) == 200
        for path in ['/.env', '/vendor/autoload.php', '/storage/logs/laravel.log']:
            actual = status(path)
            assert actual in (403, 404), f'{path}: HTTP {actual}'
        print('PASS release public routes, compiled assets, and private-file isolation')
    finally:
        server.terminate()
        server.wait(timeout=10)
print('Release smoke check passed; temporary isolated database and credentials removed.')
