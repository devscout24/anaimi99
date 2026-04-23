<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">

<head>

    <meta charset="utf-8" />
    <title>{{ $systemSetting->system_title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset($systemSetting->favicon) }}">

    @include('backend.partials.styles')
    @stack('styles')

</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('backend.partials.header')
        @include('backend.partials.remove-notification-modal')
        @include('backend.partials.app-menu') <!-- menu bar, user siderbar, sidebar -->
        <!-- Left Sidebar End -->

        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    @yield('content') <!-- ***main content*** -->

                </div>
            </div>
            @include('backend.partials.footer')
        </div>
        <!-- ============================================================== -->
        <!-- End right Content here -->
        <!-- ============================================================== -->

    </div>
    <!-- END layout-wrapper -->

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    {{-- @include('backend.partials.preloader')  --}}
    @include('backend.partials.theme-settings')
    @include('backend.partials.scripts')
    @stack('scripts')

</body>

</html>
