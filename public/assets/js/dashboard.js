/**
 * TERIMA JASA - Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('dashboardSidebar');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    if (sidebarCloseBtn && sidebar) {
        sidebarCloseBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }

    // 2. Role Switcher helper
    const roleSelect = document.getElementById('roleQuickSwitcher');
    if (roleSelect) {
        roleSelect.addEventListener('change', (e) => {
            const selectedRole = e.target.value;
            window.location.href = `?role=${selectedRole}`;
        });
    }
});
