param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [string]$DatabaseName = "fouskee_concepts"
)

$ErrorActionPreference = "Stop"

function Resolve-MySql {
    $command = Get-Command mysql -ErrorAction SilentlyContinue
    if ($null -ne $command) {
        return $command.Source
    }

    $candidates = @(
        "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
    )

    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    throw "mysql.exe was not found."
}

$ProjectPath = [IO.Path]::GetFullPath($ProjectPath)
$sqlPath = Join-Path `
    $ProjectPath `
    "backend\database\security\audit-events-append-only.sql"

if (-not (Test-Path $sqlPath)) {
    throw "Audit hardening SQL not found at $sqlPath."
}

$mysql = Resolve-MySql
$sql = Get-Content -Path $sqlPath -Raw

Write-Host ""
Write-Host "Installing append-only audit triggers..." -ForegroundColor Cyan
Write-Host "MySQL will prompt for the LOCAL root password." -ForegroundColor Yellow

$args = @(
    "--host=127.0.0.1",
    "--port=3306",
    "--user=root",
    "--password",
    "--database=$DatabaseName",
    "--execute=$sql"
)

& $mysql @args

if ($LASTEXITCODE -ne 0) {
    throw "Audit hardening failed with exit code $LASTEXITCODE."
}

Write-Host "Audit trigger hardening complete." -ForegroundColor Green
