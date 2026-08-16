param(
    [string]$ProjectPath = (Get-Location).Path
)

$ErrorActionPreference = "Stop"

function Require-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required command '$Name' was not found in PATH."
    }
}

$ProjectPath = [System.IO.Path]::GetFullPath($ProjectPath)
Set-Location $ProjectPath

Write-Host "Fouskee Concepts - Foundation v0.1" -ForegroundColor Green
Write-Host "Project path: $ProjectPath" -ForegroundColor Cyan

Require-Command "git"
Require-Command "composer"
Require-Command "node"
Require-Command "npm"

foreach ($dir in @(
    "docs\architecture", "docs\adr", "docs\api", "docs\database",
    "docs\security", "docs\operations", "docs\handover", "docs\brand",
    "infrastructure", ".github\workflows"
)) {
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
}

if (-not (Test-Path ".git")) {
    git init
    git branch -M main
}
git checkout -B develop

if (-not (Test-Path "backend\artisan")) {
    Write-Host "Creating Laravel backend..." -ForegroundColor Cyan
    composer create-project laravel/laravel backend
}

if (-not (Test-Path "frontend\package.json")) {
    Write-Host "Creating React + Vite + TypeScript frontend..." -ForegroundColor Cyan
    npm create vite@latest frontend -- --template react-ts
}

if (Test-Path "frontend\package.json") {
    Push-Location frontend
    npm install
    Pop-Location
}

@"
# Fouskee Concepts Management System

Enterprise-grade rice wholesale/retail management platform.

## Stack
- Laravel API
- MySQL
- React + Vite + TypeScript
- Git/GitHub
- PowerShell 7.6.5

## Engineering standards
- SOLID and Single Responsibility Principle
- Prefer source files <= 200 lines
- Domain-oriented modular monolith
- Strong typing
- Server-side authoritative business rules
- Automated tests and CI
- Immutable auditability for sensitive operations
- Continuous technical handover documentation

## Current milestone
Foundation v0.1
"@ | Set-Content -Encoding UTF8 "README.md"

@"
# Contributing

## Branches
- `main` = production/releasable history
- `develop` = integration branch
- `feature/*` = feature work

## Quality
Preserve SRP, clear domain boundaries, appropriate tests, and current technical documentation.
"@ | Set-Content -Encoding UTF8 "CONTRIBUTING.md"

@"
# Fouskee Concepts - Foundation v0.1 Handover

## Current state
- Repository initialized.
- `main` and `develop` branches established.
- Laravel backend scaffolded.
- React/Vite/TypeScript frontend scaffolded.
- Architecture and documentation structure established.
- Brand direction recorded.

## Next milestone
Identity, organization, authorization, Superadmin, and audit foundation.

## Handover policy
Documentation is updated at each architectural milestone and before production releases.
"@ | Set-Content -Encoding UTF8 "docs\handover\README.md"

Write-Host ""
Write-Host "Foundation scaffold complete." -ForegroundColor Green
Write-Host "Next: configure backend\.env for MySQL." -ForegroundColor Cyan
