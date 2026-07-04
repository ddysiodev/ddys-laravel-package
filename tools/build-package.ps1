param(
    [string] $Version = "0.1.0"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$Dist = Join-Path $Root "dist"
$Zip = Join-Path $Dist ("ddys-laravel-package-v{0}.zip" -f $Version)
$Prefix = "ddys-laravel-package"

if (-not (Test-Path (Join-Path $Root "composer.json"))) {
    throw "Missing composer.json."
}

$resolvedRoot = [System.IO.Path]::GetFullPath($Root)
$resolvedDist = [System.IO.Path]::GetFullPath($Dist)
if (-not $resolvedDist.StartsWith($resolvedRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Refusing to clean a dist path outside the project."
}

if (Test-Path $Dist) {
    Remove-Item -Path $Dist -Recurse -Force
}

New-Item -ItemType Directory -Force -Path $Dist | Out-Null
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$archive = [System.IO.Compression.ZipFile]::Open($Zip, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -LiteralPath $Root -Recurse -File | Where-Object {
        $_.FullName -notlike "$Dist*" -and
        $_.FullName -notmatch '\\(vendor|node_modules|\.git)\\'
    } | ForEach-Object {
        $fullName = [System.IO.Path]::GetFullPath($_.FullName)
        if (-not $fullName.StartsWith($resolvedRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "Refusing to package a file outside the project."
        }
        $relative = $fullName.Substring($resolvedRoot.Length).TrimStart("\", "/").Replace("\", "/")
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, ($Prefix + "/" + $relative)) | Out-Null
    }
}
finally {
    $archive.Dispose()
}

Write-Host $Zip

