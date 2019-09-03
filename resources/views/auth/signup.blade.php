@extends('master.layout')
@section('title')
@if(App::getlocale() == 'fa')
<title>هش بازار | ثبت نام</title>
<style type="text/css">
	input {direction: rtl;}
</style>
@else
<title>Hashbazaar | Signup</title>
<style type="text/css">
	input {font-family: Ubuntu-Regular;}
	a {font-family: Ubuntu-Regular;}
</style> 
@endif
@endsection
@section('content')
	<?php

			?>
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">

				<form method="post" action="{{route('signup',['locale'=>session('locale')])}}" class="login100-form validate-form p-l-55 p-r-55 p-t-148">

					<ul>
						@foreach($errors->all() as $error)
							<li style="color: red;margin-bottom: 1%;">{{$error}}</li>
						@endforeach
					</ul>
				<input type="hidden" name="_token" value="{{csrf_token()}}">
					{{--<input type="hidden" name="hashPower" value="{{isset($_GET['hashPower'])?$_GET['hashPower']:null}}">--}}
					<input type="hidden" name="plan" value="{{isset($_GET['plan'])?$_GET['plan']:null}}">
					<span class="login100-form-title">
						{{__("SignUp")}}
					</span>

					<div class="wrap-input100 validate-input m-b-10" data-validate="Please enter your name">
						<input class="input100" pattern='[a-zA-Z0-9 آ ا ب پ ت ث ج چ ح خ د ذ ر ز ژ س ش ص ض ط ظ ع غ ف ق ک گ ل م ن و ه ی]+'  type="text" name="name" placeholder="{{__("Username")}}" value="{{Request::old('name')}}">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input email m-b-10" data-validate = "Please enter email" >
						<input class="input100" type="email" name="email" placeholder="{{__("Email")}}" value="{{Request::old('email')}}">
						<span class="focus-input100"></span>
					</div>

                    <div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
                            <input class="input100" type="password" name="password" placeholder="{{__("Password")}}">
                            <span class="focus-input100"></span>
                    </div>

                    <div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
                            <input class="input100" type="password" name="confirm_password" placeholder="{{__("Confirm password")}}">
                            <span class="focus-input100"></span>
                    </div>

					<div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
						<input required class="input100" type="text" pattern="[a-zA-Z0-9]+"  name="captcha" placeholder="{{__("Security Code")}}">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
						<a onclick="refreshCaptcha(event)" style="cursor: pointer;">{{Captcha::img()}}</a>
					</div>

					<div class="container-login100-form-btn">
						<button id="submitBtn" class="login100-form-btn">
                            {{__("SignUp")}}
                        </button>
					</div>

					<div class="container-socialnet1">

						{{-- <a href="https://facebook.com" class="socialnet-flex1" id="fb">
							<img src="/public/img/facebook.svg" alt=""></a>

						<a href="https://twitter.com" class="socialnet-flex1" id="twttr">
							<img src="/public/img/twitter.svg" alt=""></a>
						 --}}
					@if(isset($_GET['plan']))
							<a href="{{route('redirectToProvider',['locale'=>session('locale')]).'?plan='.$_GET['plan']}}" class="socialnet-flex1" id="gp">
								<img src="{{URL::asset('img/icons/googleicon.png')}}" alt="Google Login"></a>
					@else
							<a href="{{route('redirectToProvider',['locale'=>session('locale')])}}" class="socialnet-flex1" id="gp">
								<img src="{{URL::asset('img/icons/googleicon.png')}}" alt="Google Login"></a>
					@endif

						{{-- alt="Join With Google Account" --}}

					</div>

					<div class="flex-col-c p-t-0 p-b-40">
						<span class="txt1 p-b-9">
                                {{__("do you have an account?")}}
						</span>
		@if(isset($_GET['hashPower']))
				<a href="{{route('login',['locale'=>session('locale')]).'?hashPower='.$_GET['hashPower']}}" class="txt3">{{__("Log in now")}}</a>
		@else
			<a href="{{route('login',['locale'=>session('locale')])}}" class="txt3">{{__("Log in now")}}</a>
		@endif


					</div>
				</form>
			</div>
		</div>
	</div>
	@include('master.footer')

<!--===============================================================================================-->
	<!-- <script src="vendor/jquery/jquery-3.2.1.min.js"></script> -->
<!--===============================================================================================-->
<script src="{{URL::asset('vendor/animsition/js/animsition.min.js')}}"></script>
	<!--===============================================================================================-->
	<script src="{{URL::asset('vendor/bootstrap/js/popper.js')}}"></script>
	<!-- <script src="vendor/bootstrap/js/bootstrap.min.js"></script> -->
	<!--===============================================================================================-->
	<script src="{{URL::asset('vendor/select2/select2.min.js')}}"></script>
	<!--===============================================================================================-->
	<script src="{{URL::asset('vendor/daterangepicker/moment.min.js')}}"></script>
	<script src="{{URL::asset('vendor/daterangepicker/daterangepicker.js')}}"></script>
	<!--===============================================================================================-->
	<script src="{{URL::asset('vendor/countdowntime/countdowntime.js')}}"></script>
	<!--===============================================================================================-->
	<script src="{{URL::asset('js/main.js')}}"></script>
	<script>
		function submitForm(){
			document.getElementById('submitBtn').disabled = true
		}
		function refreshCaptcha(e){
			var element = e;
			axios.get('captcha-refresh').then(function(response){
				element.target.src = response.data

			});
		}
	</script>

@endsection
