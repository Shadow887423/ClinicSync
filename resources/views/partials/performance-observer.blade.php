{{-- Genuine Browser Web Vitals Instrumentation --}}
<script>
(function() {
    if (!('performance' in window) || !('timing' in window.performance)) return;

    let fcp = null, lcp = null, cls = 0;

    // Observe Paint Timing (FCP)
    try {
        const paintObserver = new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                if (entry.name === 'first-contentful-paint') {
                    fcp = Math.round(entry.startTime);
                }
            }
        });
        paintObserver.observe({ type: 'paint', buffered: true });
    } catch (e) {}

    // Observe Largest Contentful Paint (LCP)
    try {
        const lcpObserver = new PerformanceObserver((entryList) => {
            const entries = entryList.getEntries();
            if (entries.length > 0) {
                lcp = Math.round(entries[entries.length - 1].startTime);
            }
        });
        lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) {}

    // Observe Layout Shift (CLS)
    try {
        const clsObserver = new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                if (!entry.hadRecentInput) {
                    cls += entry.value;
                }
            }
        });
        clsObserver.observe({ type: 'layout-shift', buffered: true });
    } catch (e) {}

    // Send metrics once window is fully loaded
    window.addEventListener('load', function() {
        setTimeout(function() {
            try {
                const nav = performance.getEntriesByType('navigation')[0] || performance.timing;
                const ttfb = nav.responseStart ? Math.round(nav.responseStart - (nav.requestStart || nav.fetchStart || 0)) : null;
                const domLoaded = nav.domContentLoadedEventEnd ? Math.round(nav.domContentLoadedEventEnd - (nav.startTime || nav.navigationStart || 0)) : null;
                const fullyLoaded = nav.loadEventEnd ? Math.round(nav.loadEventEnd - (nav.startTime || nav.navigationStart || 0)) : Math.round(performance.now());

                const payload = {
                    url: window.location.pathname,
                    fcp: fcp || ttfb,
                    lcp: lcp || fcp || ttfb,
                    cls: Math.round(cls * 1000) / 1000,
                    ttfb: Math.max(0, ttfb || 0),
                    dom_loaded: Math.max(0, domLoaded || 0),
                    fully_loaded: Math.max(0, fullyLoaded || 0),
                    response_time_ms: Math.max(0, ttfb || 0)
                };

                const data = JSON.stringify(payload);
                if (navigator.sendBeacon) {
                    const blob = new Blob([data], { type: 'application/json' });
                    navigator.sendBeacon('/api/monitoring/vitals', blob);
                } else {
                    fetch('/api/monitoring/vitals', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: data,
                        keepalive: true
                    }).catch(function() {});
                }
            } catch (err) {}
        }, 500);
    });
})();
</script>
