param(
    [string]$MySqlPath = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
    [switch]$UseExistingRootEnvironment,
    [string]$OuterReportPath = "C:\Users\USER\Downloads\fouskee-sales-evidence-verification.txt"
)

$ErrorActionPreference = "Stop"
$Databases = @("fouskee_concepts", "fouskee_concepts_test")
$Triggers = @(
    "sales_orders_freeze_commercial_update",
    "sales_orders_prevent_delete_after_draft",
    "sales_order_lines_freeze_insert",
    "sales_order_lines_freeze_update",
    "sales_order_lines_freeze_delete",
    "sales_quotations_freeze_commercial_update",
    "sales_quotations_prevent_delete_after_draft",
    "sales_quotation_lines_freeze_insert",
    "sales_quotation_lines_freeze_update",
    "sales_quotation_lines_freeze_delete"
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

$secure = $null
$password = $null

try {
    if (-not $UseExistingRootEnvironment) {
        $secure = Read-Host "MySQL root password" -AsSecureString
        $credential = [PSCredential]::new("root", $secure)
        $password = $credential.GetNetworkCredential().Password
        $env:MYSQL_PWD = $password
    }

    foreach ($database in $Databases) {
        $rows = @(Query-Root -Sql @"
SELECT CONCAT(TRIGGER_NAME, '|', DEFINER, '|', ACTION_STATEMENT)
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = '$database';
"@)

        $map = @{}
        foreach ($row in $rows) {
            $parts = $row -split '\|', 3
            if ($parts.Count -eq 3) {
                $map[$parts[0]] = @($parts[1], $parts[2])
            }
        }

        foreach ($name in $Triggers) {
            if (-not $map.ContainsKey($name)) {
                throw "Missing trigger $name in $database."
            }

            if ($map[$name][0] -like "fouskee_app@*") {
                throw "Unsafe runtime definer for $name."
            }

            if ($map[$name][1] -notmatch "SIGNAL SQLSTATE") {
                throw "Trigger $name lacks an explicit tamper signal."
            }
        }

        Report "PASS: all 10 Sales evidence triggers verified in $database."
    }

    Report "SALES EVIDENCE TRIGGER BASELINE: PASS"
}
finally {
    if (-not $UseExistingRootEnvironment) {
        Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
    }

    $password = $null
    $secure = $null
}
