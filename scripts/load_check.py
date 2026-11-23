import concurrent.futures
import time
import urllib.request
import sys

base_url = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8080"
urls = [
    base_url + "/",
    base_url + "/index.php?page=catalog",
    base_url + "/index.php?page=catalog&category=Electricidad",
    base_url + "/index.php?page=catalog&min_rating=3&available_only=1",
]
latencies = []
errors = []

def request(url):
    started = time.perf_counter()
    try:
        with urllib.request.urlopen(url, timeout=10) as response:
            response.read()
            status = response.status
        elapsed = time.perf_counter() - started
        if status != 200:
            errors.append(status)
        return elapsed
    except Exception as exc:
        errors.append(type(exc).__name__)
        return time.perf_counter() - started

started_test = time.perf_counter()
while time.perf_counter() - started_test < 20:
    with concurrent.futures.ThreadPoolExecutor(max_workers=50) as pool:
        batch_urls = [urls[i % len(urls)] for i in range(50)]
        latencies.extend(pool.map(request, batch_urls))

latencies.sort()
p95 = latencies[max(0, int(len(latencies) * 0.95) - 1)]
error_rate = len(errors) / max(1, len(latencies)) * 100

print(f"Requests: {len(latencies)}")
print(f"Errors: {len(errors)}")
print(f"Error rate: {error_rate:.2f}%")
print(f"P95: {p95:.3f}s")
print("PASS" if p95 <= 2.0 and error_rate < 1.0 else "FAIL")

if p95 > 2.0 or error_rate >= 1.0:
    sys.exit(1)
