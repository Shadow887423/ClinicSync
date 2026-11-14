/**
 * CLINICSYNC — Website Inspection Deterrence & Frontend Protection Layer
 * 
 * Notice: Browser developer tools cannot be completely blocked by any client-side script.
 * This script provides a production-grade behavioral deterrent against casual inspection
 * while preserving full keyboard navigation, screen reader accessibility, and form interactions.
 * Genuine security is strictly enforced on the server-side.
 */
(function() {
    'use strict';

    // Retrieve runtime configuration
    const config = Object.assign({
        disableContextMenu: true,
        blockShortcuts: true,
        detectDevTools: true,
        showWarning: true,
        isAdmin: false
    }, window.__CLINICSYNC_SECURITY__ || {});

    let lastNotificationTime = 0;
    const NOTIFICATION_COOLDOWN_MS = 15000; // 15 seconds cooldown between alerts

    // Utility: Check if an element is an input or editable field
    function isInputElement(el) {
        if (!el) return false;
        const tag = el.tagName ? el.tagName.toUpperCase() : '';
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    // Display a non-intrusive, accessible toast notification
    function showSecurityToast(message) {
        if (!config.showWarning) return;

        const now = Date.now();
        if (now - lastNotificationTime < NOTIFICATION_COOLDOWN_MS) {
            return;
        }
        lastNotificationTime = now;

        let toastContainer = document.getElementById('clinicsync-security-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'clinicsync-security-toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.bottom = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '999999';
            toastContainer.style.maxWidth = '360px';
            toastContainer.setAttribute('aria-live', 'polite');
            toastContainer.setAttribute('role', 'alert');
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = 'clinicsync-sec-toast';
        toast.style.background = '#0f172a';
        toast.style.color = '#f8fafc';
        toast.style.padding = '12px 16px';
        toast.style.borderRadius = '10px';
        toast.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3)';
        toast.style.border = '1px solid rgba(255, 255, 255, 0.12)';
        toast.style.fontFamily = 'system-ui, -apple-system, sans-serif';
        toast.style.fontSize = '0.85rem';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '12px';
        toast.style.marginBottom = '10px';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';

        toast.innerHTML = `
            <svg style="width: 22px; height: 22px; flex-shrink: 0; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div style="flex-grow: 1;">
                <div style="font-weight: 600; color: #ffffff; margin-bottom: 2px;">Security Notice</div>
                <div style="opacity: 0.85; line-height: 1.3;">${message}</div>
            </div>
            <button type="button" aria-label="Close" style="background: none; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; padding: 0 4px; line-height: 1;">&times;</button>
        `;

        const closeBtn = toast.querySelector('button');
        closeBtn.addEventListener('click', function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        });

        toastContainer.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        // Auto remove after 6 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 6000);
    }

    // 1. Right-Click Context Menu Restriction
    if (config.disableContextMenu) {
        document.addEventListener('contextmenu', function(e) {
            // NEVER block context menu on inputs, textareas, or contentEditable elements
            // (preserves copy, paste, select all, spellcheck accessibility)
            if (isInputElement(e.target)) {
                return true;
            }

            e.preventDefault();
            showSecurityToast('Right-click context menu is restricted to safeguard clinical healthcare records.');
            return false;
        }, { capture: true, passive: false });
    }

    // 2. Developer Tools Shortcut Deterrence
    if (config.blockShortcuts) {
        document.addEventListener('keydown', function(e) {
            const isCtrlOrCmd = e.ctrlKey || e.metaKey;
            const isShift = e.shiftKey;
            const isAlt = e.altKey;
            const key = (e.key || '').toLowerCase();
            const keyCode = e.keyCode || e.which;

            // F12
            const isF12 = key === 'f12' || keyCode === 123;

            // Ctrl + Shift + I or Cmd + Alt + I (Inspect)
            const isInspect = (isCtrlOrCmd && isShift && (key === 'i' || keyCode === 73)) ||
                              (e.metaKey && isAlt && (key === 'i' || keyCode === 73));

            // Ctrl + Shift + J or Cmd + Alt + J (Console)
            const isConsole = (isCtrlOrCmd && isShift && (key === 'j' || keyCode === 74)) ||
                              (e.metaKey && isAlt && (key === 'j' || keyCode === 74));

            // Ctrl + Shift + C or Cmd + Alt + C (Element Inspector)
            const isElementInspector = (isCtrlOrCmd && isShift && (key === 'c' || keyCode === 67)) ||
                                       (e.metaKey && isAlt && (key === 'c' || keyCode === 67));

            // Ctrl + Shift + K (Firefox Console)
            const isFirefoxConsole = isCtrlOrCmd && isShift && (key === 'k' || keyCode === 75);

            // Ctrl + U or Cmd + Alt + U (View Source)
            const isViewSource = (isCtrlOrCmd && !isShift && !isAlt && (key === 'u' || keyCode === 85)) ||
                                 (e.metaKey && isAlt && (key === 'u' || keyCode === 85));

            if (isF12 || isInspect || isConsole || isElementInspector || isFirefoxConsole || isViewSource) {
                // Prevent devtools shortcut
                e.preventDefault();
                e.stopPropagation();
                showSecurityToast('Developer tools shortcut disabled. All actions are authenticated and audited server-side.');
                return false;
            }

            // Normal keyboard operations (Ctrl+C copy, Ctrl+V paste, Ctrl+A select all, Tab, Enter)
            // are explicitly allowed to proceed unimpeded.
            return true;
        }, { capture: true, passive: false });
    }

    // 3. Lightweight Developer Tools Detection
    if (config.detectDevTools) {
        let resizeTimer = null;

        // Check for window resize delta indicating docked devtools
        function checkWindowDocks() {
            const widthThreshold = window.outerWidth - window.innerWidth > 160;
            const heightThreshold = window.outerHeight - window.innerHeight > 160;

            if (widthThreshold || heightThreshold) {
                showSecurityToast('Developer tools window detected. Please be aware that healthcare operations are audited.');
            }
        }

        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(checkWindowDocks, 400);
        }, { passive: true });

        // Initial check on load
        if (document.readyState === 'complete') {
            checkWindowDocks();
        } else {
            window.addEventListener('load', checkWindowDocks, { once: true });
        }
    }
})();
