@props([
'title' => null,
'icon' => null,
])
@php
    $attributes = $attributes->class([
    'rpd-sidebar navbar-expand-lg collapse collapse-horizontal min-vh-100 show',
    session()->get('sidebar-show')? 'show' : '',
    ])->merge([]);
    $context_sidebar = true;
@endphp
    <div {{$attributes}} id="sidebar" >

        <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
            @if($title)
                <span class="fs-5">{{$title}}</span>
            @endif
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">

            {{ $slot }}

        </ul>
    </div>
    <div v-pre class="rpd-sidebar-toggler pt-3">
        <button class="navbar-toggler"
                data-bs-toggle="collapse"
                data-bs-target="#sidebar"
                aria-controls="sidebar"
                aria-expanded="false"
                aria-label="Toggle navigation"
                type="button"
        >
            <span class="bi bi-grip-vertical text-muted"></span>
        </button>
        <style>
            .rpd-sidebar {
                width: 200px;
            }
            .rpd-sidebar-toggler {
                margin-right: -1rem;
                z-index: 1;
            }
            .rpd-sidebar-toggler .navbar-toggler{
                padding-left: 0;
                transition: none;
            }
            .navbar-toggler:focus{
                box-shadow: none;
            }
        </style>
    </div>


