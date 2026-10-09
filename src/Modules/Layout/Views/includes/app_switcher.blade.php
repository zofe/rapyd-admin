{{-- Switch between sibling applications (config rapyd.layout.apps, see docs/THEMES.md).
     The brand keeps linking to the home as always: only the chevron opens the menu. --}}
<li class="nav-item sidebar-apps">
    <div class="sidebar-apps-row d-flex align-items-center">

        {{-- niente utility d-flex qui: è display:flex !important e impedirebbe di
             nascondere il marchio quando la sidebar è stretta (vedi _sidebar.scss) --}}
        <a class="sidebar-brand" style="overflow: hidden;" href="{{ $homeRoute }}">
            @if(config('rapyd.layout.logo_sidebar'))
                <img src="{{ config('rapyd.layout.logo_sidebar') }}" class="img-fluid px-2" alt="{{ config('rapyd.layout.brand') ?: config('app.name') }}">
            @else
                {{ config('rapyd.layout.brand') ?: config('app.name') }}
            @endif
        </a>

        <div class="dropdown">
            <button type="button" class="sidebar-apps-toggle" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false" aria-label="{{ __('Switch application') }}">
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end sidebar-apps-menu">
                @foreach($apps as $app)
                    <li>
                        @if($app['current'])
                            <span class="dropdown-item active" aria-current="true">
                                <i class="fas fa-check fa-fw me-1" aria-hidden="true"></i>{{ $app['name'] }}
                            </span>
                        @else
                            <a class="dropdown-item d-flex align-items-center" href="{{ $app['url'] }}">
                                <i class="fas fa-{{ $app['icon'] ?: 'circle-nodes' }} fa-fw me-1" aria-hidden="true"></i>
                                <span class="flex-grow-1">{{ $app['name'] }}</span>
                                <i class="fas fa-arrow-up-right-from-square ms-2 small opacity-50" aria-hidden="true"></i>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

    </div>
</li>
