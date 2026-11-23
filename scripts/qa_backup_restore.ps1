param(
    [string]$DatabaseName = "oficioclick_backup_restore_test",
    [string]$ComposeDirectory = (Resolve-Path "$PSScriptRoot\..").Path
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"
Set-Location $ComposeDirectory

$tempSchema = $null

try {
    $create = "CREATE DATABASE IF NOT EXISTS $DatabaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $create | docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'

    $schema = Get-Content "database\schema.sql" -Raw
    $schema = $schema.Replace('CREATE DATABASE IF NOT EXISTS `oficioclick`', "CREATE DATABASE IF NOT EXISTS $DatabaseName")
    $schema = $schema.Replace('USE `oficioclick`;', "USE $DatabaseName;")

    $tempSchema = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($tempSchema, $schema, (New-Object System.Text.UTF8Encoding($false)))
    docker compose cp $tempSchema db:/tmp/oficioclick_restore_schema.sql
    docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" < /tmp/oficioclick_restore_schema.sql'

    docker compose cp "tests\fixtures\seed_pilot_100.sql" db:/tmp/seed_pilot_100.sql
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName < /tmp/seed_pilot_100.sql"

    $report = docker compose exec -T db sh -lc "mysql -N -B -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName -e 'SELECT COUNT(*) FROM usuario; SELECT COUNT(*) FROM servicio; SELECT COUNT(*) FROM reserva; SELECT COUNT(*) FROM calificacion;'"
    Write-Output $report

    docker compose exec -T db sh -lc "mysqldump --single-transaction -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName > /tmp/oficioclick_restore_backup.sql"

    $reset = "DROP DATABASE $DatabaseName; CREATE DATABASE $DatabaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` -e '$reset'"
    docker compose exec -T db sh -lc "mysql -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName < /tmp/oficioclick_restore_backup.sql"

    $restored = docker compose exec -T db sh -lc "mysql -N -B -uroot -p`"`$MYSQL_ROOT_PASSWORD`"` $DatabaseName -e 'SELECT COUNT(*) FROM usuario; SELECT COUNT(*) FROM servicio; SELECT COUNT(*) FROM reserva; SELECT COUNT(*) FROM calificacion;'"
    Write-Output "Restored:"
    Write-Output $restored
}
finally {
    $drop = "DROP DATABASE IF EXISTS $DatabaseName;"
    $drop | docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'
    if ($tempSchema -and (Test-Path $tempSchema)) {
        Remove-Item $tempSchema -ErrorAction SilentlyContinue
    }
}
