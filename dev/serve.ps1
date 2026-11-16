# Serves the teme-dev site on http://127.0.0.1:8099 (the address its .env APP_URL names).
# Run from anywhere:  powershell -File dev\serve.ps1
# The site's working directory must be its public/ folder, as under a real web server.
$plugin = Split-Path -Parent $PSScriptRoot
$site = Join-Path (Split-Path -Parent $plugin) 'teme-dev'
$router = Join-Path $site 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'

Set-Location (Join-Path $site 'public')
php -S 127.0.0.1:8099 $router
