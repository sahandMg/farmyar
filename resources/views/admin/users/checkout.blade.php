@extends('admin.master.header')
@section('content')

    <?php

    $setting = \App\Setting::first();

    ?>
    <div id="checkout">


                @if(count($users) > 0)

                    <table class="table table-striped">

                        <thead>
                        <tr>
                            <td>Id</td>
                            <td> Name </td>
                            <td> Email </td>
                            <td>Pending Amount(BTC)</td>
                            <td>Status</td>
                            <td>Checkout</td>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($users as $key => $user)
                            <tr>
                                <td>{{$user->code}}</td>
                                <td>{{$user->name}}</td>
                                <td>{{$user->email}}</td>
                                <td id="pendingBtc_{{$user->code}}">{{App\User::userPending($user)}}</td>
                                @if($user->block == 1)
                                    <td> <button id={{$user->code}} @click="block" class="btn btn-danger"> Blocked </button> </td>
                                @else
                                    <td> <button id={{$user->code}} @click="block" class="btn btn-success"> Active </button> </td>
                                @endif
                                @if(\App\User::userPending($user) >= $setting->minimum_redeem)
                                    <td>

                                        <button id="modal_{{$user->code}}" class="btn btn-info" data-toggle="modal" data-target="#myModal_{{$user->code}}"> Pay </button>
                                    {{-- Modal For $user --}}
                                        <div class="modal fade" id="myModal_{{$user->code}}" role="dialog">
                                            <div class="modal-dialog">

                                                <!-- Modal content-->
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button style="margin-left:0" type="button" class="close" data-dismiss="modal">&times;</button>
                                                        <h4 class="modal-title">تسویه</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p style="text-align: right;direction: rtl">جهت پرداخت QR زیر را اسکن کنید</p>
                                                        <div>
                                                            @if(is_null($user->wallet))
                                                                <p style="text-align: right">کابر کیف پولی ثبت نکرده است</p>
                                                            @else
                                                                <div class="row justify-content-center">
                                                                    <div class="col-4">{!! QrCode::size(150)->generate($user->wallet->addr) !!}
                                                                    </div>
                                                                    <p style="text-align: left">{{$user->wallet->addr}}</p>
                                                                </div>

                                                                <div class="row justify-content-center">
                                                                    <div class="col-4">
                                                                        <button id={{$user->code}} onclick="pay()" class="btn btn-danger"> بعد از پرداخت، کلیک کنید </button>
                                                                        <button id={{$user->code.'-loading'}} hidden class="buttonload btn btn-warning"><i class="fa fa-refresh fa-spin"></i> Loading</button>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        {{-- End Of Modal--}}

                                    </td>
                                @else
                                    <td> <button disabled id={{$user->code}} onclick="pay()" class="btn btn-success"> Pay </button> </td>
                                @endif

                            </tr>

                            {{--<tr v-for="(user,key) in users">--}}
                                {{--<td>@{{user.code}}</td>--}}
                                {{--<td>@{{user.name}}</td>--}}
                                {{--<td>@{{user.email}}</td>--}}
                                {{--<td :id='key'>{{user.pending}}</td>--}}
                                {{--<td v-if="user.block == 1"> <button :id='user.code' @click="block" class="btn btn-danger"> Blocked </button> </td>--}}
                                {{--<td v-else=""> <button :id='user.code' @click="block" class="btn btn-success"> Active </button> </td>--}}
                                {{--<td v-if="user.pending >= 0.01"> <button :id='user.code' @click="pay(user.code,key)" class="btn btn-success"> Pay </button> </td>--}}
                                {{--<td v-else=""> <button disabled :id='user.code' @click="pay" class="btn btn-success"> Pay </button> </td>--}}
                            {{--</tr>--}}
                        @endforeach
                        </tbody>

                    </table>



                @else

                <h1> No User!</h1>
        @endif
    </div>
    <script>

        function pay(e) {
            var event = e || window.event;
                var code = event.target.id;
            console.log(event.target);
            event.target.hidden = true;
            document.getElementById(code+'-loading').hidden = false;
            axios.post('{{route('redeem',['locale'=>session('locale')])}}',{'code':code}).then(function (response) {

                var resp = response.data;
                if(resp['type'] != 'error'){

                    event.target.hidden = false;
                    document.getElementById(code+'-loading').hidden = true;
                    event.target.disabled = true;
                    document.getElementById('pendingBtc_'+code).innerHTML = 0;
                    document.getElementById('modal_'+code).disabled = true;

                    alert(resp['body'])

                }else{
                    alert(resp['body'])
                }

            })
        }

        new Vue({
            el:'#checkout',
            data:{

                users:[],

            },
            created:function () {
               this.users = {!! DB::connection('mysql')->table('users')->get() !!}

            },
            methods:{
                pay:function (code,key) {


                    axios.post('{{route('redeem',['locale'=>session('locale')])}}',{'code':code}).then(function (response) {

                        var resp = response.data;
                        if(resp['type'] != 'error'){

                            document.getElementById(code).disable = true;
                            document.getElementById(key).innerHTML = 0;
                            alert(resp['body'])

                        }else{
                            alert(resp['body'])
                        }

                    })
                },
                block:function (e) {
                    var code = String(e.target.id)
                    console.log(this.$refs.code);
                    axios.get('{{route('blockUser',['locale'=>session('locale')])}}'+'?code='+e.target.id).then(function (response) {

                        if(e.target.className == 'btn btn-danger'){

                            e.target.innerHTML = 'Active';
                            e.target.className = 'btn btn-success';

                        }else{

                            e.target.innerHTML = 'Blocked';
                            e.target.className = 'btn btn-danger';
                        }

//
                    })
                }
            },

        })
    </script>
@endsection
