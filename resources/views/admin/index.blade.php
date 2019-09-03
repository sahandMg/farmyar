@extends('admin.master.header')
@section('content')
<?php
        $setting = \App\Setting::first();
        $hashReport = \App\hashRate::orderBy('created_at','desc')->first();
?>
    <style>
        .col{
            border:1px solid gray;
            box-shadow: 5px 5px 15px gray ;
            margin: 10px;
            padding: 5px;
            text-align: center;
            font-family: "B Yekan", sans-serif;
            border-radius: 10px;
        }
        h3{
            font-size: 20px;
            font-family: "B Yekan", sans-serif;
        }
        p{
            color: black;
        }
    </style>
    <div class="container">
        <h2 style="text-align: center"><span>{{\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::now())}}</span></h2>
        <br>
        <div class="row justify-content-center">
            <div class="col"><h3>مجموع کل استخراج</h3>
                <hr>
                <p> <span>{{$setting->total_mining}}</span> BTC </p>
            </div>
            <div class="col"><h3>سود</h3>
                <hr>
                {{-- 0.01638 = second miner profite --}}
                <p>{{$setting->total_benefit}} BTC </p>
            </div>
            <div class="col"><h3>کل تراهش</h3>
                <hr>
                <p>{{$setting->total_th}} TH </p>
            </div>
            <div class="col"><h3>تعداد کاربران</h3>
                <hr>
                <p>{{\App\User::get()->count()}}</p>
            </div>
        </div>
        <br>
        <div class="row justify-content-center">
            <div class="col"><h3>سختی شبکه</h3>
                <hr>
                <p> <span>{{$hashReport->difficulty}}</span> Terra </p>
            </div>
            <div class="col"><h3>پاداش استخراج</h3>
                <hr>
                {{-- 0.01638 = second miner profite --}}
                <p>{{$hashReport->block_reward}} BTC </p>
            </div>

        </div>

        <br><br>

    </div>
        <h3 style="text-align: center">استخراج روزانه</h3>
        <div style="border: 1px solid black;width: 100%" id="chartdiv"></div>
        <br><br>
        <h3 style="text-align: center">سود روزانه</h3>
        <div style="border: 1px solid black;width: 100%" id="chartdiv2"></div>



            <script src="https://www.amcharts.com/lib/4/core.js"></script>
            <script src="https://www.amcharts.com/lib/4/charts.js"></script>
            <script src="https://www.amcharts.com/lib/4/themes/animated.js"></script>
        <script>


                  am4core.useTheme(am4themes_animated);

                  var chart = am4core.create("chartdiv", am4charts.XYChart);
                  chart.data = [];
                  axios.get('{!! (route('chartDataAdmin',['locale'=>session('locale')])) !!}').then(function (resp) {

                      chart.data = resp.data


                      var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
                      categoryAxis.dataFields.category = "time";
                      categoryAxis.renderer.grid.template.location = 0.5;
                      categoryAxis.renderer.minGridDistance = 30;

                      categoryAxis.renderer.labels.template.adapter.add("dy", function(dy, target) {
                          if (target.dataItem && target.dataItem.index & 2 == 2) {
                              return dy + 25;
                          }
                          return dy;
                      });

                      var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

                      // Create series
                      var series = chart.series.push(new am4charts.ColumnSeries());
                      series.dataFields.valueY = "mined";
                      series.dataFields.categoryX = "time";
                      series.name = "mined";
                      series.columns.template.tooltipText = "{categoryX}: [bold]{valueY}[/]";
                      series.columns.template.fillOpacity = .8;

                      var columnTemplate = series.columns.template;
                      columnTemplate.strokeWidth = 2;
                      columnTemplate.strokeOpacity = 1;
                      console.log(chart.data)
                  });


        </script>

        <script>


            am4core.useTheme(am4themes_animated);

            var chart2 = am4core.create("chartdiv2", am4charts.XYChart);
            chart2.data = [];
            axios.get('{!! (route('chartDataProfit',['locale'=>session('locale')])) !!}').then(function (resp) {

                chart2.data = resp.data


                var categoryAxis = chart2.xAxes.push(new am4charts.CategoryAxis());
                categoryAxis.dataFields.category = "time";
                categoryAxis.renderer.grid.template.location = 0.5;
                categoryAxis.renderer.minGridDistance = 30;

                categoryAxis.renderer.labels.template.adapter.add("dy", function(dy, target) {
                    if (target.dataItem && target.dataItem.index & 2 == 2) {
                        return dy + 25;
                    }
                    return dy;
                });

                var valueAxis = chart2.yAxes.push(new am4charts.ValueAxis());

                // Create series
                var series = chart2.series.push(new am4charts.ColumnSeries());
                series.dataFields.valueY = "benefit";
                series.dataFields.categoryX = "time";
                series.name = "benefit";
                series.columns.template.tooltipText = "{categoryX}: [bold]{valueY}[/]";
                series.columns.template.fillOpacity = .8;

                var columnTemplate = series.columns.template;
                columnTemplate.strokeWidth = 2;
                columnTemplate.strokeOpacity = 1;
                console.log(chart2.data)
            });


        </script>



@endsection