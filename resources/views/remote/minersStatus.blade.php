@extends('remote.layout')
@section('content')

    @if(is_null($minerData))
      <div class="container">
        <h1> No Data Available</h1>
      </div>
    @else
        <!-- <br/> -->
<div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <h2>{{\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($minerData->created_at))}}</h2>
                                <h2 class="title-1 m-b-25 text-right" style="direction: rtl;">وضعیت دستگاه های ماینر</h2>
                                <div class="table-responsive table--no-card m-b-40">
                                    <table class="table table-borderless table-striped table-earning">
                                        <thead>
                                            <tr  class="text-center">
                                                <th>Ip</th>
                                                <th>Type</th>
                                                <th>Temp2</th>
                                                <th>Temp1</th>
                                                <th>Fan Speed</th>
                                                <th>Total Th</th>
                                                <th>Up time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                             @for($i=0;$i< count(unserialize($minerData->data));$i++)
             <tr class="text-center">
                <td>{{unserialize($minerData->data)[$i]['ip']}}</td>
                <td>{{unserialize($minerData->data)[$i]['minerName']}}</td>
                <td>{{implode( ", ", unserialize($minerData->data)[$i]['temp2'])}}</td>
                <td>{{implode( ", ", unserialize($minerData->data)[$i]['temp1'])}}</td>
                <td>{{implode( ", ", unserialize($minerData->data)[$i]['fanSpeeds'])}}</td>
                <td>{{unserialize($minerData->data)[$i]['totalHashrate']}}</td>
                <td>{{unserialize($minerData->data)[$i]['upTime']}}</td>
            </tr>
        @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- <div class="col-lg-3">
                                <h2 class="title-1 m-b-25">Top countries</h2>
                                <div class="au-card au-card--bg-blue au-card-top-countries m-b-40">
                                    <div class="au-card-inner">
                                        <div class="table-responsive">
                                            <table class="table table-top-countries">
                                                <tbody>
                                                    <tr>
                                                        <td>United States</td>
                                                        <td class="text-right">$119,366.96</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Australia</td>
                                                        <td class="text-right">$70,261.65</td>
                                                    </tr>
                                                    <tr>
                                                        <td>United Kingdom</td>
                                                        <td class="text-right">$46,399.22</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Turkey</td>
                                                        <td class="text-right">$35,364.90</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Germany</td>
                                                        <td class="text-right">$20,366.96</td>
                                                    </tr>
                                                    <tr>
                                                        <td>France</td>
                                                        <td class="text-right">$10,366.96</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Australia</td>
                                                        <td class="text-right">$5,366.96</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Italy</td>
                                                        <td class="text-right">$1639.32</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                        </div>        
    @endif
   <br/>
   <div class="table-responsive table--no-card m-b-40" style="direction: rtl;">
       <h2 class="title-1 m-b-25 text-right" style="direction: rtl;">لیست خرید اشتراک ها</h2>
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
                <tr class="text-center">
                  <td>1398/5/30</td>
                  <td>25</td>
                  <td>3 ماه</td>
                  <td>150 هزار تومان</td>
                  <td>یک ماه 12 روز</td>
                </tr>
            </tbody>
        </table>
  </div>
   <br/>
   <div class="au-card text-right" style="direction: rtl;">
       <h2 class="title-1 m-b-25 text-right">مدت زمان و تعداد دستگاه های خود را انتخاب کنید.</h2>
       <form action="/action_page.php" class="was-validated" >
          <label for="customRange">تعداد دستگاه ها : <span id="customRange">12</span></label>
          <input type="range" class="custom-range" id="customRange" name="devices">
          <label for="customRange2">تعداد ماه ها : <span id="customRange">10 ماه</span></label>
          <input type="range" class="custom-range" id="customRange2" name="times">
          <br/><br/><br/>
          <div class="text-center">
              <button class="btn btn-success">خرید</button>
          </div>
       </form>
   </div>
   <script type="text/javascript">
       
   </script>
@endsection