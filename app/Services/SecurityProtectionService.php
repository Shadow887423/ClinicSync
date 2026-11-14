<?php

namespace App\Services;

use App\Models\MonitoringSetting;
use Illuminate\Support\Facades\Auth;

class SecurityProtectionService
{
    /**
     * Get all deterrence configuration options (merging DB settings with config defaults)
     */
    public function getSettings(): array
    {
        return [
            'enabled' => filter_var(MonitoringSetting::get('security.inspection_deterrence_enabled', config('security.inspection_deterrence.enabled', true)), FILTER_VALIDATE_BOOLEAN),
            'disable_context_menu' => filter_var(MonitoringSetting::get('security.disable_context_menu', config('security.inspection_deterrence.disable_context_menu', true)), FILTER_VALIDATE_BOOLEAN),
            'block_shortcuts' => filter_var(MonitoringSetting::get('security.block_shortcuts', config('security.inspection_deterrence.block_shortcuts', true)), FILTER_VALIDATE_BOOLEAN),
            'detect_devtools' => filter_var(MonitoringSetting::get('security.detect_devtools', config('security.inspection_deterrence.detect_devtools', true)), FILTER_VALIDATE_BOOLEAN),
            'show_warning' => filter_var(MonitoringSetting::get('security.show_warning', config('security.inspection_deterrence.show_warning', true)), FILTER_VALIDATE_BOOLEAN),
            'allow_admin_bypass' => filter_var(MonitoringSetting::get('security.allow_admin_bypass', config('security.inspection_deterrence.allow_admin_bypass', true)), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Determine if deterrence should be active for the current request/session
     */
    public function isDeterrenceActive(): bool
    {
        $settings = $this->getSettings();
        if (!$settings['enabled']) {
            return false;
        }

        // If admin bypass is enabled and user is logged-in admin
        if ($settings['allow_admin_bypass'] && Auth::check() && Auth::user()->isAdmin()) {
            if (session('admin_inspection_mode', false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update settings from admin console
     */
    public function updateSettings(array $data): void
    {
        MonitoringSetting::set('security.inspection_deterrence_enabled', !empty($data['enabled']) ? '1' : '0', 'Enable or disable website inspection deterrence');
        MonitoringSetting::set('security.disable_context_menu', !empty($data['disable_context_menu']) ? '1' : '0', 'Restrict right-click context menu on designated pages');
        MonitoringSetting::set('security.block_shortcuts', !empty($data['block_shortcuts']) ? '1' : '0', 'Detect and block devtools shortcuts (F12, Ctrl+Shift+I, etc.)');
        MonitoringSetting::set('security.detect_devtools', !empty($data['detect_devtools']) ? '1' : '0', 'Detect open browser developer tools');
        MonitoringSetting::set('security.show_warning', !empty($data['show_warning']) ? '1' : '0', 'Display warning toast when inspection attempts occur');
        MonitoringSetting::set('security.allow_admin_bypass', !empty($data['allow_admin_bypass']) ? '1' : '0', 'Allow logged-in administrators to bypass deterrence');
    }
}
