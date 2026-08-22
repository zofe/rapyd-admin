export const ThemeSwitcher = {
    setDarkClass: () => {
        let isDark = localStorage.theme === 'dark' ||
            (!('theme' in localStorage) &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)

        isDark
            ? document.documentElement.classList.add('dark')
            : document.documentElement.classList.remove('dark')

        ThemeSwitcher.updateAdminTheme()
    },

    init: () => {
        ThemeSwitcher.setDarkClass()
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', ThemeSwitcher.setDarkClass)
        window.ThemeSwitcher = ThemeSwitcher
    },
    updateAdminTheme: () => {
        const charts = document.querySelectorAll('.chart-area, .chart-pie, .chart-bar');
        if (document.documentElement.classList.contains('dark')) {
            charts.forEach(chart => chart.style.filter = 'invert(0.8) hue-rotate(180deg)');
        } else {
            charts.forEach(chart => chart.style.filter = 'none');
        }
    }
}
