@extends('admin.master.header')
@section('content')

 @if(count($users) > 0)

     @include('sessionError')
            <table class="table table-striped">

                <thead>
                <tr>
                    <td>id</td>
                    <td> name </td>
                    <td> email </td>
                    <td> country </td>
                    <td>total BTC</td>
                    <td>register at </td>
                    <td>login at</td>
                    <td>status</td>
                    <td>Login</td>
                    <td>Collaborate</td>
                    <td>Setting</td>
                </tr>
                </thead>

                <tbody>
                @foreach($users as $key => $user)
                    <tr>
                        <td>{{$user->code}}</td>
                        <td>{{$user->name}}</td>
                        <td>{{$user->email}}</td>
                        @if(is_null($user->country))
                            <td><img width="25" height="20" src="{{URL::asset('flags/error.svg')}}" alt=""></td>
                        @else
                            <td><img width="25" height="20" src="{{URL::asset('flags/'.$user->country.'.svg')}}" alt=""></td>
                        @endif

                            <td>{{$user->minings->sum('mined_btc')}}</td>

                        @if(\Carbon\Carbon::now()->diffInDays($user->created_at) > 720)
                            <td>{{\Carbon\Carbon::now()->diffInMonths($user->created_at)}} Months ago</td>

                        @elseif(\Carbon\Carbon::now()->diffInDays($user->created_at) > 1)
                            <td>{{\Carbon\Carbon::now()->diffInDays($user->created_at)}} Days ago</td>

                        @elseif(\Carbon\Carbon::now()->diffInMinutes($user->created_at) > 60)
                            <td>{{\Carbon\Carbon::now()->diffInHours($user->created_at)}} Hours ago</td>



                        @else
                            <td>{{\Carbon\Carbon::now()->diffInMinutes($user->created_at)}} Minutes ago</td>


                        @endif

                        @if(\Carbon\Carbon::now()->diffInDays($user->updated_at) > 720)
                            <td>{{\Carbon\Carbon::now()->diffInMonths($user->updated_at)}} Months ago</td>

                        @elseif(\Carbon\Carbon::now()->diffInDays($user->updated_at) > 1)
                            <td>{{\Carbon\Carbon::now()->diffInDays($user->updated_at)}} Days ago</td>

                        @elseif(\Carbon\Carbon::now()->diffInMinutes($user->updated_at) > 60)
                            <td>{{\Carbon\Carbon::now()->diffInHours($user->updated_at)}} Hours ago</td>



                        @else
                            <td>{{\Carbon\Carbon::now()->diffInMinutes($user->updated_at)}} Minutes ago</td>


                        @endif
                        @if($user->block == 1)
                            <td> <button id={{$user->code}} @click="block" class="btn btn-danger"> Blocked </button> </td>
                        @else
                            <td> <button id={{$user->code}} @click="block" class="btn btn-success"> Active </button> </td>
                        @endif
                        <td>
                            <form action="{{route('LoginAsUser',['locale'=>session('locale')])}}" method="POST">
                                <input type="hidden" name="_token" value="{{csrf_token()}}">
                                <input type="hidden" name="email" value="{{$user->email}}">
                                <button class="btn btn-info" type="submit">Login</button>
                            </form>
                        </td>
                        <td><a class="btn btn-primary" href="{{route('collaboration',['locale'=>session('locale'),'id'=>$user->id])}}">Collab</a></td>

                        <td><a class="btn btn-info" href="{{route('userSetting',['locale'=>session('locale'),'id'=>$user->id])}}">Setting</a></td>
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
        methods:{

            block:function (e) {


                axios.get('{{route('blockUser',['locale'=>session('locale')])}}?code='+e.target.id).then(function (response) {

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
        }
    })
</script>
@endsection