@extends('layout::app')

@section('main')

    <header id="header" class="header fixed-top">

        <div class="topbar d-flex align-items-center">
            <div class="container d-flex justify-content-center justify-content-md-between">
                <div class="contact-info d-flex align-items-center">
                    <i class="bi bi-envelope d-flex align-items-center"><a href="mailto:contact@example.com">contact@example.com</a></i>
                    <i class="bi bi-phone d-flex align-items-center ms-4"><span>+1 5589 55488 55</span></i>
                </div>
                <div class="social-links d-none d-md-flex align-items-center">
                    <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div><!-- End Top Bar -->

        <div class="branding d-flex align-items-cente">

            <div class="container position-relative d-flex align-items-center justify-content-between">
                <a href="index.html" class="logo d-flex align-items-center">
                    <!-- Uncomment the line below if you also wish to use an image logo -->
                    <!-- <img src="assets/img/logo.png" alt=""> -->
                    <h1 class="sitename">Impact</h1>
                    <span>.</span>
                </a>

                <nav id="navmenu" class="navmenu">
                    <ul>
                        <li><a href="#hero" class="active">Home<br></a></li>
                        <li><a href="#about" class="">About</a></li>
                        <li><a href="#services" class="">Services</a></li>
                        <li><a href="#portfolio" class="">Portfolio</a></li>
                        <li><a href="#team">Team</a></li>
                        <li><a href="blog.html">Blog</a></li>
                        <li class="dropdown"><a href="#"><span>Dropdown</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                            <ul>
                                <li><a href="#">Dropdown 1</a></li>
                                <li class="dropdown"><a href="#"><span>Deep Dropdown</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                                    <ul>
                                        <li><a href="#">Deep Dropdown 1</a></li>
                                        <li><a href="#">Deep Dropdown 2</a></li>
                                        <li><a href="#">Deep Dropdown 3</a></li>
                                        <li><a href="#">Deep Dropdown 4</a></li>
                                        <li><a href="#">Deep Dropdown 5</a></li>
                                    </ul>
                                </li>
                                <li><a href="#">Dropdown 2</a></li>
                                <li><a href="#">Dropdown 3</a></li>
                                <li><a href="#">Dropdown 4</a></li>
                            </ul>
                        </li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                    <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
                </nav>

            </div>

        </div>

    </header>
{{--    <main class="main">--}}

{{--        <!-- Hero Section -->--}}
{{--        <section id="hero" class="hero section accent-background">--}}

{{--            <div class="container position-relative" data-aos="fade-up" data-aos-delay="100">--}}
{{--                <div class="row gy-5 justify-content-between">--}}
{{--                    <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center">--}}
{{--                        <h2><span>Welcome to </span><span class="accent">Impact</span></h2>--}}
{{--                        <p>Sed autem laudantium dolores. Voluptatem itaque ea consequatur eveniet. Eum quas beatae cumque eum quaerat.</p>--}}
{{--                        <div class="d-flex">--}}
{{--                            <a href="#about" class="btn-get-started">Get Started</a>--}}
{{--                            <a href="https://www.youtube.com/watch?v=Y7f98aduVJ8" class="glightbox btn-watch-video d-flex align-items-center"><i class="bi bi-play-circle"></i><span>Watch Video</span></a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="col-lg-5 order-1 order-lg-2">--}}
{{--                        <img src="assets/img/hero-img.svg" class="img-fluid" alt="">--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="icon-boxes position-relative" data-aos="fade-up" data-aos-delay="200">--}}
{{--                <div class="container position-relative">--}}
{{--                    <div class="row gy-4 mt-5">--}}

{{--                        <div class="col-xl-3 col-md-6">--}}
{{--                            <div class="icon-box">--}}
{{--                                <div class="icon"><i class="bi bi-easel"></i></div>--}}
{{--                                <h4 class="title"><a href="" class="stretched-link">Lorem Ipsum</a></h4>--}}
{{--                            </div>--}}
{{--                        </div><!--End Icon Box -->--}}

{{--                        <div class="col-xl-3 col-md-6">--}}
{{--                            <div class="icon-box">--}}
{{--                                <div class="icon"><i class="bi bi-gem"></i></div>--}}
{{--                                <h4 class="title"><a href="" class="stretched-link">Sed ut perspiciatis</a></h4>--}}
{{--                            </div>--}}
{{--                        </div><!--End Icon Box -->--}}

{{--                        <div class="col-xl-3 col-md-6">--}}
{{--                            <div class="icon-box">--}}
{{--                                <div class="icon"><i class="bi bi-geo-alt"></i></div>--}}
{{--                                <h4 class="title"><a href="" class="stretched-link">Magni Dolores</a></h4>--}}
{{--                            </div>--}}
{{--                        </div><!--End Icon Box -->--}}

{{--                        <div class="col-xl-3 col-md-6">--}}
{{--                            <div class="icon-box">--}}
{{--                                <div class="icon"><i class="bi bi-command"></i></div>--}}
{{--                                <h4 class="title"><a href="" class="stretched-link">Nemo Enim</a></h4>--}}
{{--                            </div>--}}
{{--                        </div><!--End Icon Box -->--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--        </section><!-- /Hero Section -->--}}

{{--    </main>--}}
@endsection
@push('head_scripts')

@endpush
