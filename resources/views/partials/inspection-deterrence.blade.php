@php
    $securityProtection = app(\App\Services\SecurityProtectionService::class);
@endphp

@if($securityProtection->isDeterrenceActive())
    @php
        $secConfig = $securityProtection->getSettings();
    @endphp
    <script>
        window.__CLINICSYNC_SECURITY__ = {
            disableContextMenu: {{ $secConfig['disable_context_menu'] ? 'true' : 'false' }},
            blockShortcuts: {{ $secConfig['block_shortcuts'] ? 'true' : 'false' }},
            detectDevTools: {{ $secConfig['detect_devtools'] ? 'true' : 'false' }},
            showWarning: {{ $secConfig['show_warning'] ? 'true' : 'false' }},
            isAdmin: {{ Auth::check() && Auth::user()->isAdmin() ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset('js/inspection-deterrence.min.js') }}" defer></script>
@endif
