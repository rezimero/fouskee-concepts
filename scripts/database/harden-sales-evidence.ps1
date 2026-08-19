param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts",
    [string]$MySqlPath = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
    [string]$OuterReportPath = "C:\Users\USER\Downloads\fouskee-sales-evidence-hardening.txt"
)

$ErrorActionPreference = "Stop"
$Databases = @("fouskee_concepts", "fouskee_concepts_test")
$SqlPath = Join-Path `
    $ProjectPath `
    "backend\database\security\sales-commercial-evidence.sql"

function Invoke-RootQuery {
    param([string]$Sql,[string]$Database = "")

    $args = @(
        "--host=localhost",
        "--user=root",
        "--batch",
        "--skip-column-names"
    )

    if ($Database -ne "") {
        $args += "--database=$Database"
    }

    $output = $Sql | & $MySqlPath @args 2>&1

    if ($LASTEXITCODE -ne 0) {
        throw (@($output) -join "`n")
    }

    return @($output)
}

function Write-HardeningReport {
    param([string]$Message)
    Write-Host $Message
    Add-Content -Path $OuterReportPath -Encoding UTF8 -Value $Message
}

if (-not (Test-Path $SqlPath)) {
    throw "Sales hardening SQL was not found."
}

$secure = Read-Host "MySQL root password" -AsSecureString
$credential = [PSCredential]::new("root", $secure)
$password = $credential.GetNetworkCredential().Password

try {
    $env:MYSQL_PWD = $password

    foreach ($hostName in @("127.0.0.1", "localhost")) {
        $account = "'fouskee_app'@'$hostName'"
        $grants = @(Invoke-RootQuery -Sql "SHOW GRANTS FOR $account;")
        $joined = ($grants -join "`n").ToUpperInvariant()

        foreach ($forbidden in @(
            "ALL PRIVILEGES",
            "TRIGGER",
            "SUPER",
            "CREATE USER",
            "GRANT OPTION"
        )) {
            if ($joined.Contains($forbidden)) {
                throw "Runtime grant boundary failed for $account."
            }
        }

        Write-HardeningReport "PASS runtime boundary: $account"
    }

    $sql = Get-Content -Path $SqlPath -Raw

    foreach ($database in $Databases) {
        Write-HardeningReport "Installing Sales evidence triggers in $database ..."
        $result = $sql | & $MySqlPath `
            --host=localhost `
            --user=root `
            --database=$database 2>&1

        if ($LASTEXITCODE -ne 0) {
            throw (@($result) -join "`n")
        }
    }

    & (Join-Path $ProjectPath "scripts\database\verify-sales-evidence.ps1") `
        -MySqlPath $MySqlPath `
        -UseExistingRootEnvironment `
        -OuterReportPath $OuterReportPath

    if ($LASTEXITCODE -ne 0) {
        throw "Sales trigger verification failed."
    }
}
finally {
    Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
    $password = $null
    $credential = $null
    $secure = $null
}
