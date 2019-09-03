<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        body , html{
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        header {
            width: 100%;
            max-height: 80px;
            background-color: black;
            border-bottom: 3px solid orange;
        }

        header img {
            height: 14%;
            width: 20%;
            padding: 15px
        }

        .container  {
            width: 70%;
            margin: 0 auto;
            /*  margin-top: 7%;
              margin-bottom: 7%; */

        }

        .container .text {
            margin:0 auto;
            display: block;
            width:60% !important;
            height: 100%;
        }

        .container  p{
            /*font-size: 2em;*/
            display: block
        }
        .container  h1{
            text-align: center;
            font-size: 2rem;
        }

        footer {
            height: 6%;
            background: black;
            text-align: center;
            margin: auto;
            width: 100%;
            border-top: 3px solid orange;

        }

        footer a {
            display: inline-block;
            border-right: 1px solid orange;
            font-size: 1em;
            margin: auto;
            text-align: center;
            color: white;
            padding-left:2%;
            padding-right: 2%;
            margin-top: 7px;
            text-decoration: none;
        }

        @media screen and (max-width: 768px) {
            .container  h1{
                font-size: 1.5rem;
            }
        }
        @media screen and (max-width: 576px) {
            .container  {
                width: 90%;
                margin: auto;
            }
            .container p {
                font-size: 1em;
                margin-bottom: 8px;
            }
            footer a {
                display: inline-block;
                border-right: 1px solid orange;
                font-size: .8em;
                margin: auto;
                text-align: center;
                color: white;
                padding-right: 3%;
                margin-top: 7px;
                margin-left: 1% !important;
            }
        }

        @media screen and (max-width:375px) {
            .container .text {
                font-size: 1em;
                margin: 0 auto;
            }
            .container  h1{
                font-size: 1.2rem;
            }
            .container p {
                font-size: 0.9em;
                margin-bottom: 5px;
            }
        }
    </style>
    @if(Config::get('app.locale') == 'fa')
        <STYLE>
            @font-face {
                font-family: BYekanFont;
                src: url({{asset('fonts/BYekan.ttf')}});
            }
            * {
                font-family: BYekanFont;
            }
            h1, h2, h3, h4, h5, h6 {
                font-family: BYekanFont;
            }
            th, a, p, input, button, legend, label {font-family: BYekanFont;}
            .btn {font-family: BYekanFont;}
        </STYLE>
    @endif
</head>
<body>
<header>
    <div class="logo-header"><img src="{{asset('img/Logo_header.svg')}}" alt="logo_header"></div>
</header>
<div class="container">
    @if(\Illuminate\Support\Facades\Config::get('app.locale') == 'fa')
        <h1> به هش بازار خوش آمدید </h1>
        <p style="text-align: center;">با تشکر از عضویت شما در هش بازار. برای فعال سازی حساب خود رو دکمه فعال سازی کلیک کنید</p>
        <a href="{{route($route,['locale'=>session('locale'),'token'=>$user->verifyUser->token])}}"><button style="cursor: pointer;margin: 10px;display:inline-block; font-size: 15px;color:white;background: orange;padding: 10px;border-radius:10px">فعال سازی</button></a>
    @else
        <h1> Welcome to Hash Bazaar </h1>
        <p style="text-align: center;">Thanks for your registration. From now on you join our community.</p>
        <p>It means you have the permission to invest in Hash Bazaar by yourself and use your Investment ID as your referral code to invite your friends. More friends, more bonuses. You can find our reward program from your panel.</p>
    @endif

</div>

@include('email.master.footer')
</body>
</html>