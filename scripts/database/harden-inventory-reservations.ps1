param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [string]$MySqlPath = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
    [string]$OuterReportPath = "C:\Users\USER\Downloads\fouskee-v0.4b1-reservation-hardening.txt"
)

$ErrorActionPreference = "Stop"
$Databases = @("fouskee_concepts","fouskee_concepts_test")
$SqlPath = Join-Path `
    $ProjectPath `
    "backend\database\security\inventory-reservations-append-only.sql"
$Triggers = @(
    "inventory_reservations_freeze_identity",
    "inventory_reservations_prevent_delete"
)

function Query-Root {
    param([string]$Sql)

    $output = $Sql | & $MySqlPath `
        --host=localhost `
        --user=root `
        --batch `
        --skip-column-names 2>&1

    if ($LASTEXITCODE -ne 0) {
        throw (@($output) -join "`n")
    }

    return @($output)
}

function Report {
    param([string]$Message)
    Write-Host $Message
    Add-Content -Path $OuterReportPath -Encoding UTF8 -Value $Message
}

$secure = Read-Host "MySQL root password" -AsSecureString
$credential = [PSCredential]::new("root",$secure)
$password = $credential.GetNetworkCredential().Password

try {
    $env:MYSQL_PWD = $password

    foreach ($hostName in @("127.0.0.1","localhost")) {
        $account = "'fouskee_app'@'$hostName'"
        $grants = @(Query-Root -Sql "SHOW GRANTS FOR $account;")
        $joined = ($grants -join "`n").ToUpperInvariant()

        foreach ($forbidden in @(
            "ALL PRIVILEGES","TRIGGER","SUPER","CREATE USER","GRANT OPTION"
        )) {
            if ($joined.Contains($forbidden)) {
                throw "Runtime grant boundary failed for $account."
            }
        }

        Report "PASS runtime boundary: $account"
    }

    $sql = Get-Content -Path $SqlPath -Raw

    foreach ($database in $Databases) {
        Report "Installing reservation triggers in $database ..."

        $output = $sql | & $MySqlPath `
            --host=localhost `
            --user=root `
            --database=$database 2>&1

        if ($LASTEXITCODE -ne 0) {
            throw (@($output) -join "`n")
        }

        $rows = @(Query-Root -Sql @"
SELECT CONCAT(TRIGGER_NAME, '|', DEFINER, '|', ACTION_STATEMENT)
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = '$database';
"@)

        foreach ($name in $Triggers) {
            $row = @($rows | Where-Object { $_ -like "$name|*" })

            if ($row.Count -ne 1) {
                throw "Expected trigger $name once in $database."
            }

            if ($row[0] -match "\|fouskee_app@") {
                throw "Unsafe runtime definer for $name."
            }

            if ($row[0] -notmatch "SIGNAL SQLSTATE") {
                throw "Trigger $name lacks an explicit tamper signal."
            }
        }

        Report "PASS: both reservation triggers verified in $database."
    }

    Report "INVENTORY RESERVATION HARDENING: PASS"
}
finally {
    Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
    $password = $null
    $credential = $null
    $secure = $null
}
