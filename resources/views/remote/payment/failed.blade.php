@extends('remote.layout')
@section('title')

@endsection

@section('content')
   <div class="au-card text-right" style="direction: rtl;">
       <div class="alert alert-danger">
         <p>پراخت شما با مشکل مواجه شده است.</p>
         <p>در صورتی که مبلغی از حساب شما کثر شده باشد، حداکثر تا ۴۸ ساعت بازگردانده می شود.</p>
         <p>شماره تراکنش: <strong>{{$code}}</strong></p>
         <p>لطفا دوباره فرآیند خرید را تکرار نمایید.</p>
       </div>
   </div>
   <br/><br/>
 @include('remote/scripts')
   <script type="text/javascript">
    
   </script>
@endsection