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
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwilio">
                            2. Twilio SMS Gateway (Global)
                        </button>
                    </h2>
                    <div id="collapseTwilio" class="accordion-collapse collapse">
                        <div class="accordion-body small bg-light text-muted font-monospace">
                            SMS_GATEWAY_PROVIDER=twilio<br>
                            SMS_SIMULATION_ENABLED=false<br>
                            SMS_TWILIO_SID=ACXXXXXXXXXXXXXXXX<br>
                            SMS_TWILIO_AUTH_TOKEN=your_auth_token<br>
                            SMS_TWILIO_FROM=+1234567890
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSemaphore">
                            3. Semaphore SMS Gateway (PH / Southeast Asia)
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
                        <button class="accordion-button collapsed py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustom">
                            4. Generic Webhook / Custom HTTP SMS Gateway
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
@endsection
