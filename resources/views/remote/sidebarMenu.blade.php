<aside class="menu-sidebar d-none d-lg-block">
            <div class="logo" style="justify-content: center;">
                <a href="{{route('index',['locale'=>App::getLocale()])}}">
                    <!-- <img src="{{URL::asset('remoteDashboard/images/icon/logo.png')}}" alt="Cool Admin" /> -->
                    <img src="{{URL::asset('img/farmyar.svg')}}" alt="HashBazaar" style="height: 51px;" />
                </a>
            </div>
            <div class="menu-sidebar__content js-scrollbar1">
                <nav class="navbar-sidebar">
                    <ul class="list-unstyled navbar__list">
                        <li class="{{Request::route()->getName() == 'remoteDashboard'?'active has-sub':null}}">
                            <a class="js-arrow" href="{{route('remoteDashboard',['locale'=>App::getLocale()])}}">
                                <i class="fas fa-tachometer-alt"></i> داشبورد</a>
                        <!--     <ul class="list-unstyled navbar__sub-list js-sub-list">
                                <li>
                                    <a href="index.html">Dashboard 1</a>
                                </li>
                                <li>
                                    <a href="index2.html">Dashboard 2</a>
                                </li>
                                <li>
                                    <a href="index3.html">Dashboard 3</a>
                                </li>
                                <li>
                                    <a href="index4.html">Dashboard 4</a>
                                </li>
                            </ul> -->
                        </li>

                        <li class="{{Request::route()->getName() == 'minerStatus'?'active has-sub':null}}">
                            <a class="js-arrow" href="{{route('minerStatus',['locale'=>App::getLocale()])}}">
                                <i class="fas fa-table"></i>مانیتورینگ وضعیت ماینرها</a>
                            <!--     <ul class="list-unstyled navbar__sub-list js-sub-list">
                                    <li>
                                        <a href="index.html">Dashboard 1</a>
                                    </li>
                                    <li>
                                        <a href="index2.html">Dashboard 2</a>
                                    </li>
                                    <li>
                                        <a href="index3.html">Dashboard 3</a>
                                    </li>
                                    <li>
                                        <a href="index4.html">Dashboard 4</a>
                                    </li>
                                </ul> -->
                        </li>
                        <!-- <li>
                            <a href="chart.html">
                                <i class="fas fa-chart-bar"></i>Charts</a>
                        </li> -->
                        <!-- <li>
                            <a href="table.html">
                                <i class="fas fa-table"></i>Tables</a>
                        </li> -->
                        <li class="{{Request::route()->getName() == 'remoteSubscription'?'active has-sub':null}}">
                            <a href="{{route('remoteSubscription',['locale'=>App::getLocale()])}}">
                                <i class="far fa-check-square"></i> خرید اشتراک</a>
                        </li>

                        <li class="{{Request::route()->getName() == 'hardware'?'active has-sub':null}}">
                            <a href="{{route('hardware',['locale'=>App::getLocale()])}}">
                                <i class="zmdi zmdi-shopping-basket"></i>خرید سخت افزار</a>
                        </li>

                        <li class="{{Request::route()->getName() == 'tutorials'?'active has-sub':null}}">
                            <a href="{{route('tutorials',['locale'=>App::getLocale()])}}">
                                <i class="zmdi zmdi-book"></i>آموزش ها</a>
                        </li>
                        <!-- <li>
                            <a href="#">
                                <i class="fas fa-calendar-alt"></i>Calendar</a>
                        </li> -->
                        <li>
                            <a href="{{route('logout',['locale'=>App::getLocale()])}}">
                                <i class="zmdi zmdi-power"></i>خروج</a>
                        </li>
                        <!-- <li>
                            <a href="map.html">
                                <i class="fas fa-map-marker-alt"></i>Maps</a>
                        </li> -->
                      <!--   <li class="has-sub">
                            <a class="js-arrow" href="#">
                                <i class="fas fa-copy"></i>Pages</a>
                            <ul class="list-unstyled navbar__sub-list js-sub-list">
                                <li>
                                    <a href="login.html">Login</a>
                                </li>
                                <li>
                                    <a href="register.html">Register</a>
                                </li>
                                <li>
                                    <a href="forget-pass.html">Forget Password</a>
                                </li>
                            </ul>
                        </li> -->
                        <!-- <li class="has-sub">
                            <a class="js-arrow" href="#">
                                <i class="fas fa-desktop"></i>UI Elements</a>
                            <ul class="list-unstyled navbar__sub-list js-sub-list">
                                <li>
                                    <a href="button.html">Button</a>
                                </li>
                                <li>
                                    <a href="badge.html">Badges</a>
                                </li>
                                <li>
                                    <a href="tab.html">Tabs</a>
                                </li>
                                <li>
                                    <a href="card.html">Cards</a>
                                </li>
                                <li>
                                    <a href="alert.html">Alerts</a>
                                </li>
                                <li>
                                    <a href="progress-bar.html">Progress Bars</a>
                                </li>
                                <li>
                                    <a href="modal.html">Modals</a>
                                </li>
                                <li>
                                    <a href="switch.html">Switchs</a>
                                </li>
                                <li>
                                    <a href="grid.html">Grids</a>
                                </li>
                                <li>
                                    <a href="fontawesome.html">Fontawesome Icon</a>
                                </li>
                                <li>
                                    <a href="typo.html">Typography</a>
                                </li>
                            </ul>
                        </li> -->
                    </ul>
                </nav>
            </div>
        </aside>
