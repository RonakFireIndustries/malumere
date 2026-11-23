[Console]::OutputEncoding = New-Object System.Text.UTF8Encoding($false)
$OutputEncoding = [Console]::OutputEncoding

$urls = @(
  'http://localhost/malumere/',
  'http://localhost/malumere/contact/',
  'http://localhost/malumere/treatment/injectables-facial-contouring/',
  'http://localhost/malumere/results/acne-treatment-case-reference/',
  'http://localhost/malumere/treatment-type/acne-acne-scars/'
)
foreach ($url in $urls) {
  try {
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30
    $h = $r.Content
    $title = [regex]::Match($h, '<title>(.*?)</title>', 'Singleline').Groups[1].Value.Trim()
    $desc = [regex]::Match($h, '<meta name="description" content="([^"]*)"', 'Singleline').Groups[1].Value
    $canon = [regex]::Match($h, '<link rel="canonical" href="([^"]*)"', 'Singleline').Groups[1].Value
    $robots = [regex]::Match($h, '<meta name="robots" content="([^"]*)"', 'Singleline').Groups[1].Value
    $h1 = [regex]::Match($h, '<h1[^>]*>(.*?)</h1>', 'Singleline').Groups[1].Value -replace '<[^>]+>','' -replace '\s+',' '
    $placeholders = [regex]::Matches($h, '\[(REAL, CONSENT-APPROVED|BEFORE/AFTER IMAGES REQUIRED|IMAGE REQUIRED)[^\]]*\]').Count
    Write-Output "URL $url STATUS $($r.StatusCode)"
    Write-Output "TITLE $title"
    Write-Output "DESC $desc"
    Write-Output "CANON $canon"
    if ($robots) { Write-Output "ROBOTS $robots" }
    Write-Output "H1 $($h1.Trim())"
    Write-Output "PLACEHOLDERS $placeholders"
  } catch {
    Write-Output "URL $url ERROR $($_.Exception.Message)"
  }
}