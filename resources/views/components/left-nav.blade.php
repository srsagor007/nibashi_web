<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <a href="{{ route('dashboard') }}">
            {{-- <img alt="Logo" src="{{ asset('storage/' . settings('site_info', 'site_logo')) }}"
                    class="h-40px app-sidebar-logo-default" /> --}}
            
            @if(settings('site_info', 'site_logo') )
                {{-- @if( file_exists( asset('storage/' . settings('site_info', 'site_logo')) ) ) --}}
                <img alt="Logo" src="{{ asset('storage/' . settings('site_info', 'site_logo'))  }}"
                    class="h-40px app-sidebar-logo-default" />
                <img alt="Logo" src="{{ asset('storage/' . settings('site_info', 'site_logo'))  }}"
                    class="h-30px app-sidebar-logo-minimize" />
                {{-- @else
                <h2 class="text-white">{{ settings('site_info', 'site_title') }}</h2>
                @endif --}}
            @else
                <h2 class="text-white">{{ settings('site_info', 'site_title') }}</h2>
            @endif
        </a>
        <!--end::Logo image-->
        <!--begin::Sidebar toggle-->
        <!--begin::Minimized sidebar setup:
            if (isset($_COOKIE["sidebar_minimize_state"]) && $_COOKIE["sidebar_minimize_state"] === "on") {
                1. "src/js/layout/sidebar.js" adds "sidebar_minimize_state" cookie value to save the sidebar minimize state.
                2. Set data-kt-app-sidebar-minimize="on" attribute for body tag.
                3. Set data-kt-toggle-state="active" attribute to the toggle element with "kt_app_sidebar_toggle" id.
                4. Add "active" class to to sidebar toggle element with "kt_app_sidebar_toggle" id.
            }
        -->
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
            data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-duotone ki-black-left-line fs-3 rotate-180">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </div>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->

    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <!--begin::Scroll wrapper-->
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">
                <!--begin::Menu-->
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
                    data-kt-menu="true" data-kt-menu-expand="false">

                    {{-- Icons Link --}}
                    {{-- https://preview.keenthemes.com/html/metronic/docs/icons/keenicons --}}
                    @foreach ($menus as $menu)
                        @php
                            $menu_url = '#';
                            $menu_dropdown_class = '';
                            if ($menu->route_name && Route::has($menu->route_name)) {
                                $menu_url = route($menu->route_name);
                            } else {
                                $menu_url = $menu->menu_url ? url($menu->menu_url) : '#';
                            }

                            if ($menu->sub_menus->count() > 0) {
                                $menu_url = '#';
                                $menu_dropdown_class = 'nav-main-link-submenu';
                            }

                            $main_menu = isset($mm) ? $mm : request()->mm ?? '';
                            $sub_menu = isset($sm) ? $sm : request()->sm ?? '';

                            $main_menu = $main_menu ? \Str::of($main_menu)->slug('_') : '';
                            $sub_menu = $sub_menu ? \Str::of($sub_menu)->slug('_') : '';

                            session(['mm' => $main_menu, 'sm' => $sub_menu]);
                        @endphp

                        @php
                            $mm_title_slug = \Str::of($menu->title)->slug('_');
                            $mm_url = $menu_url . ($menu_url != '#' ? '?mm=' . $mm_title_slug : '');
                        @endphp
                        <!--begin:Menu item-->
                        <div class="menu-item
                            {{ isset($main_menu) && $main_menu == $mm_title_slug ? 'here show' : '' }}
                            {{ $menu->sub_menus->count() ? 'menu-accordion' : '' }}"
                            data-kt-menu-trigger="{{ $menu->sub_menus->count() ? 'click' : '' }}">
                            <!--begin:Menu link-->
                            @if ($menu->sub_menus->count() == 0)
                                <a class="menu-link" href="{{ $mm_url }}">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-{{ $menu->menu_icon ?? 'element-11' }} fs-2"></i>
                                    </span>
                                    <span class="menu-title">{{ $menu->title }}</span>
                                </a>
                            @else
                                <span class="menu-link">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-{{ $menu->menu_icon ?? 'element-11' }} fs-2"></i>
                                    </span>
                                    <span class="menu-title">{{ $menu->title }}</span>
                                    <span class="menu-arrow"></span>
                                </span>

                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    @foreach ($menu->sub_menus as $child_menu)
                                    @php
                                        $mm_title_slug = \Str::of($menu->title)->slug('_');
                                        $sm_title_slug = \Str::of($child_menu->title)->slug('_');
                                        $title_slug = "mm={$mm_title_slug}&sm={$sm_title_slug}";
                                        $sm_url = $child_menu->route_name && Route::has($child_menu->route_name) ? route($child_menu->route_name) : ($child_menu->menu_url ? url($child_menu->menu_url) : '#');
                                        $sm_url .= $sm_url != '#' ? "?{$title_slug}" : '';
                                    @endphp
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link {{ isset($sub_menu) && $sub_menu == $sm_title_slug ? 'active' : '' }}"
                                            href="{{ $sm_url }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot"></span>
                                            </span>
                                            <span class="menu-title">{{ $child_menu->title }}</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    @endforeach
                                </div>
                                <!--end:Menu sub-->
                            @endif
                            <!--end:Menu link-->

                        </div>
                        <!--end:Menu item-->
                    @endforeach
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Scroll wrapper-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::sidebar menu-->

    @if (env('APP_ENV') == 'local')
        <!--begin::Footer-->
        <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
            <a href="https://preview.keenthemes.com/html/metronic/docs"
               class="btn btn-flex flex-center btn-custom btn-primary overflow-hidden text-nowrap px-0 h-40px w-100"
               data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click"
               title="Visit Website">
                <span class="btn-label">Documentations</span>
                <i class="ki-outline ki-document btn-icon fs-2 m-0">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </a>
        </div>
        <!--end::Footer-->
    @endif
</div>
