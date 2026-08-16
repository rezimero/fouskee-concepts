param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts"
)

$ErrorActionPreference = "Stop"

function Assert-LastExitCode([string]$Step) {
    if ($LASTEXITCODE -ne 0) {
        throw "$Step failed with exit code $LASTEXITCODE."
    }
}

$ProjectPath = [IO.Path]::GetFullPath($ProjectPath)
Set-Location $ProjectPath

if (-not (Test-Path ".git")) {
    throw "No Git repository found at $ProjectPath."
}

if ((git branch --show-current) -ne "develop") {
    throw "Expected current branch to be 'develop'."
}

$name = git config --get user.name
$email = git config --get user.email

if ([string]::IsNullOrWhiteSpace($name) -or [string]::IsNullOrWhiteSpace($email)) {
    throw "Git user.name and user.email must be configured before committing."
}

Write-Host "Git identity: $name <$email>" -ForegroundColor Cyan

@"
* text=auto eol=lf

*.bat text eol=crlf
*.cmd text eol=crlf

*.png binary
*.jpg binary
*.jpeg binary
*.gif binary
*.ico binary
*.zip binary
"@ | Set-Content ".gitattributes" -Encoding utf8

@"
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
indent_style = space
indent_size = 4

[*.{json,yml,yaml,js,jsx,ts,tsx,css,scss,html}]
indent_size = 2

[*.md]
trim_trailing_whitespace = false

[*.ps1]
indent_size = 4
"@ | Set-Content ".editorconfig" -Encoding utf8

git add .gitattributes .editorconfig
Assert-LastExitCode "Staging repository standards"

git add --renormalize .
Assert-LastExitCode "Line-ending normalization"

$staged = @(git diff --cached --name-only)
$forbiddenPattern = '(^|/)\.env$|(^|/)node_modules/|(^|/)vendor/|\.zip$|\.pem$|\.p12$|\.pfx$|(^|/)database\.sqlite$'
$forbidden = @($staged | Where-Object { $_ -match $forbiddenPattern })

if ($forbidden.Count -gt 0) {
    Write-Host "Unsafe files are staged:" -ForegroundColor Red
    $forbidden | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
    throw "Commit aborted because unsafe/generated files are staged."
}

Write-Host "Secret/generated-file staging check passed." -ForegroundColor Green

Write-Host "Running Laravel tests..." -ForegroundColor Cyan
Push-Location "backend"
try {
    php artisan test
    Assert-LastExitCode "Laravel tests"
}
finally {
    Pop-Location
}

Write-Host "Running frontend lint..." -ForegroundColor Cyan
Push-Location "frontend"
try {
    npm run lint
    Assert-LastExitCode "Frontend lint"

    Write-Host "Running frontend production build..." -ForegroundColor Cyan
    npm run build
    Assert-LastExitCode "Frontend build"
}
finally {
    Pop-Location
}

git add .
Assert-LastExitCode "Final staging"

$forbidden = @(git diff --cached --name-only | Where-Object { $_ -match $forbiddenPattern })
if ($forbidden.Count -gt 0) {
    throw "Final staging check detected forbidden files."
}

Write-Host "Creating Foundation v0.1 commit..." -ForegroundColor Cyan
git commit -m "chore: establish Fouskee Concepts foundation"
Assert-LastExitCode "Foundation commit"

$mainExists = git branch --list main
if ([string]::IsNullOrWhiteSpace($mainExists)) {
    git branch main
    Assert-LastExitCode "Creating main branch"
}

Write-Host ""
Write-Host "Foundation v0.1 committed successfully." -ForegroundColor Green
Write-Host "Current branch: $(git branch --show-current)"
Write-Host "Commit: $(git rev-parse --short HEAD)"
Write-Host ""
git status --short
