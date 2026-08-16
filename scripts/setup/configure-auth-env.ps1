param(
    [string]$ProjectPath = "C:\Users\USER\FouskeeConcepts"
)

$ErrorActionPreference = "Stop"

function Set-EnvValue(
    [string]$Path,
    [string]$Name,
    [string]$Value
) {
    $content = ''

    if (Test-Path $Path) {
        $content = Get-Content $Path -Raw
    }

    $escaped = [Regex]::Escape($Name)

    if ($content -match "(?m)^$escaped=") {
        $content = [Regex]::Replace(
            $content,
            "(?m)^$escaped=.*$",
            "$Name=$Value"
        )
    }
    else {
        $content = $content.TrimEnd() + "`n$Name=$Value`n"
    }

    $content = $content -replace "`r`n", "`n"
    $content = $content.TrimEnd([char[]]"`r`n") + "`n"

    $utf8 = New-Object System.Text.UTF8Encoding($false)
    [IO.File]::WriteAllText($Path, $content, $utf8)
}

$ProjectPath = [IO.Path]::GetFullPath($ProjectPath)
$backendEnv = Join-Path $ProjectPath "backend\.env"
$backendExample = Join-Path $ProjectPath "backend\.env.example"
$frontendLocal = Join-Path $ProjectPath "frontend\.env.local"

if (-not (Test-Path $backendEnv)) {
    throw "backend\.env was not found."
}

$statefulDomains = @(
    'localhost:5173',
    'localhost:8000',
    '127.0.0.1:5173',
    '127.0.0.1:8000'
) -join ','

foreach ($path in @($backendEnv, $backendExample)) {
    Set-EnvValue $path 'FRONTEND_URL' 'http://localhost:5173'
    Set-EnvValue $path 'SANCTUM_STATEFUL_DOMAINS' $statefulDomains
    Set-EnvValue $path 'SESSION_DOMAIN' 'localhost'
}

Set-EnvValue $frontendLocal 'VITE_API_URL' 'http://localhost:8000'

Write-Host "Local SPA authentication environment configured." -ForegroundColor Green
