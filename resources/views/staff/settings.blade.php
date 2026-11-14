@extends('layouts.dashboard')

@section('title', 'SMS Gateway & System Settings')
@section('page-title', 'SMS Gateway & Automation Configuration')

@section('content')
<div class="row g-4">
    <!-- Left Column: Status & Test Dispatcher -->
    <div class="col-lg-6">
        <!-- Gateway Status Card -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-broadcast text-teal me-2" style="color: var(--secondary-teal);"></i> Gateway Status & Mode
            </h5>

            <div class="p-3 bg-light rounded-3 mb-4 border">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted small fw-bold text-uppercase">Configured Provider</div>
                        <div class="h5 fw-bold text-dark mb-0">{{ strtoupper($gateway) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-bold text-uppercase">Simulation Mode</div>
                        <div>
                            @if($isSimulation || $gateway === 'simulated')
                                <span class="badge bg-info text-dark">Simulated Testbed (Safe Mode)</span>
                            @else
                                <span class="badge bg-success">Live Network API</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-bold text-uppercase">Lead Reminder Window</div>
                        <div class="h6 fw-bold text-dark mb-0">{{ $reminderHours }} Hours Prior</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small fw-bold text-uppercase">Queue Runner</div>
                        <div class="badge bg-dark">database / sync</div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-2 text-center small mb-3">
                <div class="col-4 p-2 bg-light rounded border">
                    <div class="fw-bold fs-5 text-success">{{ $stats['total_sent'] }}</div>
                    <div class="text-muted">Dispatched</div>
                </div>
                <div class="col-4 p-2 bg-light rounded border">
                    <div class="fw-bold fs-5 text-info">{{ $stats['total_simulated'] }}</div>
                    <div class="text-muted">Simulated Logs</div>
                </div>
                <div class="col-4 p-2 bg-light rounded border">
                    <div class="fw-bold fs-5 text-danger">{{ $stats['total_failed'] }}</div>
                    <div class="text-muted">Failed Delivery</div>
                </div>
            </div>
        </div>

        <!-- Live SMS Test Console -->
        <div class="card card-custom p-4">
            <h5 class="fw-bold text-dark mb-2">
                <i class="bi bi-send-check text-primary me-2"></i> Live SMS Diagnostic Console
            </h5>
            <p class="text-muted small mb-3">
                Send a test text message to verify that your active SMS provider (or simulated testbed) is operating normally.
            </p>

            <form action="{{ route('staff.test-sms') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold">Recipient Mobile Phone <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="+15551234567 or 09171234567" value="{{ old('phone', '+15551234567') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Test Message Body (Max 160 chars) <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="3" maxlength="160" required>{{ old('message', 'CLINICSYNC Diagnostic Test: Automated SMS Gateway is operating normally.') }}</textarea>
                </div>

                <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                    <i class="bi bi-send me-1"></i> Dispatch Test SMS
                </button>
            </form>
        </div>

        <!-- Shelex Free OTP Live Inspector Card -->
        <div class="card card-custom p-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-shield-check text-success me-2"></i> Free OTP & Inbound SMS Live Tool
                </h5>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Shelex API</span>
            </div>
            <p class="text-muted small mb-3">
                Fetch available public test phone numbers from <a href="https://otp-api.shelex.dev/api/" target="_blank" class="text-teal">otp-api.shelex.dev</a> to test incoming OTPs & SMS without commercial carrier fees.
            </p>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label class="form-label small fw-bold">1. Available Countries</label>
                    <div class="input-group input-group-sm">
                        <select id="otpCountrySelect" class="form-select">
                            <option value="">-- Click Load Countries --</option>
                        </select>
                        <button type="button" class="btn btn-outline-teal" id="btnLoadCountries" onclick="loadOtpCountries()">
                            <i class="bi bi-arrow-clockwise"></i> Load
                        </button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-bold">2. Phone Numbers</label>
                    <select id="otpNumberSelect" class="form-select form-select-sm" disabled onchange="onOtpNumberSelected()">
                        <option value="">-- Select country first --</option>
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btnCopyOtpPhone" disabled onclick="copyOtpNumber()">
                    <i class="bi bi-clipboard me-1"></i> Use for Test SMS
                </button>
                <button type="button" class="btn btn-sm btn-teal rounded-pill" id="btnCheckOtpCode" disabled onclick="checkOtpCode()">
                    <i class="bi bi-search me-1"></i> Check Incoming OTP / SMS
                </button>
            </div>

            <div id="otpResultBox" class="p-3 bg-light rounded-3 border d-none small font-monospace">
                <div class="fw-bold text-dark mb-1 d-flex justify-content-between">
                    <span><i class="bi bi-chat-text text-teal me-1"></i> Received Message:</span>
                    <span id="otpStatusBadge" class="badge bg-info"></span>
                </div>
                <div id="otpResultContent" class="text-muted">Waiting for query...</div>
            </div>
        </div>
    </div>

    <!-- Right Column: Setup & Environment Instructions -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-code-square text-dark me-2"></i> SMS Gateway Provider Configuration</h5>
            
            <p class="small text-muted mb-3">
                To connect a real SMS gateway provider, update the environment variables in your <code>.env</code> file. The application securely reads credentials from the environment and does not store plaintext API tokens in source control.
            </p>

            <!-- Accordion of providers -->
            <div class="accordion" id="gatewayAccordion">
                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSim">
                            1. Simulated Testbed (Development Mode)
                        </button>
                    </h2>
                    <div id="collapseSim" class="accordion-collapse collapse show">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=simulated<br>
                            SMS_SIMULATION_ENABLED=true<br>
                            SMS_REMINDER_HOURS_BEFORE=24
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold text-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTextbee">
                            <i class="bi bi-star-fill text-warning me-1"></i> 2. TextBee Free Gateway (300 Free SMS/mo to Real Numbers)
                        </button>
                    </h2>
                    <div id="collapseTextbee" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=textbee<br>
                            SMS_SIMULATION_ENABLED=false<br>
                            SMS_TEXTBEE_API_KEY=your_api_key<br>
                            SMS_TEXTBEE_DEVICE_ID=optional_device_id<br>
                            <span class="text-secondary"># Register free at https://textbee.dev</span>
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwilio">
                            3. Twilio SMS Gateway (Includes ~$15 Free Trial Credit)
                        </button>
                    </h2>
                    <div id="collapseTwilio" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=twilio<br>
                            SMS_SIMULATION_ENABLED=false<br>
                            SMS_TWILIO_SID=ACXXXXXXXXXXXXXXXX<br>
                            SMS_TWILIO_AUTH_TOKEN=your_auth_token<br>
                            SMS_TWILIO_FROM=+1234567890<br>
                            <span class="text-secondary"># Twilio trial sends directly to your verified personal number</span>
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSemaphore">
                            4. Semaphore SMS Gateway (PH / Southeast Asia)
                        </button>
                    </h2>
                    <div id="collapseSemaphore" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=semaphore<br>
                            SMS_SIMULATION_ENABLED=false<br>
                            SMS_SEMAPHORE_API_KEY=your_semaphore_key<br>
                            SMS_SEMAPHORE_SENDER_NAME=CLINICSYNC
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTextbelt">
                            5. Textbelt SMS API (1 Free SMS/Day)
                        </button>
                    </h2>
                    <div id="collapseTextbelt" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=textbelt<br>
                            SMS_SIMULATION_ENABLED=false<br>
                            SMS_TEXTBELT_KEY=textbelt
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustom">
                            6. Generic Webhook / Custom HTTP SMS Gateway
                        </button>
                    </h2>
                    <div id="collapseCustom" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=custom_http<br>
                            SMS_SIMULATION_ENABLED=false<br>
                            SMS_CUSTOM_HTTP_URL=https://api.sms-provider.com/send<br>
                            SMS_CUSTOM_HTTP_TOKEN=bearer_or_api_token
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold text-teal" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtpApi">
                            <i class="bi bi-shield-check me-1"></i> 5. Shelex Free OTP & Inbound Test SMS API
                        </button>
                    </h2>
                    <div id="collapseOtpApi" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted">
                            <p class="mb-2">Aggregates public phone numbers to receive and read incoming OTPs/SMS for testing without SMS carrier costs:</p>
                            <div class="font-monospace p-2 bg-white rounded border mb-2">
                                <strong>1. Available Countries:</strong><br>
                                <span class="text-primary">GET</span> /staff/otp-service/countries<br>
                                <em>Proxy for:</em> https://otp-api.shelex.dev/api/countries<br><br>
                                <strong>2. Numbers per Country:</strong><br>
                                <span class="text-primary">GET</span> /staff/otp-service/list/{country}<br>
                                <em>Proxy for:</em> https://otp-api.shelex.dev/api/list/&#123;&#123;country&#125;&#125;<br><br>
                                <strong>3. Read Received OTP / Message:</strong><br>
                                <span class="text-primary">GET</span> /staff/otp-service/verify/{country}/{phone}?match=CLINICSYNC&ago=300<br>
                                <em>Proxy for:</em> https://otp-api.shelex.dev/api/&#123;&#123;country&#125;&#125;/&#123;&#123;phone&#125;&#125;?match=&#123;&#123;substring&#125;&#125;&ago=&#123;&#123;ago&#125;&#125;
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-top pt-3 mt-4">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-terminal me-1"></i> Running Reminder Scheduler & Queue Worker</h6>
                <p class="small text-muted mb-2">Execute these commands in your console to process automated 24-hour reminders and background jobs:</p>
                
                <div class="p-2 rounded bg-dark text-light small font-monospace mb-2">
                    # Send upcoming appointment reminders immediately:<br>
                    <span class="text-success">php artisan appointments:send-reminders --hours=24</span>
                </div>

                <div class="p-2 rounded bg-dark text-light small font-monospace">
                    # Run Laravel task scheduler continuously:<br>
                    <span class="text-success">php artisan schedule:work</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section: Website Inspection Deterrence & Frontend Protection -->
<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card card-custom p-4 border shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                            <i class="bi bi-shield-lock-fill me-1"></i> Behavioral Protection Layer
                        </span>
                        @if($securityConfig['enabled'])
                            <span class="badge bg-success">Active Protection</span>
                        @else
                            <span class="badge bg-secondary">Deterrence Disabled</span>
                        @endif
                        @if(session('admin_inspection_mode', false))
                            <span class="badge bg-warning text-dark">Admin Debug Mode Active</span>
                        @endif
                    </div>
                    <h5 class="fw-bold text-dark mb-0">Website Inspection Deterrence & Frontend Protection</h5>
                    <p class="text-muted small mb-0 mt-1">
                        Discourages casual users from accessing browser developer inspection tools while safeguarding normal navigation, accessibility, and form inputs.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <form action="{{ route('staff.settings.toggle-inspection-mode') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ session('admin_inspection_mode', false) ? 'btn-warning text-dark' : 'btn-outline-secondary' }} rounded-pill px-3">
                            <i class="bi bi-bug me-1"></i>
                            {{ session('admin_inspection_mode', false) ? 'Disable Admin Inspection Mode' : 'Enable Admin Inspection Mode' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informational Security Notice -->
            <div class="alert alert-info py-2 px-3 small mb-4 border-start border-4 border-info">
                <div class="fw-bold"><i class="bi bi-info-circle-fill me-1"></i> Defense-in-Depth Principle:</div>
                <div class="opacity-90">
                    Browser developer tools cannot be completely disabled by any website because clients retain full execution authority in their own browsers. These controls act as behavioral deterrents against casual tampering. True clinical and authentication security is strictly enforced on the server-side via authorization middleware, rate limiting, CSRF verification, and database encryption.
                </div>
            </div>

            <form action="{{ route('staff.settings.security.update') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <!-- Master Switch -->
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="enabled" id="sec_enabled" value="1" {{ $securityConfig['enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="sec_enabled">Master Deterrence Layer</label>
                            </div>
                            <div class="text-muted small">
                                Enables or disables all client-side inspection deterrence mechanisms globally across the application.
                            </div>
                        </div>
                    </div>

                    <!-- Right Click Context Menu -->
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="disable_context_menu" id="sec_context_menu" value="1" {{ $securityConfig['disable_context_menu'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="sec_context_menu">Restrict Right-Click Menu</label>
                            </div>
                            <div class="text-muted small">
                                Blocks the right-click context menu on page canvases while strictly preserving right-click copy/paste inside form input fields.
                            </div>
                        </div>
                    </div>

                    <!-- Block Shortcuts -->
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="block_shortcuts" id="sec_shortcuts" value="1" {{ $securityConfig['block_shortcuts'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="sec_shortcuts">Block DevTools Shortcuts</label>
                            </div>
                            <div class="text-muted small">
                                Traps and cancels F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, and Ctrl+U without interfering with standard navigation or Ctrl+C / Ctrl+V.
                            </div>
                        </div>
                    </div>

                    <!-- DevTools Detection -->
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="detect_devtools" id="sec_detect" value="1" {{ $securityConfig['detect_devtools'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="sec_detect">Lightweight DevTools Detection</label>
                            </div>
                            <div class="text-muted small">
                                Detects open developer tools via debounced resize thresholds. Avoids aggressive loops, freezes, or forced redirects.
                            </div>
                        </div>
                    </div>

                    <!-- Warning Toast -->
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="show_warning" id="sec_warning" value="1" {{ $securityConfig['show_warning'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="sec_warning">Security Warning Notice</label>
                            </div>
                            <div class="text-muted small">
                                Displays a non-intrusive floating security banner when inspection attempts occur, informing the user that access is audited.
                            </div>
                        </div>
                    </div>

                    <!-- Admin Bypass -->
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="allow_admin_bypass" id="sec_admin_bypass" value="1" {{ $securityConfig['allow_admin_bypass'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="sec_admin_bypass">Administrator Bypass Option</label>
                            </div>
                            <div class="text-muted small">
                                Allows authenticated administrators to bypass deterrence restrictions using the inspection mode switch above.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Save Security Deterrence Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function loadOtpCountries() {
        const btn = document.getElementById('btnLoadCountries');
        const select = document.getElementById('otpCountrySelect');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch('{{ route("staff.otp.countries") }}');
            const data = await res.json();
            if (data.status === 'success' && Array.isArray(data.countries)) {
                select.innerHTML = '<option value="">-- Choose Country --</option>';
                data.countries.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = typeof c === 'string' ? c : (c.name || c.country || JSON.stringify(c));
                    opt.textContent = (typeof c === 'string' ? c : (c.name || c.country)).toUpperCase();
                    select.appendChild(opt);
                });
                select.onchange = onOtpCountrySelected;
            } else {
                alert('Could not load countries. Ensure the external API is reachable.');
            }
        } catch (err) {
            alert('Error contacting OTP service endpoint: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Load';
        }
    }

    async function onOtpCountrySelected() {
        const country = document.getElementById('otpCountrySelect').value;
        const numberSelect = document.getElementById('otpNumberSelect');
        const btnCopy = document.getElementById('btnCopyOtpPhone');
        const btnCheck = document.getElementById('btnCheckOtpCode');

        if (!country) {
            numberSelect.disabled = true;
            numberSelect.innerHTML = '<option value="">-- Select country first --</option>';
            btnCopy.disabled = true;
            btnCheck.disabled = true;
            return;
        }

        numberSelect.disabled = true;
        numberSelect.innerHTML = '<option value="">Loading phone numbers...</option>';

        try {
            const res = await fetch(`{{ url('/staff/otp-service/list') }}/${encodeURIComponent(country)}`);
            const data = await res.json();
            numberSelect.innerHTML = '<option value="">-- Select a phone number --</option>';

            if (data.status === 'success' && Array.isArray(data.numbers) && data.numbers.length > 0) {
                data.numbers.forEach(item => {
                    const opt = document.createElement('option');
                    const phone = typeof item === 'string' ? item : (item.phone || item.number || JSON.stringify(item));
                    opt.value = phone;
                    opt.textContent = phone;
                    numberSelect.appendChild(opt);
                });
                numberSelect.disabled = false;
            } else {
                numberSelect.innerHTML = '<option value="">No numbers currently available for this country</option>';
            }
        } catch (err) {
            numberSelect.innerHTML = '<option value="">Error loading numbers</option>';
        }
    }

    function onOtpNumberSelected() {
        const phone = document.getElementById('otpNumberSelect').value;
        const btnCopy = document.getElementById('btnCopyOtpPhone');
        const btnCheck = document.getElementById('btnCheckOtpCode');
        const hasPhone = Boolean(phone);
        btnCopy.disabled = !hasPhone;
        btnCheck.disabled = !hasPhone;
    }

    function copyOtpNumber() {
        const phone = document.getElementById('otpNumberSelect').value;
        if (!phone) return;
        const input = document.querySelector('input[name="phone"]');
        if (input) {
            input.value = phone;
            input.scrollIntoView({ behavior: 'smooth' });
            input.classList.add('is-valid');
            setTimeout(() => input.classList.remove('is-valid'), 2000);
        }
    }

    async function checkOtpCode() {
        const country = document.getElementById('otpCountrySelect').value;
        const phone = document.getElementById('otpNumberSelect').value;
        const btnCheck = document.getElementById('btnCheckOtpCode');
        const resultBox = document.getElementById('otpResultBox');
        const badge = document.getElementById('otpStatusBadge');
        const content = document.getElementById('otpResultContent');

        if (!country || !phone) return;

        btnCheck.disabled = true;
        btnCheck.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';
        resultBox.classList.remove('d-none');
        badge.className = 'badge bg-warning text-dark';
        badge.textContent = 'Querying...';
        content.textContent = `Querying Shelex OTP API for ${phone}...`;

        try {
            const cleanPhone = encodeURIComponent(phone.replace(/[^\d+]/g, ''));
            const res = await fetch(`{{ url('/staff/otp-service/verify') }}/${encodeURIComponent(country)}/${cleanPhone}?match=CLINICSYNC&ago=600`);
            const data = await res.json();

            if (data.status === 'success' && data.data) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Received';
                content.innerHTML = `<pre class="mb-0 text-success">${JSON.stringify(data.data, null, 2)}</pre>`;
            } else {
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Pending';
                content.textContent = 'No message matching "CLINICSYNC" found in the last 10 minutes for this number.';
            }
        } catch (err) {
            badge.className = 'badge bg-danger';
            badge.textContent = 'Error';
            content.textContent = 'Failed to fetch messages: ' + err.message;
        } finally {
            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="bi bi-search me-1"></i> Check Incoming OTP / SMS';
        }
    }
</script>
@endsection
