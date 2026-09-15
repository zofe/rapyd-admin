{{-- Theme picker (config rapyd.theme_switch): the bundled look and every registered theme, kept per visitor in the session. --}}
@if(config('rapyd.theme_switch') && config('rapyd.themes'))
    @php($themes = app(\Zofe\Rapyd\Themes\ThemeManager::class))
    <li class="nav-item dropdown">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" title="Theme" aria-label="Choose theme">
            <i class="fas fa-palette"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            @foreach($themes->names() as $name)
                <a class="dropdown-item {{ $name === $themes->active() ? 'active' : '' }}"
                   href="{{ request()->fullUrlWithQuery([\Zofe\Rapyd\Themes\ThemeManager::QUERY => $name]) }}">
                    {{ ucfirst($name) }}
                </a>
            @endforeach
        </div>
    </li>
@endif
