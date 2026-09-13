{{-- Light / dark toggle: one click flips the current look; the icon shows what the click will give.
     The choice lives in localStorage (see resources/js/theme-switcher.js); nothing stored = follow the system. --}}
<li class="nav-item" x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            localStorage.theme = this.dark ? 'light' : 'dark';
            ThemeSwitcher.setDarkClass();
            this.dark = document.documentElement.classList.contains('dark');
        },
    }">
    <a href="#" class="nav-link" @click.prevent="toggle()" title="Light / dark" aria-label="Toggle dark mode">
        <i x-show="!dark" class="fas fa-moon"></i>
        <i x-show="dark" class="fas fa-sun"></i>
    </a>
</li>
