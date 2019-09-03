@extends('admin.master.header')
@section('content')
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Transaction ID</th>
                <th>Name</th>
                <th>Amount</th>
                <th>wallet</th>
                <th>Time</th>

            </tr>
            </thead>
            <tbody>

            <tr v-for="(transaction, index) in transactions">
                <td>@{{transaction.id}}</td>
                <td>@{{transaction.code}}</td>
                <td>@{{transaction.name}}</td>
                <td>@{{parseFloat(transaction.amount_btc).toFixed(8)}}</td>
                <td>@{{transaction.addr}}</td>
                <td>@{{transaction.created_at}}</td>
            </tr>


            </tbody>
        </table>
    </div>
</div>


<script>

    new Vue({
        el:'.app',
        data:{
            transactions:[]
        },

        created:function () {

            this.getTrans()

        },
        methods:{

            getTrans:function () {

                vm = this;


                axios.get({!! json_encode(route('adminGetRedeems',['locale'=>session('locale')])) !!}).then(function (response) {

                    vm.transactions = response.data;

                });

                setTimeout(function () {

                    vm.getTrans()
                },10000);
//
//                for(i=0 ; i< vm.transactions.length; i++){
//
//                    document.getElementById('confirmed'+i).innerHTML = vm.transactions[i]['txConfirmed'];
//                    document.getElementById('unrecognised'+i).innerHTML = vm.transactions[i]['unrecognised'];
//                    document.getElementById('processed'+i).innerHTML = vm.transactions[i]['processed'];
//                }
            }
        }
    });

</script>

@endsection
