<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Admin Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.21/dist/vue.js"></script>
    <script  src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js" ></script>
    <script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- Our Custom CSS -->
    <link rel="stylesheet" href="{{URL::asset('css/sidebar.css')}}">

    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous"></script>
    <link rel="icon" href="{{URL::asset('img/favicon.ico')}}" type="image/x-icon"/>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
</head>

<body>

<div class="wrapper">
    <!-- Sidebar  -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>Management</h3>
            <strong>MG</strong>
        </div>


            <ul class="list-unstyled components">

                @if(Request::route()->getName() == 'adminHome')

                    <li  class="active">
                @else
                    <li>
                        @endif

                        <a href="#homeSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                            <i class="fas fa-home"></i>
                            <span style="padding-left: 20px"> صفحه اصلی</span>
                        </a>
                        <ul class="collapse list-unstyled" id="homeSubmenu">

                            <li>
{{--                                <a href="{{route('adminHome',['locale'=>session('locale')])}}">داشبورد</a>--}}
                            </li>

                        </ul>
                    </li>

                @if(Request::route()->getName() == 'adminGetUsersList')

            <li  class="active">
            @else
                <li>
            @endif
                <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-users" style='font-size:20px'></i>
                    <span style="padding-left: 20px">کاربران</span>
                </a>
                <ul class="collapse list-unstyled" id="pageSubmenu">
                    <li>
                        <a href="{{route('adminGetUsersList',['locale'=>session('locale')])}}">لیست کاربران</a>
                    </li>
                    <li>
                        <a href="#">Page 3</a>
                    </li>
                </ul>
            </li>



            @if(Request::route()->getName() == 'adminTransactions')
            <li  class="active">
            @else
                <li>
            @endif
                <a href="{{route('adminTransactions',['locale'=>session('locale')])}}">
                    <i  class="fab fa-bitcoin" style='font-size:20px'></i>
                    <span style="padding-left: 20px">تراکنش ها</span>
                </a>
            </li>


            @if(Request::route()->getName() == 'adminRedeems')
            <li  class="active">
            @else
                <li>
            @endif
                <a href="{{route('adminRedeems',['locale'=>session('locale')])}}">
                    <i class="fas fa-money-check-alt"></i>
                    <span style="padding-left: 20px">پرداخت شده به کاربر</span>
                </a>
            </li>


            @if(Request::route()->getName() == 'adminCheckout')
                <li  class="active">
            @else
                <li>
                    @endif
                    <a href="{{route('adminCheckout',['locale'=>session('locale')])}}">
                        <i class='fas fa-money-check' style='font-size:20px'></i>
                        <span style="padding-left: 20px">تسویه با کاربر</span>
                    </a>
                </li>

            @if(Request::route()->getName() == 'siteSetting')
                <li  class="active">
            @else
                <li>
                    @endif
                    <a href="{{route('siteSetting',['locale'=>session('locale')])}}">
                        <i class="fas fa-pencil-alt" style='font-size:20px'></i>
                        <span style="padding-left: 20px">تنظیمات</span>
                    </a>
                </li>
                @if(Request::route()->getName() == 'hardwareOrders')
                    <li  class="active">
                @else
                    <li>
                        @endif
                        <a href="{{route('hardwareOrders',['locale'=>session('locale')])}}">
                            <i class="fas fa-pencil-alt" style='font-size:20px'></i>
                            <span style="padding-left: 20px">سفارش سخت افزار</span>
                        </a>
                    </li>
                @if(Request::route()->getName() == ' getLogs')
                    <li  class="active">
                @else
                    <li>
                        @endif
                        <a href="{{route('getLogs',['locale'=>session('locale')])}}">
                            <i class="fas fa-pencil-alt" style='font-size:20px'></i>
                            <span style="padding-left: 20px">لاگ</span>
                        </a>
                    </li>
                @if(Request::route()->getName() == 'AdminMessage')
                    <li  class="active">
                @else
                    <li>
                        @endif
                        <a href="{{route('AdminMessage',['locale'=>session('locale')])}}">
                            <i class="far fa-comment-dots" style='font-size:20px'></i>
                            <span style="padding-left: 20px">پیام ها</span>
                        </a>
                    </li>

                @if(Request::route()->getName() == 'adminLogout')
                    <li  class="active">
                @else
                    <li>
                        @endif
                        <a href="{{route('adminLogout',['locale'=>session('locale')])}}">
                            <i class="far fa-share-square" style='font-size:20px'></i>
                            <span style="padding-left: 20px">خروج</span>
                        </a>
                    </li>
        </ul>

    </nav>

    <!-- Page Content  -->
    <div id="content" class="app">

        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">

                <button type="button" id="sidebarCollapse" class="btn btn-info">
                    <i class="fas fa-align-left"></i>
                    <span>Toggle Sidebar</span>
                </button>
                <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-align-justify"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    {{--<ul class="nav navbar-nav ml-auto">--}}
                        {{--<li class="nav-item active">--}}
                            {{--<a class="nav-link" href="#">Page</a>--}}
                        {{--</li>--}}
                        {{--<li class="nav-item">--}}
                            {{--<a class="nav-link" href="#">Page</a>--}}
                        {{--</li>--}}
                        {{--<li class="nav-item">--}}
                            {{--<a class="nav-link" href="#">Page</a>--}}
                        {{--</li>--}}
                        {{--<li class="nav-item">--}}
                            {{--<a class="nav-link" href="#">Page</a>--}}
                        {{--</li>--}}
                    {{--</ul>--}}
                </div>
            </div>
        </nav>
@yield('content')


    <!-- jQuery CDN - Slim version (=without AJAX) -->
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <!-- Popper.JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
        <!-- Bootstrap JS -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>

        <script type="text/javascript">
            $(document).ready(function () {
                $('#sidebarCollapse').on('click', function () {
                    $('#sidebar').toggleClass('active');
                });
            });
        </script>
</div>
</body>
</html>