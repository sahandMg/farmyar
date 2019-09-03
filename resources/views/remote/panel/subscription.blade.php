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
   <div class="au-card text-right" style="direction: rtl;">
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
   </div>
   <br/><br/><br/><br/><br/>
 @include('remote/scripts')
   <script type="text/javascript">
       var devices = document.getElementById("devices");
       var times = document.getElementById("times");

       var devicesValue = document.getElementById("devicesValue");
       var timesValue = document.getElementById("timesValue");
       var cost = document.getElementById("cost");
        var price = {!! $setting->remote_fee !!}
        devicesValue.innerHTML = devices.value; 
        timesValue.innerHTML = times.value + ' ماه';
        cost.innerHTML = (parseInt(devices.value)*price * parseInt(times.value )) + ' تومان' ;

        devices.oninput = function() {
             devicesValue.innerHTML = devices.value; 
             cost.innerHTML = (parseInt(devices.value)*price * parseInt(times.value )) + ' تومان' ;
        }

        times.oninput = function() {
            timesValue.innerHTML = times.value + ' ماه';
            cost.innerHTML = (parseInt(devices.value)*price * parseInt(times.value )) + ' تومان' ;
        }
   </script>
@endsection