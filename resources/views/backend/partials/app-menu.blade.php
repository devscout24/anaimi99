 <!-- ========== App Menu ========== -->
 <div class="app-menu navbar-menu">
     <!-- LOGO -->
     <div class="navbar-brand-box">
         <!-- Dark Logo-->
         <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
             <span class="logo-sm">
                 @if (!empty($systemSetting->mini_logo))
                     <img src="{{ asset($systemSetting->mini_logo) }}" alt="Logo" height="22">
                 @endif
             </span>
             <span class="logo-lg">
                 @if (!empty($systemSetting->logo))
                     <img src="{{ asset($systemSetting->logo) }}" alt="Logo" height="35">
                 @endif
             </span>
         </a>
         <!-- Light Logo-->
         <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
             <span class="logo-sm">
                 @if (!empty($systemSetting->mini_logo))
                     <img src="{{ asset($systemSetting->mini_logo) }}" alt="Logo" height="22">
                 @endif
             </span>
             <span class="logo-lg">
                 @if (!empty($systemSetting->logo))
                     <img src="{{ asset($systemSetting->logo) }}" alt="Logo" height="35">
                 @endif
             </span>
         </a>
         <button type="button" class="p-0 btn btn-sm fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
             <i class="ri-record-circle-line"></i>
         </button>
     </div>

     <!-- sidebar-user -->
     <div class="m-1 rounded dropdown sidebar-user">
         <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
             <span class="gap-2 d-flex align-items-center">
                 <img class="rounded header-profile-user" src="{{ auth()->user()->avatar ? asset(auth()->user()->avatar) : asset('backend/assets/images/users/avatar-1.jpg') }}" alt="Header Avatar">
                 <span class="text-start">
                     <span class="d-block fw-medium sidebar-user-name-text">{{ auth()->user()->name }}</span>
                     <span class="d-block fs-14 sidebar-user-name-sub-text"><i class="align-baseline ri ri-circle-fill fs-10 text-success"></i> <span class="align-middle">{{ __('admin.online') }}</span></span>
                 </span>
             </span>
         </button>
         <div class="dropdown-menu dropdown-menu-end">
             <!-- item-->
             <h6 class="dropdown-header">{{ __('admin.welcome', ['name' => auth()->user()->name]) }}</h6>
             <a class="dropdown-item" href="{{ route('admin.profile-settings.edit') }}"><i class="align-middle mdi mdi-account-circle text-muted fs-16 me-1"></i> <span
                     class="align-middle">{{ __('admin.profile') }}</span></a>
             <!-- Logout -->
             <form method="POST" action="{{ route('logout') }}">
                 @csrf
                 <button type="submit" class="dropdown-item">
                     <i class="align-middle mdi mdi-logout text-muted fs-16 me-1"></i>
                     <span class="align-middle" data-key="t-logout">{{ __('admin.logout') }}</span>
                 </button>
             </form>
         </div>
     </div>

     <!-- sidebar -->
     <div id="scrollbar">
         <div class="container-fluid">

             <div id="two-column-menu">
             </div>
             <ul class="navbar-nav" id="navbar-nav">

                 <!--  Menu -->
                 <li class="menu-title"><span data-key="t-menu">{{ __('admin.menu') }}</span></li>





                 <!-- Dashboard -->
                 <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                         <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">{{ __('admin.dashboards') }}</span>
                     </a>
                 </li>

                 {{-- Category Menu --}}
                 {{-- <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.categories.*') ? '' : 'collapsed' }}" href="#sidebarCategory" data-bs-toggle="collapse" role="button"
                         aria-expanded="{{ request()->routeIs('admin.categories.*') ? 'true' : 'false' }}" aria-controls="sidebarCategory">
                         <i class="ri-folder-line"></i> <span>Category</span>
                     </a>
                     <div class="collapse menu-dropdown {{ request()->routeIs('admin.categories.*') ? 'show' : '' }}" id="sidebarCategory">
                         <ul class="nav nav-sm flex-column">
                             <li class="nav-item">
                                 <a href="{{ route('admin.categories.create') }}" class="nav-link {{ request()->routeIs('admin.categories.create') ? 'active' : '' }}">
                                     Add Category
                                 </a>
                             </li>
                             <li class="nav-item">
                                 <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">
                                     All Categories
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li> --}}

                 {{-- Product Menu --}}
                 {{-- <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.products.*') ? '' : 'collapsed' }}" href="#sidebarProduct" data-bs-toggle="collapse" role="button"
                         aria-expanded="{{ request()->routeIs('admin.products.*') ? 'true' : 'false' }}" aria-controls="sidebarProduct">
                         <i class="ri-shopping-bag-3-line"></i> <span>Product</span>
                     </a>
                     <div class="collapse menu-dropdown {{ request()->routeIs('admin.products.*') ? 'show' : '' }}" id="sidebarProduct">
                         <ul class="nav nav-sm flex-column">
                             <li class="nav-item">
                                 <a href="{{ route('admin.products.create') }}" class="nav-link {{ request()->routeIs('admin.products.create') ? 'active' : '' }}">
                                     Add Product
                                 </a>
                             </li>
                             <li class="nav-item">
                                 <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.index') ? 'active' : '' }}">
                                     All Products
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li> --}}


                 {{-- service --}}
                 <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->is('admin/services*') || request()->is('admin/service-prices*') ? '' : 'collapsed' }}" href="#sidebarService" data-bs-toggle="collapse" role="button"
                         aria-expanded="{{ request()->is('admin/services*') || request()->is('admin/service-prices*') ? 'true' : 'false' }}" aria-controls="sidebarService">
                         <i class="ri-service-line"></i> <span>{{ __('admin.service') }}</span>
                     </a>
                     <div class="collapse menu-dropdown {{ request()->is('admin/services*') || request()->is('admin/service-prices*') ? 'show' : '' }}" id="sidebarService">
                         <ul class="nav nav-sm flex-column">
                             <li class="nav-item">
                                 <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.index') ? 'active' : '' }}">
                                     {{ __('admin.all_services') }}
                                 </a>
                             </li>
                             <li class="nav-item">
                                 <a href="{{ route('admin.service-prices.index') }}" class="nav-link {{ request()->routeIs('admin.service-prices.index') ? 'active' : '' }}">
                                     {{ __('admin.service_prices') }}
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li>






                 {{-- <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Pages</span></li> --}}

                 {{-- nested drop down menu  --}}
                 {{-- <li class="nav-item">
                     <a class="nav-link menu-link" href="#sidebarAuth" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAuth">
                         <i class="ri-account-circle-line"></i> <span data-key="t-authentication">Authentication</span>
                     </a>
                     <div class="collapse menu-dropdown" id="sidebarAuth">
                         <ul class="nav nav-sm flex-column">
                             <li class="nav-item">
                                 <a href="#sidebarSignIn" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSignIn" data-key="t-signin"> Sign
                                     In
                                 </a>
                                 <div class="collapse menu-dropdown" id="sidebarSignIn">
                                     <ul class="nav nav-sm flex-column">
                                         <li class="nav-item">
                                             <a href="auth-signin-basic.html" class="nav-link" data-key="t-basic"> Basic
                                             </a>
                                         </li>
                                         <li class="nav-item">
                                             <a href="auth-signin-cover.html" class="nav-link" data-key="t-cover"> Cover
                                             </a>
                                         </li>
                                     </ul>
                                 </div>
                             </li>
                         </ul>
                     </div>
                 </li> --}}

                 {{-- Loyalty Points --}}
                 <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.loyalty-setting.*') ? 'active' : '' }}" href="{{ route('admin.loyalty-setting.edit') }}">
                         <i class="ri-medal-line"></i> <span>{{ __('admin.loyalty_management') }}</span>
                     </a>
                 </li>





                  <!-- Manage Clients -->
                 <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.manage.*') ? '' : 'collapsed' }}" href="#sidebarManageClients" data-bs-toggle="collapse" role="button"
                         aria-expanded="{{ request()->routeIs('admin.manage.*') ? 'true' : 'false' }}" aria-controls="sidebarManageClients">
                         <i class="ri-user-settings-line"></i> <span data-key="t-manage-clients">{{ __('admin.manage_clients') }}</span>
                     </a>
                     <div class="collapse menu-dropdown {{ request()->routeIs('admin.manage.*') ? 'show' : '' }}" id="sidebarManageClients">
                         <ul class="nav nav-sm flex-column">
                             <li class="nav-item">
                                 <a href="{{ route('admin.manage.salons') }}" class="nav-link {{ request()->routeIs('admin.manage.salons') ? 'active' : '' }}">
                                     {{ __('admin.manage_salons') }}
                                 </a>
                             </li>
                             <li class="nav-item">
                                 <a href="{{ route('admin.manage.barbers') }}" class="nav-link {{ request()->routeIs('admin.manage.barbers') ? 'active' : '' }}">
                                     {{ __('admin.manage_barbers') }}
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li>


                    <!-- Reports & Financials -->
                 <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('admin.reports.*') ? '' : 'collapsed' }}" href="#sidebarReports" data-bs-toggle="collapse" role="button"
                        aria-expanded="{{ request()->routeIs('admin.reports.*') ? 'true' : 'false' }}" aria-controls="sidebarReports">
                        <i class="ri-file-chart-line"></i> <span data-key="t-reports">{{ __('admin.reports_finance') }}</span>
                    </a>
                    <div class="collapse menu-dropdown {{ request()->routeIs('admin.reports.*') ? 'show' : '' }}" id="sidebarReports">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.transactions') }}" class="nav-link {{ request()->routeIs('admin.reports.transactions') ? 'active' : '' }}">
                                    {{ __('admin.all_transactions') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.providers') }}" class="nav-link {{ request()->routeIs('admin.reports.providers') ? 'active' : '' }}">
                                    {{ __('admin.provider_reports') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.bookings') }}" class="nav-link {{ request()->routeIs('admin.reports.bookings') ? 'active' : '' }}">
                                    {{ __('admin.booking_report') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.revenue') }}" class="nav-link {{ request()->routeIs('admin.reports.revenue') ? 'active' : '' }}">
                                    {{ __('admin.revenue_report') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.loyalty') }}" class="nav-link {{ request()->routeIs('admin.reports.loyalty') ? 'active' : '' }}">
                                    {{ __('admin.loyalty_report') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.reports.analytics') }}" class="nav-link {{ request()->routeIs('admin.reports.analytics') ? 'active' : '' }}">
                                    {{ __('admin.business_analytics') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>





                 <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.chat.view') ? 'active' : '' }}" href="{{ route('admin.chat.view') }}">
                         <i class="ri-chat-3-line"></i> <span>{{ __('admin.chat') }}</span>
                     </a>
                 </li>





                 {{-- Settings --}}
                 <li class="menu-title"><span data-key="t-menu">{{ __('admin.settings') }}</span></li>

                 {{-- Settings Section --}}
                 <li class="nav-item">
                     <a class="nav-link menu-link {{ request()->routeIs('admin.system-settings.*') || request()->routeIs('admin.mail-settings.*') || request()->routeIs('admin.profile-settings.*') || request()->routeIs('admin.payment-settings.*') || request()->routeIs('admin.commission-settings.*') ? '' : 'collapsed' }}"
                         href="#sidebarSettings" data-bs-toggle="collapse" role="button"
                         aria-expanded="{{ request()->routeIs('admin.system-settings.*') || request()->routeIs('admin.mail-settings.*') || request()->routeIs('admin.profile-settings.*') || request()->routeIs('admin.payment-settings.*') || request()->routeIs('admin.commission-settings.*') ? 'true' : 'false' }}"
                         aria-controls="sidebarSettings">
                         <i class="ri-settings-3-line"></i> <span>{{ __('admin.settings') }}</span>
                     </a>

                     <div class="collapse menu-dropdown {{ request()->routeIs('admin.stripe-settings.*') || request()->routeIs('admin.system-settings.*') || request()->routeIs('admin.mail-settings.*') || request()->routeIs('admin.profile-settings.*') || request()->routeIs('admin.payment-settings.*') || request()->routeIs('admin.social-settings.*') || request()->routeIs('admin.commission-settings.*') ? 'show' : '' }}"
                         id="sidebarSettings">

                         <ul class="nav nav-sm flex-column">
                             {{-- Profile Settings --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.profile-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.profile-settings.*') ? 'active' : '' }}">
                                     <i class="ri-user-settings-line"></i> <span>{{ __('admin.profile_settings') }}</span>
                                 </a>
                             </li>

                             {{-- dynamic pages --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.dynamic.index') }}" class="nav-link {{ request()->routeIs('admin.dynamic.*') ? 'active' : '' }}">
                                     <i class="ri-pages-line"></i> <span>{{ __('admin.dynamic_pages') }}</span>
                                 </a>
                             </li>

                             {{-- Social Settings --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.social-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.social-settings.*') ? 'active' : '' }}">
                                     <i class="ri-share-line"></i> <span>{{ __('admin.social_settings') }}</span>
                                 </a>
                             </li>

                             {{-- Stripe Settings --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.stripe-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.stripe-settings.*') ? 'active' : '' }}">
                                     <i class="ri-mail-settings-line"></i> <span>{{ __('admin.stripe_settings') }}</span>
                                 </a>
                             </li>

                             {{-- Commission Settings --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.commission-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.commission-settings.*') ? 'active' : '' }}">
                                     <i class="ri-percent-line"></i> <span>{{ __('admin.commission_settings') }}</span>
                                 </a>
                             </li>

                             {{-- System Settings --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.system-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.system-settings.*') ? 'active' : '' }}">
                                     <i class="ri-settings-3-line"></i> <span>{{ __('admin.system_settings') }}</span>
                                 </a>
                             </li>

                             {{-- Mail Settings --}}
                             <li class="nav-item">
                                 <a href="{{ route('admin.mail-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.mail-settings.*') ? 'active' : '' }}">
                                     <i class="ri-mail-settings-line"></i> <span>{{ __('admin.mail_settings') }}</span>
                                 </a>
                             </li>
                         </ul>
                     </div>
                 </li>

             </ul>
         </div>
         <!-- Sidebar -->
     </div>

     <div class="sidebar-background"></div>
 </div>
