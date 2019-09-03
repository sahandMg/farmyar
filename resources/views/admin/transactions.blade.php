@extends('admin.master.header')
@section('content')
    <?php
            $users = \App\User::all();
    ?>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>OrderID</th>
            <th>TH/S</th>
            <th>User/ID</th>
            <th>Country</th>
            <th>Amount(BTC)</th>
            <th>Amount(Toman)</th>
            <th>Status</th>
            <th>Authority</th>
            <th>Type</th>
            <th>Record Created</th>

        </tr>
        </thead>
        <tbody>

        @foreach($transactions as $transaction)
            <tr>
                <td>{{$transaction->id}}</td>
                <td>{{$transaction->code}}</td>
                <?php
                    $query = DB::connection('mysql')->table('bit_hashes')->where('order_id',$transaction->code)->first();
                    if(!is_null($query)){
                        $query = $query->hash;
                    }else{
                        $query ='--';
                    }
                ?>
                <td> {{$query}} </td>

                <td> {{$users->find($transaction->user_id)->name}} / {{$transaction->user_id}} </td>
                <td><img width="25" height="20" src="{{URL::asset('flags/'.strtolower(substr($transaction->country,0,2)).'.svg')}}" alt="{{$transaction->country}}"></td>
                @if(is_null($transaction->amount_btc)) <td>--</td> @else <td>{{$transaction->amount_btc}}</td> @endif
                @if(is_null($transaction->amount_toman)) <td>--</td> @else <td>{{$transaction->amount_toman}}</td> @endif
                <td>{{$transaction->status}}</td>
                <td>{{$transaction->authority}}</td>
                <td>{{$transaction->checkout}}</td>
                <td>{{\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($transaction->created_at))}}</td>
            </tr>
        @endforeach
        {{--<tr v-for="(transaction, index) in transactions">--}}
        {{--<td>@{{transaction.paymentID}}</td>--}}
        {{--<td>@{{transaction.orderID}}</td>--}}
        {{--<td>@{{transaction.userID}}</td>--}}
        {{--<td><img width="25" height="20" :src="flag" alt="">@{{ readFlag(transaction.countryID) }}</td>--}}
        {{--<td>@{{transaction.amount.toFixed(6)}}</td>--}}
        {{--<td id="confirmed">@{{transaction.txConfirmed}}</td>--}}
        {{--<td id="unrecognised">@{{transaction.unrecognised}}</td>--}}
        {{--<td id="processed">@{{transaction.processed}}</td>--}}
        {{--<td>@{{transaction.recordCreated}}</td>--}}
        {{--</tr>--}}


        </tbody>
    </table>
    {{ $transactions->links() }}
    </div>
    {{ $transactions->links() }}
    <script>

        new Vue({
            el:'.app',
            data:{
                transactions:[],
                flag:''

            },

            created:function () {


            },
            methods:{

                readFlag: function (country) {

                    this.flag = '../flags/'+country.substr(0,2).toLowerCase()+'.svg'

                }
            },

        });

    </script>

@endsection
