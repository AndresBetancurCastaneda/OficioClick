param(
    [string]$DatabaseName = "oficioclick_backup_restore_test",
    [string]$ComposeDirectory = (Join-Path $PSScriptRoot '..'),
    [string]$SourceDirectory = (Join-Path $PSScriptRoot '..')
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"
$ComposeDirectory = (Resolve-Path $ComposeDirectory).Path
$SourceDirectory = (Resolve-Path $SourceDirectory).Path
Set-Location $ComposeDirectory

$DatabaseName = $DatabaseName.Trim()
if ($DatabaseName -notmatch '^oficioclick_backup_restore_[A-Za-z0-9_]+$') {
    throw "DatabaseName must use the disposable oficioclick_backup_restore_ prefix."
}

$runId = [Guid]::NewGuid().ToString('N')
$schemaContainerPath = "/tmp/oficioclick_restore_schema_$runId.sql"
$seedContainerPath = "/tmp/oficioclick_restore_seed_$runId.sql"
$backupContainerPath = "/tmp/oficioclick_restore_backup_$runId.sql"
$checkContainerPath = "/tmp/oficioclick_restore_check_$runId.sql"
$countsContainerPath = "/tmp/oficioclick_restore_counts_$runId.sql"
$resetContainerPath = "/tmp/oficioclick_restore_reset_$runId.sql"
$tempSchema = $null
$preflightSql = $null
$countsSql = $null
$resetSql = $null
$databaseCreated = $false
$preflightCopied = $false

try {
    $preflightSql = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText(
        $preflightSql,
        "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$DatabaseName';",
        (New-Object System.Text.UTF8Encoding($false))
    )
    docker compose cp $preflightSql "db:$checkContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not copy the disposable database preflight query."
    }
    $preflightCopied = $true

    $databaseExists = docker compose exec -T db sh -lc "mysql -N -B -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` < $checkContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not check whether the disposable database already exists."
    }
    if ([string]$databaseExists -ne "0") {
        throw "Refusing to overwrite the existing database '$DatabaseName'. Choose an unused disposable name."
    }
    $create = "CREATE DATABASE $DatabaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $create | docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
    if ($LASTEXITCODE -ne 0) {
        throw "Could not create the disposable database."
    }
    $databaseCreated = $true

    $schemaPath = Join-Path (Join-Path $SourceDirectory 'database') 'schema.sql'
    $seedPath = Join-Path (Join-Path (Join-Path $SourceDirectory 'tests') 'fixtures') 'seed_pilot_100.sql'
    $schema = Get-Content $schemaPath -Raw
    $schema = $schema.Replace('CREATE DATABASE IF NOT EXISTS `oficioclick`', "CREATE DATABASE IF NOT EXISTS $DatabaseName")
    $schema = $schema.Replace('USE `oficioclick`;', "USE $DatabaseName;")

    $tempSchema = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($tempSchema, $schema, (New-Object System.Text.UTF8Encoding($false)))
    docker compose cp $tempSchema "db:$schemaContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not copy the disposable schema into the database container."
    }
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` < $schemaContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not initialize the disposable schema."
    }

    docker compose cp $seedPath "db:$seedContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not copy the QA fixture into the database container."
    }
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName < $seedContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not load the QA fixture."
    }

    $countsQuery = "SELECT (SELECT COUNT(*) FROM usuario), (SELECT COUNT(*) FROM servicio), (SELECT COUNT(*) FROM reserva), (SELECT COUNT(*) FROM calificacion);"
    $countsSql = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($countsSql, $countsQuery, (New-Object System.Text.UTF8Encoding($false)))
    docker compose cp $countsSql "db:$countsContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not copy the disposable count query."
    }
    $report = docker compose exec -T db sh -lc "mysql -N -B -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName < $countsContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not verify the QA fixture counts."
    }
    $expectedCounts = "100`t50`t30`t20"
    if ([string]$report -ne $expectedCounts) {
        throw "Unexpected QA fixture counts: $report"
    }
    Write-Output "Seeded: $report"

    docker compose exec -T db sh -lc "mysqldump --single-transaction -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName > $backupContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not create the disposable database backup."
    }

    $resetSql = [System.IO.Path]::GetTempFileName()
    $reset = "DROP DATABASE $DatabaseName; CREATE DATABASE $DatabaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    [System.IO.File]::WriteAllText($resetSql, $reset, (New-Object System.Text.UTF8Encoding($false)))
    docker compose cp $resetSql "db:$resetContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not copy the disposable database reset query."
    }
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` < $resetContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not recreate the disposable database for restore."
    }
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName < $backupContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not restore the disposable database backup."
    }

    $restored = docker compose exec -T db sh -lc "mysql -N -B -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName < $countsContainerPath"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not verify the restored database counts."
    }
    if ([string]$restored -ne $expectedCounts) {
        throw "Restored database counts differ from the seeded database: $restored"
    }
    Write-Output "Restored: $restored"
}
finally {
    if ($databaseCreated) {
        $drop = "DROP DATABASE IF EXISTS $DatabaseName;"
        $drop | docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
        if ($LASTEXITCODE -ne 0) {
            throw "Could not remove the disposable database '$DatabaseName'."
        }
    }
    if ($preflightCopied) {
        docker compose exec -T db rm -f $schemaContainerPath $seedContainerPath $backupContainerPath $checkContainerPath $countsContainerPath $resetContainerPath
        if ($LASTEXITCODE -ne 0) {
            throw "Could not clean up temporary files in the database container."
        }
    }
    if ($tempSchema -and (Test-Path $tempSchema)) {
        Remove-Item $tempSchema -ErrorAction SilentlyContinue
    }
    if ($preflightSql -and (Test-Path $preflightSql)) {
        Remove-Item $preflightSql -ErrorAction SilentlyContinue
    }
    if ($countsSql -and (Test-Path $countsSql)) {
        Remove-Item $countsSql -ErrorAction SilentlyContinue
    }
    if ($resetSql -and (Test-Path $resetSql)) {
        Remove-Item $resetSql -ErrorAction SilentlyContinue
    }
}
