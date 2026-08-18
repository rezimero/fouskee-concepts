param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [Parameter(Mandatory)]
    [string]$Database,
    [string]$MySqlExe = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
)

$ErrorActionPreference = "Stop"

$ProjectPath = [IO.Path]::GetFullPath($ProjectPath)
$SqlPath = Join-Path `
    $ProjectPath `
    "backend\database\security\procurement-evidence-hardening.sql"

if (-not (Test-Path $MySqlExe)) {
    throw "MySQL CLI not found at $MySqlExe."
}

if (-not (Test-Path $SqlPath)) {
    throw "Procurement hardening SQL not found at $SqlPath."
}

$sqlForMySql = $SqlPath.Replace('\', '/')

Write-Host "Procurement evidence hardening requires a privileged MySQL account."
Write-Host "Enter the privileged MySQL password only at the mysql prompt."

& $MySqlExe `
    "--host=127.0.0.1" `
    "--port=3306" `
    "--user=root" `
    "--password" `
    "--database=$Database" `
    "--execute=source $sqlForMySql"

if ($LASTEXITCODE -ne 0) {
    throw "MySQL procurement evidence hardening failed."
}

Write-Host "Procurement evidence triggers installed on $Database." `
    -ForegroundColor Green
