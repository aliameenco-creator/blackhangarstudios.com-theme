"""Build only distributable theme files; never include Git metadata or credentials."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
output = root / 'dist' / 'black-hangar.zip'
output.parent.mkdir(exist_ok=True)
directories = ('assets', 'blocks', 'includes', 'parts', 'patterns', 'templates')
files = [root / name for name in ('functions.php', 'style.css', 'theme.json', 'readme.txt')]
for name in directories:
    files.extend(path for path in (root / name).rglob('*') if path.is_file())
for file in files:
    relative = file.relative_to(root)
    if any(part.startswith('.') for part in relative.parts) or file.suffix.lower() in ('.key', '.pem', '.pfx', '.p12'):
        raise ValueError('Unexpected sensitive file in theme distribution: ' + str(relative))
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for file in sorted(files):
        archive.write(file, Path('black-hangar') / file.relative_to(root))
print('Built:', output)
