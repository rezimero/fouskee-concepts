param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [string]$DatabaseName = "fouskee_concepts",
    [string]$DatabaseUser = "fouskee_app"
)

$ErrorActionPreference = "Stop"

function Require-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required command '$Name' was not found in PATH."
    }
}

function ConvertFrom-SecureValue([Security.SecureString]$SecureValue) {
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecureValue)
    try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

function New-DatabasePassword {
    $chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789"
    $bytes = New-Object byte[] 32
    [Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
    -join ($bytes | ForEach-Object { $chars[$_ % $chars.Length] })
}

function Set-EnvValue([string]$Path, [string]$Key, [string]$Value) {
    $rendered = if ($Value -match '\s') { '"' + $Value + '"' } else { $Value }
    $lines = @(Get-Content $Path)
    $pattern = "^$([regex]::Escape($Key))="
    $found = $false

    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match $pattern) {
            $lines[$i] = "$Key=$rendered"
            $found = $true
            break
        }
    }

    if (-not $found) { $lines += "$Key=$rendered" }
    Set-Content $Path -Value $lines -Encoding utf8
}

Write-Host ""
Write-Host "Fouskee Concepts - MySQL Configuration" -ForegroundColor Green

Require-Command "mysql"
Require-Command "php"

$ProjectPath = [IO.Path]::GetFullPath($ProjectPath)
$BackendPath = Join-Path $ProjectPath "backend"
$EnvPath = Join-Path $BackendPath ".env"
$ExampleEnvPath = Join-Path $BackendPath ".env.example"

if (-not (Test-Path (Join-Path $BackendPath "artisan"))) {
    throw "Laravel backend not found at $BackendPath."
}
if (-not (Test-Path $EnvPath)) {
    Copy-Item $ExampleEnvPath $EnvPath
}

$rootSecure = Read-Host "Enter your LOCAL MySQL root password" -AsSecureString
$rootPassword = ConvertFrom-SecureValue $rootSecure
$appPassword = New-DatabasePassword
$tempClient = Join-Path $env:TEMP "fouskee-mysql-client-$PID.cnf"
$tempSql = Join-Path $env:TEMP "fouskee-mysql-setup-$PID.sql"

try {
    @"
[client]
user=root
password=$rootPassword
"@ | Set-Content $tempClient -Encoding ascii

    @"
CREATE DATABASE IF NOT EXISTS ``$DatabaseName``
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '$DatabaseUser'@'127.0.0.1' IDENTIFIED BY '$appPassword';
CREATE USER IF NOT EXISTS '$DatabaseUser'@'localhost' IDENTIFIED BY '$appPassword';

ALTER USER '$DatabaseUser'@'127.0.0.1' IDENTIFIED BY '$appPassword';
ALTER USER '$DatabaseUser'@'localhost' IDENTIFIED BY '$appPassword';

GRANT ALL PRIVILEGES ON ``$DatabaseName``.* TO '$DatabaseUser'@'127.0.0.1';
GRANT ALL PRIVILEGES ON ``$DatabaseName``.* TO '$DatabaseUser'@'localhost';
FLUSH PRIVILEGES;
"@ | Set-Content $tempSql -Encoding ascii

    Write-Host "Creating database and application user..." -ForegroundColor Cyan
    Get-Content $tempSql -Raw | & mysql "--defaults-extra-file=$tempClient"
    if ($LASTEXITCODE -ne 0) {
        throw "MySQL returned exit code $LASTEXITCODE."
    }

    $settings = [ordered]@{
        APP_NAME      = "Fouskee Concepts"
        APP_ENV       = "local"
        APP_DEBUG     = "true"
        APP_URL       = "http://localhost:8000"
        DB_CONNECTION = "mysql"
        DB_HOST       = "127.0.0.1"
        DB_PORT       = "3306"
        DB_DATABASE   = $DatabaseName
        DB_USERNAME   = $DatabaseUser
        DB_PASSWORD   = $appPassword
    }

    foreach ($entry in $settings.GetEnumerator()) {
        Set-EnvValue $EnvPath $entry.Key $entry.Value
    }

    foreach ($entry in $settings.GetEnumerator()) {
        $exampleValue = if ($entry.Key -eq "DB_PASSWORD") { "" } else { $entry.Value }
        Set-EnvValue $ExampleEnvPath $entry.Key $exampleValue
    }

    Push-Location $BackendPath
    try {
        Write-Host "Clearing Laravel configuration cache..." -ForegroundColor Cyan
        php artisan config:clear

        Write-Host "Running migrations..." -ForegroundColor Cyan
        php artisan migrate

        Write-Host "Migration status:" -ForegroundColor Cyan
        php artisan migrate:status
    }
    finally {
        Pop-Location
    }

    $rootGitIgnore = Join-Path $ProjectPath ".gitignore"
    $existing = if (Test-Path $rootGitIgnore) { @(Get-Content $rootGitIgnore) } else { @() }

    foreach ($entry in @("*.zip", "Thumbs.db", ".DS_Store")) {
        if ($existing -notcontains $entry) {
            Add-Content $rootGitIgnore -Value $entry -Encoding utf8
        }
    }

    Write-Host ""
    Write-Host "MySQL configuration complete." -ForegroundColor Green
    Write-Host "Database : $DatabaseName"
    Write-Host "App user : $DatabaseUser"
    Write-Host "Host     : 127.0.0.1:3306"
    Write-Host "Generated DB password is stored in backend\.env only." -ForegroundColor Yellow
    Write-Host "Do NOT commit backend\.env." -ForegroundColor Yellow
}
finally {
    $rootPassword = $null
    $rootSecure = $null
    Remove-Item $tempClient -Force -ErrorAction SilentlyContinue
    Remove-Item $tempSql -Force -ErrorAction SilentlyContinue
}
