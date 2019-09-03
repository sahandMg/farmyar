@extends('remote.layout')
@section('title')

@endsection
@section('content')
@if(is_null($minerData))
      <div class="container">
        <h1> No Data Available</h1>
      </div>
    @else

<div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <h2 style="font-size: 25px;">{{\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($minerData->created_at))}}</h2>
                                <h2 class="title-1 m-b-25 text-right" style="direction: rtl;">وضعیت دستگاه های ماینر</h2>
                                <div class="table-responsive table--no-card m-b-40">
                                    <table class="table table-borderless table-striped table-earning">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Ip</th>
                                                <th>مدل</th>
                                                <th>دمای یک</th>
                                                <th>دمای دو</th>
                                                <th>سرعت فن ها</th>
                                                <th>میانگین تراهش 5 ساعت اخیر</th>
                                                <th>زمان کارکرد پیوسته</th>
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
                        <br/>
                        <br/>
                        <br/>     
    @endif

    @include('remote/scripts')

@endsection