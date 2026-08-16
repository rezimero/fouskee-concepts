$ErrorActionPreference = "Continue"

$ProjectPath = "C:\Users\USER\FouskeeConcepts"

Write-Host ""
Write-Host "============================================" -ForegroundColor DarkGreen
Write-Host " Fouskee Concepts - Foundation Verification" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor DarkGreen
Write-Host ""

if (-not (Test-Path $ProjectPath)) {
    Write-Error "Project path not found: $ProjectPath"
    exit 1
}

Set-Location $ProjectPath

function Show-CommandResult {
    param(
        [string]$Title,
        [scriptblock]$Command
    )

    Write-Host ""
    Write-Host "---- $Title ----" -ForegroundColor Cyan
    try {
        & $Command
    }
    catch {
        Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Show-CommandResult "PowerShell" {
    $PSVersionTable.PSVersion
}

Show-CommandResult "Git" {
    git --version
    git branch --show-current
    git status --short
}

Show-CommandResult "PHP" {
    php --version
}

Show-CommandResult "PHP Configuration" {
    php --ini
}

Show-CommandResult "PHP Database Extensions" {
    $modules = php -m
    $dbModules = $modules | Where-Object {
        $_ -match 'PDO|pdo_mysql|mysqli|pdo_sqlite|sqlite'
    }

    if ($dbModules) {
        $dbModules
    }
    else {
        Write-Host "No PDO/MySQL/SQLite database extension detected." -ForegroundColor Yellow
    }
}

Show-CommandResult "Laravel" {
    Push-Location "$ProjectPath\backend"
    php artisan --version
    Pop-Location
}

Show-CommandResult "Node.js" {
    node --version
}

Show-CommandResult "npm" {
    npm --version
}

Show-CommandResult "Frontend" {
    if (Test-Path "$ProjectPath\frontend\package.json") {
        Write-Host "frontend\package.json found." -ForegroundColor Green
    }
    else {
        Write-Host "frontend\package.json NOT found." -ForegroundColor Red
    }

    if (Test-Path "$ProjectPath\frontend\node_modules") {
        Write-Host "frontend\node_modules found." -ForegroundColor Green
    }
    else {
        Write-Host "frontend\node_modules NOT found." -ForegroundColor Yellow
    }
}

Show-CommandResult "MySQL CLI" {
    if (Get-Command mysql -ErrorAction SilentlyContinue) {
        mysql --version
    }
    else {
        Write-Host "mysql command not found in PATH." -ForegroundColor Yellow
    }
}

Show-CommandResult "MySQL Port 3306" {
    $result = Test-NetConnection -ComputerName "127.0.0.1" -Port 3306 -WarningAction SilentlyContinue
    Write-Host "TcpTestSucceeded: $($result.TcpTestSucceeded)"
}

Write-Host ""
Write-Host "============================================" -ForegroundColor DarkGreen
Write-Host " Verification complete" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor DarkGreen
Write-Host ""
Write-Host "Copy the complete output and send it back before MySQL configuration." -ForegroundColor Yellow
