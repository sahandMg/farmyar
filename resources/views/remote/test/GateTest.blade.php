<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
         <script src="https://cdn.jsdelivr.net/npm/vue@2.5.21/dist/vue.js"></script>
             <script  src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js" ></script>
                <script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.js"></script>
                 <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
         <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
             <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
             <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <title>Gate Test</title>
</head>
<body>
<div class="container">
     <form style="padding: 20px;" method="POST" action="{{route('RemoteZarrinPalPayingTest',['locale'=>App::getLocale()])}}">
              <input type="hidden" name="_token" value="{{csrf_token()}}">
           <div class="form-group">

             <input name="months" type="hidden" value="1" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="نام کاربری خود را وارد کنید">
           </div>

           <div class="form-group">
             <input name="devices" type="hidden" class="form-control" value="10" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
           </div>

            <button type="submit" class="btn btn-success">تراکنش موفق زرین پال </button>

           </form>

    <form style="padding: 20px;" method="POST" action="{{route('RemotePaystarPayingTest',['locale'=>App::getLocale()])}}">
        <input type="hidden" name="_token" value="{{csrf_token()}}">
        <div class="form-group">

            <input name="months" type="hidden" value="1" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="نام کاربری خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="devices" type="hidden" class="form-control" value="10" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>

        <button type="submit" class="btn btn-success">تراکنش موفق پی استار </button>

    </form>


    <form style="padding: 20px;" method="POST" action="{{route('RemoteHardwareZarrinPalPayingTest',['locale'=>App::getLocale()])}}">
        <input type="hidden" name="_token" value="{{csrf_token()}}">
        <div class="form-group">

            <input name="name" type="hidden" value="sahand" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="نام کاربری خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="phone" type="hidden" class="form-control" value="09387782916" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="address" type="hidden" class="form-control" value="ایران تهران" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="post" type="hidden" class="form-control" value="۰۹۳۲۱۲۳۲۱" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>

        <button type="submit" class="btn btn-success">تراکنش موفق زرین پال سخت </button>

    </form>



    <form style="padding: 20px;" method="POST" action="{{route('RemoteHardwarePaystarPayingTest',['locale'=>App::getLocale()])}}">
        <input type="hidden" name="_token" value="{{csrf_token()}}">
        <div class="form-group">

            <input name="name" type="hidden" value="1" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="نام کاربری خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="phone" type="hidden" class="form-control" value="10" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="address" type="hidden" class="form-control" value="10" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>

        <div class="form-group">
            <input name="post" type="hidden" class="form-control" value="10" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="ایمیل خود را وارد کنید">
        </div>


        <button type="submit" class="btn btn-success">تراکنش موفق پی استار سخت </button>

    </form>

</div>
</body>
</html>