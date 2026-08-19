param(
 [string]$MySqlPath="C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
 [string]$ReportPath="C:\Users\USER\Downloads\fouskee-v0.4a-migration-authority.txt"
)
$ErrorActionPreference="Stop"
$Hosts=@("127.0.0.1","localhost")
$Databases=@("fouskee_concepts","fouskee_concepts_test")
$Desired=@("SELECT","INSERT","UPDATE","DELETE","CREATE","ALTER","DROP","INDEX","REFERENCES")

function Invoke-MySql{
 param([string]$Sql,[string]$User,[string]$HostName="localhost")
 $out=$Sql|& $MySqlPath --host=$HostName --user=$User --batch --skip-column-names 2>&1
 if($LASTEXITCODE -ne 0){throw (@($out)-join "`n")}
 return @($out)
}
function Plain([Security.SecureString]$Value){
 ([PSCredential]::new("x",$Value)).GetNetworkCredential().Password
}
function Report([string]$Message=""){
 Write-Host $Message
 Add-Content $ReportPath -Encoding UTF8 -Value $Message
}
function Assert-Runtime([string]$HostName){
 $a="'fouskee_app'@'$HostName'"
 $g=@(Invoke-MySql -Sql "SHOW GRANTS FOR $a;" -User root)
 $j=($g-join "`n").ToUpperInvariant()
 foreach($f in @("ALL PRIVILEGES","TRIGGER","SUPER","CREATE USER","GRANT OPTION","ROLE_ADMIN")){
   if($j.Contains($f)){throw "Runtime account $a is over-privileged."}
 }
 Report "PASS runtime boundary: $a"
}
function Assert-Migrator([string]$HostName){
 $q="CONCAT(CHAR(39),'fouskee_migrator',CHAR(39),'@',CHAR(39),'$HostName',CHAR(39))"
 $global=@(Invoke-MySql -User root -Sql "SELECT PRIVILEGE_TYPE FROM information_schema.USER_PRIVILEGES WHERE GRANTEE=$q AND PRIVILEGE_TYPE<>'USAGE';")
 if($global.Count){throw "Migrator has unexpected global privileges."}
 $roles=@(Invoke-MySql -User root -Sql "SELECT FROM_USER FROM mysql.role_edges WHERE TO_USER='fouskee_migrator' AND TO_HOST='$HostName';")
 if($roles.Count){throw "Migrator must not have roles."}
 foreach($db in $Databases){
   $actual=@(Invoke-MySql -User root -Sql "SELECT PRIVILEGE_TYPE FROM information_schema.SCHEMA_PRIVILEGES WHERE GRANTEE=$q AND TABLE_SCHEMA='$db' ORDER BY PRIVILEGE_TYPE;")
   $diff=Compare-Object ($Desired|Sort-Object) ($actual|Sort-Object)
   if($null -ne $diff){throw "Unexpected migrator privileges on $db."}
 }
 Report "PASS migrator grants: fouskee_migrator@$HostName"
}

if(-not (Test-Path $MySqlPath)){throw "MySQL client not found."}
Set-Content $ReportPath -Encoding UTF8 -Value @(
 "Fouskee Concepts - v0.4A Migration Authority",
 "Generated: $((Get-Date).ToString('o'))",""
)
$rootS=Read-Host "MySQL root password" -AsSecureString
$migS=Read-Host "Migrator password" -AsSecureString
$confirmS=Read-Host "Confirm migrator password" -AsSecureString
$root=Plain $rootS;$mig=Plain $migS;$confirm=Plain $confirmS
if($mig -ne $confirm){throw "Migrator password confirmation mismatch."}

try{
 $env:MYSQL_PWD=$root
 foreach($h in $Hosts){Assert-Runtime $h}
 $escaped=$mig.Replace("\","\\").Replace("'","''")
 foreach($h in $Hosts){
   $a="'fouskee_migrator'@'$h'"
   $sql=@"
CREATE USER IF NOT EXISTS $a IDENTIFIED BY '$escaped';
ALTER USER $a IDENTIFIED BY '$escaped';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM $a;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON fouskee_concepts.* TO $a;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON fouskee_concepts_test.* TO $a;
"@
   Invoke-MySql -Sql $sql -User root|Out-Null
   Assert-Migrator $h
 }
}finally{
 Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
 $root=$null;$confirm=$null
}

try{
 $env:MYSQL_PWD=$mig
 foreach($h in $Hosts){
   $who=@(Invoke-MySql -Sql "SELECT CURRENT_USER();" -User fouskee_migrator -HostName $h)
   Report "PASS migrator login $h => $($who[0])"
 }
}finally{
 Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
 $mig=$null
}
Report ""
Report "MIGRATION AUTHORITY: PASS"
Report "Runtime fouskee_app remains CRUD-only."
Report "Migrator has DML plus reviewed schema-change privileges only."
Report "TRIGGER and server administration remain excluded."
