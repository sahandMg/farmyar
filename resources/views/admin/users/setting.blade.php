@extends('admin.master.header')
@section('content')

@include('formMessage')
 <form style="padding: 20px;" method="POST" action="{{route('userSetting',['locale'=>session('locale')])}}">
          <input class="form-control" type="hidden" name="_token" value="{{csrf_token()}}">

        <input class="form-control" type="hidden" name="id" value="{{$user->id}}">
    <div class="form-group">
         <label for="code">Code</label>
         <input readonly class="form-control" id="code" type="text" placeholder="{{$user->code}}">
    </div>
     <div class="form-group">
         <label for="name">Name</label>
         <input readonly class="form-control" id="name" type="text" placeholder="{{$user->name}}">
     </div>
     <div class="form-group">
         <label for="email">Email</label>
         <input readonly class="form-control" id="email" type="email" placeholder="{{$user->email}}">
     </div>
     <div class="form-group">
         <label for="password"> Password</label>
         <input class="form-control" name="password" id="password" type="password">
      </div>
     <div class="form-group">
         <label for="planid">Plan_id</label>
         <input class="form-control" id="planid" name="planid" type="number" min="0" placeholder="{{$user->plan_id}}">
    </div>
     <div class="form-group">
         <label for="wallet">Wallet</label>
         <input class="form-control" id="wallet" name="address" type="text"  placeholder="{{!isset($user->wallet->addr)?null:$user->wallet->addr}}">
     </div>
     <button type="submit" class="btn btn-primary">ویرایش </button>
       </form>


@endsection