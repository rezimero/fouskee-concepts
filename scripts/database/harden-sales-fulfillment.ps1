param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [string]$MySqlPath = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
    [string]$OuterReportPath = "C:\Users\USER\Downloads\fouskee-v0.4b2-fulfillment-hardening.txt"
)

$ErrorActionPreference = "Stop"
$Databases = @("fouskee_concepts","fouskee_concepts_test")
$SqlPath = Join-Path `
    $ProjectPath `
    "backend\database\security\sales-fulfillment-evidence.sql"
$Triggers = @(
    "sales_fulfillments_freeze_identity",
    "sales_fulfillments_prevent_delete",
    "sales_fulfillment_lines_insert_guard",
    "sales_fulfillment_lines_freeze_update",
    "sales_fulfillment_lines_prevent_delete",
    "inventory_reservations_lifecycle_guard"
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
        Report "Installing v0.4B2 triggers in $database ..."

        $result = $sql | & $MySqlPath `
            --host=localhost `
            --user=root `
            --database=$database 2>&1

        if ($LASTEXITCODE -ne 0) {
            throw (@($result) -join "`n")
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

        Report "PASS: all 6 v0.4B2 triggers verified in $database."
    }

    Report "SALES FULFILLMENT HARDENING: PASS"
}
finally {
    Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
    $password = $null
    $credential = $null
    $secure = $null
}
