import './bootstrap';
import './modules/branch-locator';
import './modules/dashboard-charts';
import './modules/interactions';
import './modules/timekeeping-dashboard';
import './modules/theme-toggle';

// Auto-dismiss flash messages after a few seconds.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        window.setTimeout(() => element.remove(), 6000);
    });
});
