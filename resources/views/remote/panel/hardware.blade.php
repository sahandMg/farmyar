@extends('remote.layout')
@section('title')

@endsection

@section('content')
   <div class="au-card text-right" style="direction: rtl;">
       <h3 class="m-b-25 text-center">قسمت تهیه سخت افزار</h3>
       <p>اگر در فارم ماینینگ خود کامپیوتری دارید که می توانید 24 ساعته روشن بگذارید، نیازی به تهیه سخت افزار جدا ندارید، می توانید به راحتی نرم افزار را بر روی کامپیوتر خود نصب کنید.برای آموزش نصب نرم افزار به قسمت آموزش ها بروید.</p>
       <img src="{{URL::asset('remoteDashboard/images/orangepizero.png')}}">
       <p>پیشنهاد ما این است، برای کاهش مصرف برق و پایدار بودن سیستم مدیریت ماینیگ سخت افزار بالا را تهیه کنید.</p>
       <p>تمام نرم افزار های مورد نیاز بر روی این سخت افزار نصب شده است، فقط کافی است کابل شبکه و برق را متصل کنید.</p>
       <p>هزینه سخت افزار : 600 هزار تومان است.</p>
       <p>زمان دریافت برای مشتریان تهران 3 روز کاری و برای شهرستان ها 4 روز کاری است.</p>
       <br/>
       <h4 class="text-center">برای ثبت سفارش فرم زیر را پر کنید.</h4>
       <form action="{{route('RemoteOrderZarrinPalPaying',['locale'=>App::getLocale()])}}" method="post" class="was-validated" >
           @include('formError')
           <input type="hidden" name="_token" value="{{csrf_token()}}">
          <div class="form-group">
           <label>نام :</label>
           <input type="text" name="name" required class="form-control">
          </div>
          <div class="form-group">
           <label>شماره تلفن :</label>
           <input type="text" name="phone" required class="form-control">
          </div>
          <div class="form-group">
           <label>آدرس :</label>
           <input type="text" name="address" required class="form-control">
          </div>
          <div class="form-group">
           <label>کد پستی :</label>
           <input type="text" name="post" required class="form-control">
          </div>
          <div class="text-center"> 
            <button class="btn btn-success">خرید</button>
          </div>
       </form>
   </div>
   <br/><br/>
   <!-- <div class="au-card text-right" style="direction: rtl;">
     <h3 class="m-b-25 text-center">پیگیری سفارش ها</h3>
     <p class="text-center m-t-2 m-b-2">سفارشی تا حالا ثبت نکردید.</p>
     <div class="table-responsive">
     <table class="table table-bordered">
        <thead>
          <tr>
           <th>تاریخ</th>
           <th>مبلغ</th>
           <th>وضعیت</th>
           <th>کد پیگیری</th>
         </tr>
        </thead>
        <tbody>
         <tr>
           <td>1398/06/20</td>
           <td>600000 تومان</td>
           <td>در حال آماده سازی</td>
           <td>1235468</td>
        </tr>
       </tbody>
      </table>
      </div> 
   </div>
   <br/><br/> -->
 @include('remote/scripts')
   <script type="text/javascript">
    
   </script>
@endsection