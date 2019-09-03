@extends('remote.layout')
@section('title')

@endsection

@section('content')
   <div class="au-card text-right" style="direction: rtl;">
       <div class="alert alert-success">
         <p>پرداخت شما با موفقیعت انجام شد.</p>
         <p>شماره فاکتور: <strong>{{$code}}</strong></p>
       </div>
   </div>
   <br/><br/>
 @include('remote/scripts')
   <script type="text/javascript">
    
   </script>
@endsection