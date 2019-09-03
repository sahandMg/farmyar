<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="keywords"
          content="Bitcoin mining, script mining, cloud mining, hosted mining"/>
    <meta name="description"
          content="Bitcoin is the digital gold of the future & HashBazaar is the most cost effective cloud mining company on the market. Mine bitcoin through the cloud, get started today!"/>
   <meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
    <title>Wallet Address Change</title>
    <style>
        body , html{
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            max-width: 960px;
            margin: auto;
        }

        header {
            width: 100%;
            max-height: 80px;
            background-color: black;
            border-bottom: 3px solid orange;
        }

        header img {
             height: 40px;
            width: auto;
            padding: 15px
        }

        .container  {
            width: 70%;
            margin: 0 auto;          
        }

        .container .text {
            margin:0 auto;
            display: block;
            width:60% !important;
            height: 100%;
        }

        .container  p{
            font-family: sans-serif;
            display: block;
            margin: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        .container  h1{
            text-align: left; 
            font-size: 2rem;
            margin:0; mso-line-height-rule:exactly;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        footer {
            height: 60px;
            background: black;
            text-align: center;
            margin: auto;
            width: 100%;
            border-top: 3px solid orange;
            margin-top: 120px;
            
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
</head>
<body>
    <header>
        <div class="logo-header"><img src="{{asset('img/Logo_header.svg')}}" alt="logo_header"></div>
    </header>
    @if(session('locale') != 'fa')
        <div class="container">
                <h1>Confirm your wallet address</h1>
                <p style="text-align: left;">Hello, confirm your wallet address by clicking below link</p>
                <p style="text-align: left;">New Wallet Address: <b>{{$wallet}}</b></p>
                <p style="text-align: left;"><a href="{{route('RedirectWallet',['locale'=>session('locale')])}}?token={{$user->verifyUser->token}}&address={{$wallet}}"> {{$wallet}}</a></p>
        </div>
     @else
        <div class="container">
            <h1 style="text-align: right">تایید آدرس کیف پول</h1>
            <p style="text-align: right;">لطفا برای تایید آدرس جدید کیف پول خود، روی لینک زیر کلیک کنید</p>
            <p style="text-align: right;direction: rtl">آدرس جدید: </p>
            <b style="text-align: left;"><a href="{{route('RedirectWallet',['locale'=>session('locale')])}}?token={{$user->verifyUser->token}}&address={{$wallet}}"> {{$wallet}}</a></b>
        </div>
    @endif
    @include('email.master.footer')
</body>
</html>