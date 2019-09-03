<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="au theme template">
    <meta name="author" content="Hau Nguyen">
    <meta name="keywords" content="au theme template">

    <!-- Title Page-->
    <title>HashBazaar</title>

     <script  src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js" ></script>
     <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <!-- Fontfaces CSS-->
    <!-- <link href="css/font-face.css" rel="stylesheet" media="all"> -->
    <link href="{{URL::asset('remoteDashboard/vendor/font-awesome-4.7/css/font-awesome.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/font-awesome-5/css/fontawesome-all.min.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/mdi-font/css/material-design-iconic-font.min.css')}}" rel="stylesheet" media="all">

    <!-- Bootstrap CSS-->
    <link href="{{URL::asset('remoteDashboard/vendor/bootstrap-4.1/bootstrap.min.css')}}" rel="stylesheet" media="all">

    <!-- Vendor CSS-->
    <link href="{{URL::asset('remoteDashboard/vendor/animsition/animsition.min.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/wow/animate.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/css-hamburgers/hamburgers.min.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/slick/slick.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/select2/select2.min.css')}}" rel="stylesheet" media="all">
    <link href="{{URL::asset('remoteDashboard/vendor/perfect-scrollbar/perfect-scrollbar.css')}}" rel="stylesheet" media="all">
<!-- Hotjar Tracking Code for http://hashbazaar.com/ -->
   <script>
      (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:1240497,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
      })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
    </script>
    <!-- Main CSS-->
    <link href="{{URL::asset('remoteDashboard/css/theme.css')}}" rel="stylesheet" media="all">
    <STYLE>
      @font-face {
        font-family: BYekanFont;
        src: url({{asset('fonts/BYekan.ttf')}});
        /*unicode-range: U+0025-00FF;*/
        /*unicode-range: U+30-39;*/
      }
      /*@font-face {
        font-family: sans-serif;
        src: url({{asset('fonts/BYekan.ttf')}});
      }*/
      * {
        font-family: BYekanFont;
      }
      h1, h2, h3, h4, h5, h6, div {
        font-family: BYekanFont;
      }
      th, a, p, input, button, legend, label, span {font-family: BYekanFont;}
      /*.btn {font-family: sans-serif, BYekanFont;}*/
      .englishFont {font-family: sans-serif;}
    </STYLE>
</head>

<body class="animsition">
    <div class="page-wrapper">
        <!-- HEADER MOBILE-->
         @include('remote/mobileHeader')
        <!-- END HEADER MOBILE-->

        <!-- MENU SIDEBAR-->
         @include('remote/sidebarMenu')
        <!-- END MENU SIDEBAR-->
        <!-- PAGE CONTAINER-->
        <div class="page-container">
         <!-- HEADER DESKTOP-->
            @include('remote/desktopHeader')
          <!-- HEADER DESKTOP-->   
         <!-- MAIN CONTENT-->  
         <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">  
                       @yield('content')
                     </div>
                </div>
        </div>   
        <!-- END MAIN CONTENT-->
        <!-- END PAGE CONTAINER-->
       </div>

    </div>

  
    <script type="text/javascript">
        if(screen.width<920) {
           $('.header-desktop').hide(); 
        }
    </script>
</body>

</html>
<!-- end document-->