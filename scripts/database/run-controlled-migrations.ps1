param(
 [ValidateSet("Development","Test")][string]$Database="Development",
 [switch]$Status,[switch]$Pretend,
 [string]$ProjectPath="C:\Users\USER\FouskeeConcepts"
)
$ErrorActionPreference="Stop"
$db=if($Database -eq "Development"){"fouskee_concepts"}else{"fouskee_concepts_test"}
$secure=Read-Host "Migrator password for $db" -AsSecureString
$cred=[PSCredential]::new("fouskee_migrator",$secure)
$password=$cred.GetNetworkCredential().Password
$names=@("DB_CONNECTION","DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD")
$old=@{};foreach($n in $names){$old[$n]=(Get-Item "Env:\$n" -ErrorAction SilentlyContinue).Value}
try{
 $env:DB_CONNECTION="mysql";$env:DB_HOST="127.0.0.1";$env:DB_PORT="3306"
 $env:DB_DATABASE=$db;$env:DB_USERNAME="fouskee_migrator";$env:DB_PASSWORD=$password
 Push-Location "$ProjectPath\backend"
 try{
   if($Status){php artisan migrate:status}
   elseif($Pretend){php artisan migrate --pretend}
   else{php artisan migrate}
   if($LASTEXITCODE -ne 0){throw "Controlled migration command failed."}
 }finally{Pop-Location}
}finally{
 foreach($n in $names){
   if($null -eq $old[$n]){Remove-Item "Env:\$n" -ErrorAction SilentlyContinue}
   else{Set-Item "Env:\$n" $old[$n]}
 }
 $password=$null;$cred=$null;$secure=$null
}
