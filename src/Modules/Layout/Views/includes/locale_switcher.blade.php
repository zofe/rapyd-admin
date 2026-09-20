{{-- Language switcher: shown when more than one locale is enabled (config rapyd.locales).
     Codes and native names, no flags (a flag is a country, not a language).
     url_lang($locale, true) adds ?clang=1: the choice is remembered (session, user). --}}
@php($rpdLocales = app(\Zofe\Rapyd\Localization\Locales::class))
@if($rpdLocales->enabled())
    <li class="nav-item dropdown no-arrow">
        <a id="localeDropdown" class="nav-link dropdown-toggle text-uppercase" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ $rpdLocales->name(app()->getLocale()) }}">
            {{ app()->getLocale() }}
        </a>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="localeDropdown">
            @foreach($rpdLocales->all() as $locale)
                <a class="dropdown-item {{ $locale === app()->getLocale() ? 'active' : '' }}" href="{{ url_lang($locale, true) }}" hreflang="{{ $locale }}">
                    <span class="text-uppercase text-muted small me-2">{{ $locale }}</span>{{ $rpdLocales->name($locale) }}
                </a>
            @endforeach
        </div>
    </li>
@endif
