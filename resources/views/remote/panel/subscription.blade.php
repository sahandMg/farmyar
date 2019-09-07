@extends('remote.layout')
@section('title')

@endsection
<?php
$setting = App\Setting::first();
?>
@section('content')
<h2 class="title-1 m-b-25 text-right" style="direction: rtl;">لیست خرید اشتراک ها</h2>
@if(count($orders) > 0)
<div class="table-responsive table--no-card m-b-40" style="direction: rtl;">
        <table class="table table-borderless table-striped table-earning">
            <thead>
                <tr class="text-center">
                    <th>تاریخ</th>
                    <th>تعداد دستگاه</th>
                    <th>مدت زمان</th>
                    <th>مبلغ پرداختی</th>
                    <th>مدت زمان باقی مانده</th>
                </tr>
            </thead>
            <tbody>
            @foreach($orders as $order)
                <tr class="text-center">
                    <td>{{\Morilog\Jalali\Jalalian::fromCarbon(Carbon\Carbon::parse($order->created_at))->format('d m Y')}}</td>
                  <td>{{$order->devices}}</td>
                  <td> {{$order->months}}</td>
                  <td>{{$order->transaction->amount}}</td>
                <?php $days = (Carbon\Carbon::now()->diffInDays(Carbon\Carbon::parse($order->created_at)->addMonths($order->months))) ?>
                    <td> {{$days > 30?("1 ماه ".($days - 30)):$days}} روز</td>
                </tr>
                @endforeach
            </tbody>
        </table>
  </div>
@endif
   <br/>
  <!--  <div class="au-card text-right" style="direction: rtl;">
       <h3 class="m-b-25 text-right">مدت زمان و تعداد دستگاه های خود را انتخاب کنید.</h3>
       @if($setting->zarrin_active)
       <form action="{{route('RemoteZarrinPalPaying',['locale'=>App::getLocale()])}}" class="was-validated" >
       @elseif($setting->paystar_active)
       <form action="{{route('RemotePaystarPaying',['locale'=>App::getLocale()])}}" class="was-validated" >
       @endif
          <label for="devices">تعداد دستگاه ها : <span id="devicesValue">12</span></label>
          <input style="direction: rtl;" type="range" class="custom-range" id="devices" name="devices" min="1" max="500">
          <label for="months">تعداد ماه ها : <span id="timesValue">10 ماه</span></label>
          <input  style="direction: rtl;" type="range" class="custom-range" id="times" name="months" min="1" max="12">
          <br/><br/>
          <label>مبلغ پرداختی : <span id="cost"></span></label>
          <br/><br/>
          <div class="text-center">
              <button class="btn btn-success">خرید</button>
          </div>
       </form>
   </div> -->
   <div class="card" style="direction: rtl;text-align: right;">
        <div class="card-header">خرید شتراک</div>
        <div class="card-body">
            <div class="card-title">
                <h3 class="text-right title-2">مدت زمان و تعداد دستگاه های خود را انتخاب کنید.</h3>
            </div>
            <hr/>
           @if($setting->zarrin_active)
             <form action="{{route('RemoteZarrinPalPaying',['locale'=>App::getLocale()])}}" class="was-validated" >
           @elseif($setting->paystar_active)
             <form action="{{route('RemotePaystarPaying',['locale'=>App::getLocale()])}}" class="was-validated" >
           @endif
           <div class="form-group">
              <label for="cc-payment" class="control-label mb-1">تعداد دستگاه ها :</label>
              <input name="devices" type="number" class="form-control" aria-required="true" aria-invalid="false" value="1" min="1" id="devices"  onchange="devicesChange()" />
           </div>
           <div class="form-group has-success">
              <label for="cc-name" class="control-label mb-1">تعداد ماه ها :</label>
              <input name="months" type="number" class="form-control cc-name valid" data-val="true" value="1"  aria-required="true"  id="times"   onchange="devicesChange()"  min="1" max="12" />
              <!-- <span class="help-block field-validation-valid" data-valmsg-for="cc-name" data-valmsg-replace="true"></span> -->
            </div>
             <br/>
              <label>مبلغ پرداختی : <span id="cost"></span></label>
             <br/>
                                      <!--       <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label for="cc-exp" class="control-label mb-1">Expiration</label>
                                                        <input id="cc-exp" name="cc-exp" type="tel" class="form-control cc-exp" value="" data-val="true" data-val-required="Please enter the card expiration" data-val-cc-exp="Please enter a valid month and year" placeholder="MM / YY" autocomplete="cc-exp">
                                                        <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label for="x_card_code" class="control-label mb-1">Security code</label>
                                                    <div class="input-group">
                                                        <input id="x_card_code" name="x_card_code" type="tel" class="form-control cc-cvc" value="" data-val="true" data-val-required="Please enter the security code" data-val-cc-cvc="Please enter a valid security code" autocomplete="off">

                                                    </div>
                                                </div>
                                            </div -->>
                <div>
                 <!--  <button id="payment-button" type="submit" class="btn btn-lg btn-info btn-block">
                     <i class="fa fa-lock fa-lg"></i>&nbsp;
                     <span id="payment-button-amount">Pay $100.00</span>
                     <span id="payment-button-sending" style="display:none;">Sending…</span>
                  </button> -->
                  <div class="text-center">
                     <button class="btn btn-success">خرید</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
   <br/><br/><br/><br/><br/>
 @include('remote/scripts')
   <script type="text/javascript">
       var devices = document.getElementById("devices");
       var times = document.getElementById("times");
       console.log(devices);console.log(times);
       // var devicesValue = document.getElementById("devicesValue");
       // var timesValue = document.getElementById("timesValue");
       var cost = document.getElementById("cost");
        var price = 10000;
        // devicesValue.innerHTML = devices.value; 
        // timesValue.innerHTML = times.value + ' ماه';
        cost.innerHTML = ( parseInt(devices.value)*price * parseInt(times.value ) ) + ' تومان' ;
        devices.addEventListener('input', onChange);
        times.addEventListener('input', onChange);

        function devicesChange() {
          var devicesNum = parseInt(devices.value);
          var monthNum = parseInt(times.value);
          var discount = 1;
          if(monthNum<3) {discount = 1;}
          else if(monthNum<6) {discount = 0.9;}
          else if(monthNum<12) {discount = 0.85;}
          else {discount = 0.8;}

          if(devicesNum < 11) {price = 10000;} 
          else if(devicesNum < 51) {price = 8000;}
          else if(devicesNum < 101) {price = 7000;}
          else if(devicesNum < 501) {price = 5000;}
          else {price = 4000;}
          cost.innerHTML = `<strong>`+(parseInt(devices.value)*price*discount* parseInt(times.value )) + ' تومان </strong>' ;
        }


       


        // devices.oninput = function() {
        //      devicesValue.innerHTML = devices.value; 
        //      cost.innerHTML = (parseInt(devices.value)*price * parseInt(times.value )) + ' تومان' ;
        // }

        // times.oninput = function() {
        //     timesValue.innerHTML = times.value + ' ماه';
        //     cost.innerHTML = (parseInt(devices.value)*price * parseInt(times.value )) + ' تومان' ;
        // }
   </script>
@endsection