param(
    [string] $BaseUrl = "http://zaha-core-orbit.test",
    [string] $Email = "admin@zaha-center.org",
    [string] $Password = "password"
)

$ErrorActionPreference = "Stop"

$chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
if (-not (Test-Path -LiteralPath $chromePath)) {
    $chromePath = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
}
if (-not (Test-Path -LiteralPath $chromePath)) {
    throw "Chrome or Edge executable was not found."
}

$auditRoot = Join-Path (Get-Location) "storage\app\responsive-audit"
$screenshotRoot = Join-Path $auditRoot "screenshots"
$profileDir = Join-Path $env:TEMP ("zaha-responsive-chrome-" + [guid]::NewGuid().ToString("N"))
New-Item -ItemType Directory -Force -Path $screenshotRoot, $profileDir | Out-Null

$routes = @(
    @{ slug = "login"; path = "/login" },
    @{ slug = "dashboard"; path = "/dashboard" },
    @{ slug = "profile"; path = "/profile" },
    @{ slug = "directory-users"; path = "/directory/users" },
    @{ slug = "admin-users"; path = "/dashboard/admin/users" },
    @{ slug = "admin-roles"; path = "/dashboard/admin/roles" },
    @{ slug = "admin-workflows"; path = "/dashboard/admin/workflows" },
    @{ slug = "admin-branches"; path = "/dashboard/admin/branches" },
    @{ slug = "admin-approvals"; path = "/dashboard/admin/approvals" },
    @{ slug = "admin-events-lookups"; path = "/dashboard/admin/events-lookups" },
    @{ slug = "agenda-index"; path = "/dashboard/relations/agenda" },
    @{ slug = "agenda-create"; path = "/dashboard/relations/agenda/create" },
    @{ slug = "agenda-approvals"; path = "/dashboard/relations/agenda/approvals" },
    @{ slug = "monthly-index"; path = "/dashboard/relations/monthly-activities" },
    @{ slug = "monthly-create"; path = "/dashboard/relations/monthly-activities/create" },
    @{ slug = "monthly-approvals"; path = "/dashboard/programs/monthly-activities/approvals" },
    @{ slug = "workshops-requests"; path = "/dashboard/programs/workshops-requests" },
    @{ slug = "communications-requests"; path = "/dashboard/programs/communications-requests" },
    @{ slug = "finance-donations"; path = "/dashboard/finance/donations" },
    @{ slug = "finance-donations-create"; path = "/dashboard/finance/donations/create" },
    @{ slug = "finance-bookings"; path = "/dashboard/finance/bookings" },
    @{ slug = "finance-zaha-time"; path = "/dashboard/finance/zaha-time" },
    @{ slug = "finance-payments"; path = "/dashboard/finance/payments" },
    @{ slug = "maintenance-requests"; path = "/dashboard/maintenance/requests" },
    @{ slug = "maintenance-create"; path = "/dashboard/maintenance/requests/create" },
    @{ slug = "maintenance-approvals"; path = "/dashboard/maintenance/approvals" },
    @{ slug = "transport-vehicles"; path = "/dashboard/transport/vehicles" },
    @{ slug = "transport-vehicles-create"; path = "/dashboard/transport/vehicles/create" },
    @{ slug = "transport-drivers"; path = "/dashboard/transport/drivers" },
    @{ slug = "transport-trips"; path = "/dashboard/transport/trips" },
    @{ slug = "transport-movements"; path = "/dashboard/transport/movements" },
    @{ slug = "reports-overview"; path = "/dashboard/reports/overview" },
    @{ slug = "reports-agenda"; path = "/dashboard/reports/agenda" },
    @{ slug = "reports-monthly"; path = "/dashboard/reports/monthly" },
    @{ slug = "reports-finance"; path = "/dashboard/reports/finance" },
    @{ slug = "reports-maintenance"; path = "/dashboard/reports/maintenance" },
    @{ slug = "reports-transport"; path = "/dashboard/reports/transport" },
    @{ slug = "reports-kpis"; path = "/dashboard/reports/kpis" },
    @{ slug = "enterprise-dashboard"; path = "/dashboard/enterprise" },
    @{ slug = "enterprise-annual-planning"; path = "/dashboard/enterprise/annual-planning" },
    @{ slug = "enterprise-branch-performance"; path = "/dashboard/reports/enterprise/branch-performance" }
)

$viewports = @(
    @{ slug = "mobile"; width = 390; height = 844; scale = 1 },
    @{ slug = "tablet"; width = 768; height = 1024; scale = 1 },
    @{ slug = "desktop"; width = 1440; height = 1000; scale = 1 }
)

$port = Get-Random -Minimum 9300 -Maximum 9700
$chromeArgs = @(
    "--headless=new",
    "--remote-debugging-port=$port",
    "--user-data-dir=$profileDir",
    "--disable-gpu",
    "--no-first-run",
    "--no-default-browser-check",
    "--window-size=1440,1000",
    "about:blank"
)
$chrome = Start-Process -FilePath $chromePath -ArgumentList $chromeArgs -PassThru -WindowStyle Hidden

function Wait-ForChrome {
    param([int] $Port)
    for ($i = 0; $i -lt 60; $i++) {
        try {
            return Invoke-RestMethod -Uri "http://127.0.0.1:$Port/json/version" -UseBasicParsing
        } catch {
            Start-Sleep -Milliseconds 250
        }
    }
    throw "Chrome DevTools endpoint did not start."
}

$version = Wait-ForChrome -Port $port
$target = $null
try {
    $target = Invoke-RestMethod -Method Put -Uri "http://127.0.0.1:$port/json/new?about:blank" -UseBasicParsing
} catch {
    $targets = Invoke-RestMethod -Uri "http://127.0.0.1:$port/json" -UseBasicParsing
    $target = @($targets | Where-Object { $_.type -eq "page" } | Select-Object -First 1)[0]
}
if (-not $target.webSocketDebuggerUrl) {
    throw "Chrome page target was not available."
}
$ws = [System.Net.WebSockets.ClientWebSocket]::new()
$ws.ConnectAsync([uri] $target.webSocketDebuggerUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
$script:cdpId = 0

function Receive-CdpMessage {
    $buffer = New-Object byte[] 1048576
    $segment = [ArraySegment[byte]]::new($buffer)
    $builder = New-Object System.Text.StringBuilder
    do {
        $result = $ws.ReceiveAsync($segment, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
        if ($result.Count -gt 0) {
            [void] $builder.Append([System.Text.Encoding]::UTF8.GetString($buffer, 0, $result.Count))
        }
    } while (-not $result.EndOfMessage)
    return $builder.ToString() | ConvertFrom-Json
}

function Send-Cdp {
    param(
        [string] $Method,
        [hashtable] $Params = @{}
    )
    $script:cdpId++
    $id = $script:cdpId
    $payload = @{ id = $id; method = $Method; params = $Params } | ConvertTo-Json -Depth 20 -Compress
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($payload)
    $ws.SendAsync([ArraySegment[byte]]::new($bytes), [System.Net.WebSockets.WebSocketMessageType]::Text, $true, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    while ($true) {
        $message = Receive-CdpMessage
        if ($message.id -eq $id) {
            if ($message.error) {
                throw ($message.error | ConvertTo-Json -Depth 10)
            }
            return $message.result
        }
    }
}

function Invoke-ExpressionInPage {
    param([string] $Expression)
    return Send-Cdp "Runtime.evaluate" @{
        expression = $Expression
        awaitPromise = $true
        returnByValue = $true
    }
}

function Navigate-To {
    param([string] $Url)
    Send-Cdp "Page.navigate" @{ url = $Url } | Out-Null
    Start-Sleep -Milliseconds 800
}

try {
    Send-Cdp "Page.enable" | Out-Null
    Send-Cdp "Runtime.enable" | Out-Null
    Send-Cdp "DOM.enable" | Out-Null

    Navigate-To "$BaseUrl/login"
    $loginExpression = @"
(() => {
  const email = document.querySelector('input[name="email"]');
  const password = document.querySelector('input[name="password"]');
  const form = document.querySelector('form.auth-login-form');
  email.value = '$Email';
  password.value = '$Password';
  email.dispatchEvent(new Event('input', { bubbles: true }));
  password.dispatchEvent(new Event('input', { bubbles: true }));
  form.action = location.origin + '/login';
  form.submit();
  return true;
})()
"@
    Invoke-ExpressionInPage $loginExpression | Out-Null
    Start-Sleep -Seconds 2

    $results = New-Object System.Collections.Generic.List[object]
    foreach ($viewport in $viewports) {
        Send-Cdp "Emulation.setDeviceMetricsOverride" @{
            width = $viewport.width
            height = $viewport.height
            deviceScaleFactor = $viewport.scale
            mobile = ($viewport.slug -eq "mobile")
        } | Out-Null

        foreach ($route in $routes) {
            $url = $BaseUrl.TrimEnd("/") + $route.path
            Navigate-To $url
            $metricsExpression = @"
(() => {
  const root = document.documentElement;
  const body = document.body;
  const maxWidth = Math.max(root.scrollWidth, body ? body.scrollWidth : 0);
  const offenders = Array.from(document.querySelectorAll('body *')).map((el) => {
    const rect = el.getBoundingClientRect();
    return { tag: el.tagName, cls: String(el.className || '').slice(0, 100), id: el.id || '', left: Math.round(rect.left), right: Math.round(rect.right), width: Math.round(rect.width) };
  }).filter((item) => item.width > 0 && (item.right > window.innerWidth + 2 || item.left < -2)).slice(0, 12);
  return {
    url: location.href,
    title: document.title,
    viewportWidth: window.innerWidth,
    clientWidth: root.clientWidth,
    scrollWidth: maxWidth,
    overflowX: maxWidth > root.clientWidth + 2,
    h1: document.querySelector('h1,h2,.page-header-title,.card-title')?.textContent?.trim() || '',
    offenders
  };
})()
"@
            $metrics = (Invoke-ExpressionInPage $metricsExpression).result.value
            $screenshot = Send-Cdp "Page.captureScreenshot" @{ format = "png"; fromSurface = $true }
            $fileName = "{0}-{1}.png" -f $route.slug, $viewport.slug
            $filePath = Join-Path $screenshotRoot $fileName
            [IO.File]::WriteAllBytes($filePath, [Convert]::FromBase64String($screenshot.data))
            $results.Add([pscustomobject]@{
                route = $route.slug
                path = $route.path
                viewport = $viewport.slug
                width = $viewport.width
                height = $viewport.height
                finalUrl = $metrics.url
                title = $metrics.title
                heading = $metrics.h1
                clientWidth = $metrics.clientWidth
                scrollWidth = $metrics.scrollWidth
                overflowX = $metrics.overflowX
                offenders = $metrics.offenders
                screenshot = $filePath
            })
        }
    }

    $jsonPath = Join-Path $auditRoot "responsive-results.json"
    $results | ConvertTo-Json -Depth 10 | Set-Content -Path $jsonPath -Encoding UTF8
    $summaryPath = Join-Path $auditRoot "responsive-summary.txt"
    $overflow = @($results | Where-Object { $_.overflowX })
    @(
        "Responsive audit finished: $(Get-Date -Format s)",
        "Base URL: $BaseUrl",
        "Routes checked: $($routes.Count)",
        "Viewports checked: $($viewports.Count)",
        "Screenshots: $screenshotRoot",
        "Overflow count: $($overflow.Count)",
        "",
        ($overflow | ForEach-Object { "{0} [{1}] scroll={2} client={3} screenshot={4}" -f $_.path, $_.viewport, $_.scrollWidth, $_.clientWidth, $_.screenshot })
    ) | Set-Content -Path $summaryPath -Encoding UTF8

    Write-Output "RESULTS_JSON=$jsonPath"
    Write-Output "SUMMARY=$summaryPath"
    Write-Output "SCREENSHOTS=$screenshotRoot"
    Write-Output "OVERFLOW_COUNT=$($overflow.Count)"
} finally {
    if ($ws.State -eq [System.Net.WebSockets.WebSocketState]::Open) {
        $ws.CloseAsync([System.Net.WebSockets.WebSocketCloseStatus]::NormalClosure, "done", [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    }
    if ($chrome -and -not $chrome.HasExited) {
        Stop-Process -Id $chrome.Id -Force
    }
    Remove-Item -LiteralPath $profileDir -Recurse -Force -ErrorAction SilentlyContinue
}
