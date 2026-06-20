$source = "c:\PROJECT\LARAVEL\StreamCart\BACKEND"
$dest = "c:\PROJECT\LARAVEL\StreamCart\cpanel_prep"
$zipPath = "c:\PROJECT\LARAVEL\StreamCart\BACKEND_CPANEL_READY.zip"

If (Test-Path $dest) { Remove-Item -Recurse -Force $dest }
If (Test-Path $zipPath) { Remove-Item -Force $zipPath }

New-Item -ItemType Directory -Path "$dest\core"
New-Item -ItemType Directory -Path "$dest\public_html"

Write-Host "Copying files (this might take a minute)..."
Get-ChildItem -Path $source -Exclude "public","node_modules",".git","tests" | Copy-Item -Destination "$dest\core" -Recurse

Write-Host "Copying public folder..."
Copy-Item -Path "$source\public\*" -Destination "$dest\public_html" -Recurse

Write-Host "Fixing index.php paths..."
$indexPath = "$dest\public_html\index.php"
(Get-Content $indexPath) -replace "require __DIR__.'/\.\./vendor/autoload\.php';", "require __DIR__.'/../core/vendor/autoload.php';" | Set-Content $indexPath
(Get-Content $indexPath) -replace "require_once __DIR__.'/\.\./bootstrap/app\.php';", "require_once __DIR__.'/../core/bootstrap/app.php';" | Set-Content $indexPath

Write-Host "Zipping to $zipPath ..."
Compress-Archive -Path "$dest\*" -DestinationPath $zipPath -Force

Write-Host "Cleanup..."
Remove-Item -Recurse -Force $dest

Write-Host "COMPLETED successfully!"
