@extends('master.layout')
@section('title')
@if(App::getlocale() == 'fa')
<title>هش بازار | ورود</title>
<style type="text/css">
	input {direction: rtl;}
</style>
@else
<title>Hashbazaar | Login</title>
<style type="text/css">
	input {font-family: Ubuntu-Regular;}
	a {font-family: Ubuntu-Regular;}
</style>    
@endif
@endsection
@section('content')
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<form onsubmit="submitForm()" method="post" action="{{route('RemoteLogin',['locale'=>App::getLocale()])}}" class="login100-form validate-form p-l-55 p-r-55 p-t-178">
				    <ul>
						@foreach($errors->all() as $error)
							<li style="color: red;margin-bottom: 1%;">{{$error}}</li>
						@endforeach
					</ul>
					@if(session()->has('error'))
						<p style="color: red;text-align: right">{{session('error')}}</p>
					@endif
					@if(session()->has('message'))
						<p style="color: green;text-align: right">{{session('message')}}</p>
					@endif
					<br>
                    <input type="hidden" name="_token" value="{{csrf_token()}}">
                    <input type="hidden" name="hashPower" value="{{isset($_GET['hashPower'])?$_GET['hashPower']:null}}">
						<span class="login100-form-title">
						ورود مانیتورینگ
					</span>

					<div class="wrap-input100 validate-input m-b-16" data-validate="Please enter username">
                        <input class="input100" name="email" type="email"  value="{{Request::old('email')}}" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Email Address">
						<span class="focus-input100"></span>

					</div>

					<div class="wrap-input100 validate-input" data-validate = "Please enter password">
						<input class="input100" type="password" name="password" placeholder="Password">
						<span class="focus-input100"></span>
					</div>


					<div class="text-right p-t-13 p-b-23">
						<span class="txt1">

						</span>
						<div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
							<input class="input100" type="text" pattern="[a-zA-Z0-9]+" required name="captcha" placeholder="{{__("Security Code")}}">
							<span class="focus-input100"></span>
						</div>

						<div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
							<a onclick="refreshCaptcha(event)" style="cursor: pointer;">{{Captcha::img()}}</a>
						</div>

						<a href="{{route('passwordReset',['locale'=>App::getLocale()])}}" class="txt2">
							{{__("Forgot Password?")}}
						</a>
					</div>

					<div class="container-login100-form-btn">
						<button id="submitBtn" class="login100-form-btn">
							{{__("Login")}}
						</button>
					</div>


					<div class="container-socialnet1">

						{{-- <a href="https://facebook.com" class="socialnet-flex1" id="fb"><img src="img/icons/facebook.svg" alt=""></a>

						<a href="https://twitter.com" class="socialnet-flex1" id="twttr"><img src="img/icons/twitter.svg" alt=""></a> --}}

						{{-- <a href="https://plus.google.com/+googleplus" class="socialnet-flex1" id="gp"><img src="../../../public/img/googleicon.png" alt=""></a> --}}
						{{-- <a href="{{route('redirectToProvider')}}" class="socialnet-flex" id="gp"><img src="img/icons/googleicon.png" alt=""></a> --}}

						<a href="{{route('redirectToProvider',['locale'=>App::getLocale()])}}" class="socialnet-flex1" id="gp">
							<img src="{{URL::asset('img/icons/googleicon.png')}}" alt="Google login"></a>
						{{-- alt="Join With Google Account" --}}
					</div>

					<div class="flex-col-c p-t-0 p-b-40">
						<span class="txt1 p-b-9">
							{{__("Don’t have an account?")}}
						</span>

						<a href="{{route('signup',['locale'=>App::getLocale()])}}" class="txt3">
							{{__("Sign up now")}}
						</a>
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
