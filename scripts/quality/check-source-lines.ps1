param(
    [string]$ProjectPath = (Resolve-Path "$PSScriptRoot\..\..").Path,
    [int]$MaximumLines = 200
)

$ErrorActionPreference = "Stop"

$roots = @(
    "backend/app",
    "backend/database",
    "backend/tests",
    "frontend/src"
)

$extensions = @(
    ".php",
    ".ts",
    ".tsx",
    ".js",
    ".jsx"
)

$violations = @()

foreach ($relativeRoot in $roots) {
    $root = Join-Path $ProjectPath $relativeRoot

    if (-not (Test-Path $root)) {
        continue
    }

    Get-ChildItem $root -Recurse -File |
        Where-Object { $_.Extension -in $extensions } |
        ForEach-Object {
            $lineCount = (Get-Content $_.FullName).Count

            if ($lineCount -gt $MaximumLines) {
                $relative = [IO.Path]::GetRelativePath(
                    $ProjectPath,
                    $_.FullName
                )

                $violations += [PSCustomObject]@{
                    File = $relative
                    Lines = $lineCount
                }
            }
        }
}

if ($violations.Count -gt 0) {
    Write-Host "Source files over $MaximumLines lines:" -ForegroundColor Red
    $violations |
        Sort-Object Lines -Descending |
        Format-Table -AutoSize

    throw "Source-file line-count quality gate failed."
}

Write-Host "Source-file line-count gate passed." -ForegroundColor Green
