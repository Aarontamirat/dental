"""Create a clean shared-hosting release with locked production dependencies."""
from datetime import datetime, timezone
from pathlib import Path
import argparse
import hashlib
import json
import shutil
import subprocess
import tempfile
import zipfile


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', default='.tools/releases', help='ZIP and checksum output directory')
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    if not (root / 'public/build/manifest.json').is_file():
        raise SystemExit('Run npm ci and npm run build first.')
    if (root / 'public/hot').exists():
        raise SystemExit('Stop Vite and remove its generated public/hot file before packaging.')
    composer = shutil.which('composer')
    if not composer:
        raise SystemExit('Composer 2 is required on the build computer.')
    output = (root / args.output).resolve()
    output.mkdir(parents=True, exist_ok=True)
    archive = output / f'dental-clinic-{datetime.now(timezone.utc).strftime("%Y%m%d-%H%M%S")}.zip'
    stage_parent = root / '.tools'
    stage_parent.mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='release-build-', dir=stage_parent) as temporary:
        stage = Path(temporary)
        for directory in ['app', 'config', 'database/migrations', 'database/seeders', 'lang', 'resources/views', 'routes']:
            shutil.copytree(root / directory, stage / directory)
        (stage / 'bootstrap/cache').mkdir(parents=True)
        (stage / 'bootstrap/cache/.gitignore').write_text('*\n!.gitignore\n', encoding='utf-8')
        for file in ['app.php', 'providers.php']:
            shutil.copy2(root / 'bootstrap' / file, stage / 'bootstrap' / file)
        shutil.copytree(root / 'public', stage / 'public', ignore=shutil.ignore_patterns('hot', 'storage', '*.ttf'))
        for file in ['artisan', 'composer.json', 'composer.lock', '.env.example']:
            shutil.copy2(root / file, stage / file)
        for name in ['README.md', 'ARCHITECTURE.md', 'IMPLEMENTATION_PLAN.md', 'INSTALLATION.md', 'SHARED_HOSTING_DEPLOYMENT.md', 'ADMIN_USER_GUIDE.md', 'CLINIC_CUSTOMIZATION_GUIDE.md', 'BACKUP_AND_RESTORATION.md', 'SECURITY.md', 'TESTING.md', 'ASSET_LICENSES.md', 'FRONTEND_AUDIT.md', 'DESIGN_SYSTEM.md', 'APPEARANCE_SETTINGS_GUIDE.md', 'CHANGELOG.md']:
            shutil.copy2(root / name, stage / name)
        for directory in ['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/cache/locks', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs']:
            (stage / directory).mkdir(parents=True, exist_ok=True)
            (stage / directory / '.gitignore').write_text('*\n!.gitignore\n', encoding='utf-8')
        subprocess.run([composer, 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction', '--no-scripts'], cwd=stage, check=True)
        installed = json.loads((stage / 'vendor/composer/installed.json').read_text(encoding='utf-8'))
        if installed.get('dev') or any(p['name'].startswith(('phpunit/', 'fakerphp/', 'mockery/')) for p in installed['packages']):
            raise SystemExit('Development dependencies unexpectedly present in release.')
        files = [p for p in stage.rglob('*') if p.is_file()]
        forbidden = {'.env', 'database.sqlite', 'local-access.json', 'e2e-access.json', 'hot'}
        if any(p.name in forbidden for p in files):
            raise SystemExit('A private development artifact unexpectedly entered the release.')
        with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as handle:
            for file in files:
                handle.write(file, file.relative_to(stage).as_posix())
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    archive.with_suffix('.zip.sha256').write_text(f'{digest}  {archive.name}\n', encoding='utf-8')
    print(f'Release: {archive}')
    print(f'Size: {archive.stat().st_size / 1024 / 1024:.1f} MB; production dependencies only; no credentials or patient data.')


if __name__ == '__main__':
    main()
