# AdHub demo data — imports seed-demo.sql and creates the sample deliverable
# files it references. Safe to re-run at any time.
#
# Usage (from anywhere):
#   powershell -ExecutionPolicy Bypass -File "E:\Documents\Adhub\DATABASE\seed-demo.ps1"

$ErrorActionPreference = 'Stop'
$OutputEncoding = [System.Text.Encoding]::UTF8

$dbDir   = $PSScriptRoot
$root    = Split-Path -Parent $dbDir
$mysql   = 'C:\xampp\mysql\bin\mysql.exe'
$sqlFile = Join-Path $dbDir 'seed-demo.sql'
$uploads = Join-Path $root 'uploads\deliverables'

# 1) Import the demo data.
Get-Content $sqlFile -Raw -Encoding UTF8 | & $mysql --default-character-set=utf8mb4 -u root adhubdb
if ($LASTEXITCODE -ne 0) {
    throw "mysql import failed (exit $LASTEXITCODE). Is MySQL running in XAMPP?"
}

# 2) Create the sample deliverable files referenced by the seed data.
New-Item -ItemType Directory -Path $uploads -Force | Out-Null

$pngBytes = [Convert]::FromBase64String(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
)
foreach ($name in @('demo-hero-banner.png', 'demo-social-teaser.png', 'demo-promo-poster.png', 'demo-grand-opening.png')) {
    [IO.File]::WriteAllBytes((Join-Path $uploads $name), $pngBytes)
}

@(
    'AdHub demo file — Tagline Lines (v1)',
    'Fresh. Bold. Brewed daily.',
    'UrbanFit — Train like you mean it.',
    'Bloom — Glow every day.'
) | Set-Content (Join-Path $uploads 'demo-tagline-lines.txt')

Write-Host ''
Write-Host 'Demo data imported.' -ForegroundColor Green
Write-Host 'Client logins (password: demo1234):' -ForegroundColor Cyan
Write-Host '  bloom@gmail.com     — Bloom Cosmetics'
Write-Host '  urbanfit@gmail.com  — UrbanFit Gym'
Write-Host '  brewbean@gmail.com  — BrewBean Coffee'
