@extends('master.layout')
@section('content')
@if(App::getlocale() == 'fa')
<title>هش بازار | ثبت نام</title>
<style type="text/css">
	input {direction: rtl;}
	.wrap-input100 {text-align: right;}
</style>
@else
<style type="text/css">
	input {font-family: Ubuntu-Regular;}
	a {font-family: Ubuntu-Regular;}
</style> 
@endif
<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<form method="post" action="{{route('passwordReset',['locale'=>session('locale')])}}" class="login100-form validate-form p-l-55 p-r-55 p-t-178">
				    <ul>
						@foreach($errors->all() as $error)
							<li style="color: red;margin-bottom: 1%;">{{$error}}</li>
						@endforeach
						@include('sessionError')
					</ul>
                    <input type="hidden" name="_token" value="{{csrf_token()}}">
						<span class="login100-form-title">
						{{__("Reset Password")}}
					</span>

                    <div class="wrap-input100 validate-input m-b-26 login100" style="color: black">
                           <span>{{__("Please enter your email to send password")}}</span>
                    </div>

					<div class="wrap-input100 validate-input m-b-16" data-validate="Please enter username">
                        <input class="input100" name="email" type="email"  value="{{Request::old('email')}}" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="{{__("Email Address")}}">
						<span class="focus-input100"></span>
					</div>

					

					<div class="container-login100-form-btn p-t-5 p-b-36">
						<button class="login100-form-btn">
							{{__("Send")}}
						</button>
					</div>

					<div class="flex-col-c p-t-0 p-b-40">
						<span class="txt1 p-b-9">
							{{__("Don’t have an account?")}}
						</span>

						<a href="{{route('signup',['locale'=>session('locale')])}}" class="txt3">
							{{__("Sign up now")}}
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>

@include('master.footer')
@endsection