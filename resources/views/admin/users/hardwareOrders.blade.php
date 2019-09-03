@extends('admin.master.header')
@section('content')

    @if(count($orders) > 0)

        @include('sessionError')
        <table class="table table-striped">

            <thead>
            <tr>
                <td> name </td>
                <td> address </td>
                <td> phone </td>
                <td>post</td>
                <td>code</td>
                <td>user_id </td>
                <td>trans_id</td>
            </tr>
            </thead>

            <tbody>
            @foreach($orders as $key => $order)
                <tr>
                    <td>{{$order->name}}</td>
                    <td>{{$order->address}}</td>
                    <td>{{$order->phone}}</td>
                    <td>{{$order->post}}</td>
                    <td>{{$order->code}}</td>
                    <td>{{$order->user_id}}</td>
                    <td>{{$order->trans_id}}</td>

                </tr>

            @endforeach
            </tbody>

        </table>

    @else

        <h1> No User!</h1>
    @endif

    <script>
        new Vue({
            el:'.app',
            data:{

            },
        })
    </script>
@endsection