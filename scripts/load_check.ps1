param(
    [string]$BaseUrl = "http://localhost:8080",
    [int]$RequestCount = 200
)

$urls = @(
    "$BaseUrl/",
    "$BaseUrl/index.php?page=catalog",
    "$BaseUrl/index.php?page=catalog&category=Electricidad",
    "$BaseUrl/index.php?page=catalog&min_rating=3&available_only=1"
)

$jobs = @()
for ($i = 0; $i -lt $RequestCount; $i++) {
    $url = $urls[$i % $urls.Count]
    $jobs += Start-Job -ScriptBlock {
        param($requestUrl)
        $timer = [System.Diagnostics.Stopwatch]::StartNew()
        try {
            $response = Invoke-WebRequest -Uri $requestUrl -UseBasicParsing -TimeoutSec 10
            [PSCustomObject]@{
                Milliseconds = $timer.Elapsed.TotalMilliseconds
                Success = ($response.StatusCode -eq 200)
            }
        }
        catch {
            [PSCustomObject]@{
                Milliseconds = $timer.Elapsed.TotalMilliseconds
                Success = $false
            }
        }
    } -ArgumentList $url
}

$results = $jobs | Wait-Job | Receive-Job
$jobs | Remove-Job
$latencies = @($results.Milliseconds | Sort-Object)
$errors = @($results | Where-Object { -not $_.Success }).Count
$errorRate = [Math]::Round(($errors / [Math]::Max(1, $results.Count)) * 100, 2)
$p95Index = [Math]::Max(0, [Math]::Floor($latencies.Count * 0.95) - 1)
$p95 = $latencies[$p95Index]

Write-Output "Requests: $($results.Count)"
Write-Output "Errors: $errors"
Write-Output "Error rate: $errorRate%"
Write-Output "P95: $([Math]::Round($p95 / 1000, 3))s"

if ($p95 -le 2000 -and $errorRate -lt 1) {
    Write-Output "PASS"
}
else {
    Write-Output "FAIL"
    exit 1
}
