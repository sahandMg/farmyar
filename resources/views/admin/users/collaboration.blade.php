@extends('admin.master.header')
@section('content')

 <?php
         $user = DB::connection('mysql')->table('users')->where('id',$id)->first();
 ?>

 @include('formMessage')

     <form style="padding: 20px;" method="POST" action="{{route('collaboration',['locale'=>session('locale')])}}">
              <input type="hidden" name="_token" value="{{csrf_token()}}">
           <div class="form-group">
             <label for="name">نام کاربری</label>
             <input name="name" readonly type="name" value="{{$user->name}}"  class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" >
           </div>

         <div class="form-group">
             <label for="name">ایمیل</label>
             <input name="email" readonly type="email" value="{{$user->email}}"  class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" >
         </div>

           <div class="form-group">
             <label for="exampleInputEmail1">مقدار بیت کوین ماین شده</label>
             <input name="mined_btc" type="text" class="form-control" value="" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="مثلا ۰.۰۲">
           </div>

           <div class="form-group">
             <label for="exampleInputPassword1">مقدار تراهش</label>
             <input name="th" type="text" class="form-control" id="exampleInputPassword1" placeholder="مثلا ۰.۶">
           </div>

           <div class="form-group">
             <label for="exampleInputPassword1">تعداد روزهای گذشته از قرار داد</label>
             <input name="remainedDay" type="text" class="form-control" id="exampleInputPassword1" placeholder="مثلا ۱۰">
           </div>

         <div class="form-group">
             <label for="exampleInputPassword1">مبلغ پرداختی به تومان</label>
             <input name="price" type="text" class="form-control" id="exampleInputPassword1" placeholder="مثلا ۱۰۰۰۰ تومان">
         </div>


         {{--<div class="form-group">--}}
             {{--<label for="exampleInputPassword1">پسورد ادمین</label>--}}
             {{--<input name="password" type="password" class="form-control" id="exampleInputPassword1" >--}}
         {{--</div>--}}

            <button type="submit" class="btn btn-primary">ثبت رکورد </button>

           </form>


@endsection
