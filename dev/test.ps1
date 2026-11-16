# Runs the plugin's tests with the Pest binary of the teme-dev site.
#   powershell -File dev\test.ps1                 all tests
#   powershell -File dev\test.ps1 --filter=Name    one test
# Needs the stack:  docker compose -f dev/docker-compose.yml up -d
$plugin = Split-Path -Parent $PSScriptRoot
$site = Join-Path (Split-Path -Parent $plugin) 'teme-dev'
Set-Location $plugin
& (Join-Path $site 'vendor\bin\pest.bat') --configuration phpunit.xml @args
exit $LASTEXITCODE
