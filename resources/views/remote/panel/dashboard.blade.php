@extends('remote.layout')
@section('content')


    <div class="row m-t-25">
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c1">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">

                            <img src="{{URL::asset('remoteDashboard/images/hashbazaar-miners.svg')}}">
                        </div>
                        <div class="text">

                            <h2 class="englishFont">{{$active_devices}}</h2>

                            <span>تعداد دستگاه ها</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart1"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c2">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            {{--<i class="zmdi zmdi-receipt"></i>--}}

                            <img src="{{URL::asset('remoteDashboard/images/hashbazaar-hashrate.svg')}}">
                        </div>
                        <div class="text">
                            <h2 class="englishFont">{{$total_th}} TH</h2>
                            <span>کل نرخ هش</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart2"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c4">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            <!-- <i class="zmdi zmdi-money"></i> -->
                            <img src="{{URL::asset('remoteDashboard/images/hashbazaar-mine.svg')}}">
                        </div>
                        <div class="text">
                            <h2 class="englishFont">{{$active_devices}}</h2>
                            <span>دستگاه های فعال</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart4"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c3">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            <!-- <i class="fa fa-address-book"></i> -->
                            <img src="{{URL::asset('remoteDashboard/images/bitcoin.svg')}}">
                        </div>
                        <div class="text">
                            <h2  class="englishFont" id="btcPrice" style="direction: rtl;"></h2>
                            <span>قیمت بیت کوین</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart3"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

   <div id="pools">
    
   </div>

   <br/>
   <div class="au-card text-right" style="direction: rtl;">
     <form method="post" action="{{route('RegisterFarm',['locale'=> App::getLocale()])}}">
         <input type="hidden" name="_token" value="{{csrf_token()}}">
      <div class="d-flex flex-wrap justify-content-center new-farm">
        <label style="margin-top: 5px;">نام فارم جدید:</label>
        <input type="text" name="name" class="form-control" style="margin-right: 1%;">
        <button class="btn btn-success" style="margin-right: 2%;">ایجاد</button>
       </div>
      </form> 
      <br/>
       @if(count($farms) > 0)
           <h3 class="text-center">فارم های ماینینگ شما</h3>
           <br/>
          <table class="table table-bordered">
            <thead>
              <tr>
               <th>نام</th>
               <th>شناسه</th>
             </tr>
            </thead>
            <tbody>
            @foreach($farms as $farm)
             <tr>
               <td>{{$farm->name}}</td>
               <td class="englishFont">{{$farm->code}}</td>
            </tr>
            @endforeach
           </tbody>
          </table>
         @endif
   </div>
   <br/>
   <div class="au-card text-right" style="direction: rtl;">
     <h3>با دادن اطلاعات Pool ، API خود می توانید وضعیت استخراج را در این جا مشاهد کنید و همچنین در صورت قطع شدن ماینرها، ایمیل و پیامک هشدار دریافت کنید.</h3>
     <br/>
     <div class="form-group text-center col-lg-3 col-md-4 col-sm-10 mx-auto">
       <label for="sel1">انتخاب POOL</label>
       <select class="form-control" id="selectPool">
         <option class="englishFont" value="F2Pool"><span class="englishFont">F2Pool</span></option>
         <option class="englishFont" value="Antpool">Antpool</option>
         <option class="englishFont" value="SlushPool">SlushPool</option>
       </select>
     </div>
     <hr/>
       @include('formError')
       @include('formMessage')
     <div id="Antpool">
     <p>از قسمت dashboard، تب API را انتخاب کنید، بر روی Generate key کلیک کنید. از جدول پایین key و secret را در زیر وارد کنید، سپس بر روی آیکون تیک در قسمت options کلیک کنید.</p>
     <p>اگر نیاز به راهنمایی بیشتر دارید به قسمت آموزش ها بروید.</p>
        <form class="poolForm col-lg-4 col-md-5 col-sm-11 mx-auto" method="post" action="{{route('PoolRegister',['locale'=>App::getLocale()])}}">
            <input type="hidden" name="_token" value="{{csrf_token()}}">
            <input type="hidden" name="pool" value="antpool">
          <!-- <div class="form-group">
           <label>نام کاربری :</label>
           <input type="text" name="user_id" required class="form-control">
          </div> -->

          <div class="form-group">
           <label>Key :</label>
           <input type="text" name="api_key" required class="form-control">
          </div>
          <div class="form-group">
           <label>Secret :</label>
           <input type="text" name="secret" required class="form-control">
          </div>
          <div class="text-center"> 
            <button class="btn btn-success">ثبت</button>
          </div>
        </form>
     </div>
     <div id="F2Pool">
        <form class="poolForm col-lg-4 col-md-5 col-sm-11 mx-auto" method="post" action="{{route('PoolRegister',['locale'=>App::getLocale()])}}">
            <input type="hidden" name="_token" value="{{csrf_token()}}">
            <input type="hidden" name="pool" value="f2pool">
          <div class="form-group">
           <label>نام کاربری در سایت <span class="englishFont">F2pool</span> را وارد کنید.</label>
           <input type="text" name="username" required class="form-control">
          </div>
          <div class="text-center"> 
            <button class="btn btn-success">ثبت</button>
          </div>
        </form>
     </div>
     <div id="SlushPool">
        <form class="poolForm col-lg-4 col-md-5 col-sm-11 mx-auto" method="post" action="{{route('PoolRegister',['locale'=>App::getLocale()])}}">
                <input type="hidden" name="_token" value="{{csrf_token()}}">
                <input type="hidden" name="pool" value="slushpool">
                <div class="form-group">
                    <label>Token:</label>
                    <input type="text" name="token" required class="form-control">
                </div>
                <div class="text-center">
                    <button class="btn btn-success">ثبت</button>
                </div>
            </form>
        <br/>
        
    </div>
   </div> 
    <br/>
   <br/><br/>
    <style type="text/css">
        .poolForm {

        }
        .new-farm label{width: 100px;}
        .new-farm input{width: 200px;}
        @media (max-width: 420px) {
            .new-farm label{font-size: 18px;}
            h3 {    font-size: 19px;}
        }
    </style>

    @include('remote/scripts')
    <script type="text/javascript">
        function hideAllForms() {
            $('#Antpool').hide();$('#F2Pool').hide();$('#SlushPool').hide();
        }

        var selectPool = document.getElementById("selectPool");
// activities.addEventListener("click", function() {
//     var options = activities.querySelectorAll("option");
//     var count = options.length;
//     if(typeof(count) === "undefined" || count < 2)
//     {
//         addActivityItem();
//     }
// });
hideAllForms();
$('#F2Pool').show();
$('#btcPrice').html('<img src="{{URL::asset('img/ajax-loader.gif')}}">');
selectPool.addEventListener("change", function() {
  // console.log("")
  hideAllForms();
  $('#'+selectPool.value).show();
});
       axios.get('{{route('btcPrice')}}').then(function (response) {

            console.log(response.data['message']);
            if(response.data['code'] == 200){

                $('#btcPrice').html('$'+ response.data['message'].toFixed(0));
            }

        });


        axios.post('{{route('getPoolData',['locale'=>App::getLocale()])}}').then(function (response) {
            console.log("axios message")
            
            if(response.data['code'] == 200){
              console.log(response.data['message']);
               var data = response.data['message'];
               for(var i=0; i<data.length; i++) {
                  $('#pools').append(`<h2 class="text-center englishFont">`+data[i].type.toUpperCase()+`</h2>
    <div class="row m-t-25">
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c5">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            <!-- <i class="zmdi zmdi-select-all"></i> -->
                            <img src="{{URL::asset('remoteDashboard/images/24-payment.svg')}}">
                        </div>
                        <div class="text">
                            <h2 class="englishFont">`+parseFloat(data[i].value_last_day).toFixed(6)+`</h2>
                            <span> ساعت 24</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart1"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c5">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            <!-- <i class="zmdi zmdi-receipt"></i> -->
                            <img src="{{URL::asset('remoteDashboard/images/hashbazaar-unpaid.svg')}}">
                        </div>
                        <div class="text">
                            <h2 class="englishFont">`+parseFloat(data[i].balance).toFixed(6)+`</h2>
                            <span>واریز نشده</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart2"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c5">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            <!-- <i class="zmdi zmdi-money"></i> -->
                            <img src="{{URL::asset('remoteDashboard/images/cashout.svg')}}">
                        </div>
                        <div class="text">
                            <h2 class="englishFont">`+parseFloat(data[i].paid).toFixed(6)+`</h2>
                            <span>پرداخت شده</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart4"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 dash-item">
            <div class="overview-item overview-item--c5">
                <div class="overview__inner">
                    <div class="overview-box clearfix">
                        <div class="icon">
                            <!-- <i class="fa fa-address-book"></i> -->
                            <img src="{{URL::asset('remoteDashboard/images/kollang.svg')}}">
                        </div>
                        <div class="text">
                            <h2 class="englishFont" id="btcPrice" style="direction: rtl;">`+parseFloat(data[i].value).toFixed(6)+`</h2>
                            <span>استخراج شده</span>
                        </div>
                    </div>
                    <br/>
                    <!-- <div class="overview-chart">
                        <canvas id="widgetChart3"></canvas>
                    </div> -->
                </div>
            </div>
        </div>
    </div>`);
               }
            }

        })
    </script>
@endsection