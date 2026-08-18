param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [string]$Database = "fouskee_concepts"
)

$ErrorActionPreference = "Stop"
$mysql = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
$sql = Join-Path $ProjectPath "backend\database\security\inventory-ledger-append-only.sql"

if (-not (Test-Path $mysql)) {
    throw "MySQL CLI not found at $mysql."
}
if (-not (Test-Path $sql)) {
    throw "Inventory hardening SQL not found at $sql."
}

Write-Host "Inventory ledger hardening requires a privileged MySQL account." -ForegroundColor Yellow
Write-Host "Enter the privileged MySQL password only at the mysql prompt."

Get-Content $sql -Raw | & $mysql -u root -p $Database

if ($LASTEXITCODE -ne 0) {
    throw "Inventory ledger hardening failed."
}

Write-Host "Inventory ledger append-only triggers installed on $Database." -ForegroundColor Green
