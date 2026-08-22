import * as bootstrap from 'bootstrap';
import modbox from 'bootstrap-modbox/dist/bootstrap-modbox.esm';
import TomSelect from 'tom-select/dist/js/tom-select.complete';
import 'livewire-sortable';
import { ThemeSwitcher } from './theme-switcher';

window.bootstrap  = bootstrap;
window.modbox     = modbox;
window.TomSelect  = TomSelect;

// ── Modali ────────────────────────────────────────────────────────────────────
function hideModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        const instance = bootstrap.Modal.getInstance(modal);
        if (instance) instance.hide();
    });
    document.body.classList.remove('modal-open');
    document.querySelector('div.modal-backdrop')?.remove();
}

function showModal(id) {
    new bootstrap.Modal('#' + id + 'Modal').show();
}

window.addEventListener('hide-modals', () => hideModals());
window.addEventListener('show-modal',  e => showModal(e.detail[0]));

window.confirm_modal = (message, confirm) =>
    modbox.confirm({ body: message, okButton: { label: confirm } });

// ── Sidebar toggle (persiste in localStorage) ─────────────────────────────────
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    document.body.classList.toggle('sidebar-toggled');
    sidebar.classList.toggle('toggled');
    localStorage.setItem('sidebarToggled', sidebar.classList.contains('toggled'));
}

document.addEventListener('DOMContentLoaded', () => {
    // Ripristina stato sidebar
    if (localStorage.getItem('sidebarToggled') === 'true') {
        document.body.classList.add('sidebar-toggled');
        document.querySelector('.sidebar')?.classList.add('toggled');
    }

    document.getElementById('sidebarToggleTop')?.addEventListener('click', e => {
        e.preventDefault(); toggleSidebar();
    });
    document.getElementById('sidebarToggle')?.addEventListener('click', e => {
        e.preventDefault(); toggleSidebar();
    });
});

// ── Theme switcher ────────────────────────────────────────────────────────────
ThemeSwitcher.init();
