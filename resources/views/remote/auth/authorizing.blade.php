<!DOCTYPE html>
<html>
<head>
	<title>مدیریت</title>
	<link rel="stylesheet" href="{{URL::asset('bootstrap/css/bootstrap.min.css')}}">
@if(Config::get('app.locale') == 'fa')
    <STYLE>
      @font-face {
        font-family: BYekanFont;
        src: url({{asset('fonts/BYekan.ttf')}});
      }
      * {
        font-family: BYekanFont;
      }
      h1, h2, h3, h4, h5, h6, div {
        font-family: BYekanFont;
      }
      th, a, p, input, button, legend, label {font-family: BYekanFont;}
      .btn {font-family: BYekanFont;}
    </STYLE>
 @endif

	         <script  src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js" ></script>
	             <script src="https://unpkg.com/axios/dist/axios.min.js"></script>


</head>
<body>
  <div class="container" style="direction: rtl;">
  	<div class="myCard">
		<div class="d-flex justify-content-center" style="margin-bottom: 5%;">
			<button class="btnTab" id="signupBtn">ثبت نام</button>
			<button class="btnTab" id="loginBtn">ورود</button>
		</div>
		<div id="signupForm">
			<form method="post" action="{{route('authorizing',['locale'=>App::getLocale()])}}">
			   @foreach($errors->all() as $error)
			      <div class="alert alert-danger">{{$error}}</div>
			   @endforeach
				   <input type="hidden" name="action" value="signup">
				   <input type="hidden" name="_token" value="{{csrf_token()}}">

			   <div class="form-group">
						 	<lable for="name">نام و نام خانوادگی</lable>
					      <input class="form-control" pattern='[a-zA-Z0-9 آ ا ب پ ت ث ج چ ح خ د ذ ر ز ژ س ش ص ض ط ظ ع غ ف ق ک گ ل م ن و ه ی]+'  type="text" name="name"  value="{{Request::old('name')}}">
			   </div>
			   <div class="form-group">
					<label for="email">ایمیل:</label>
					<input type="email" class="form-control"  name="email" id="email">
				</div>
				<div class="form-group">
					<label for="pwd">رمز:</label>
					<input type="password"  name="password" placeholder="{{__("Password")}}" class="form-control" id="pwd">
				</div>
				<div class="form-group">
					<label for="pwd2">تکرار رمز: </label>
					<input type="password" class="form-control" id="pwd2"  name="confirm_password" placeholder="{{__("Confirm password")}}">
				</div>
				<!-- captcha -->
				<div id="signupCaptcha">
				  <div class="d-flex justify-content-center" style="margin-bottom: 5%;">
				    <div class="wrap-input100 validate-input pass m-b-5"  style="margin-left: 5%;" data-validate = "Please enter password">
						<input required class="form-control" type="text" pattern="[a-zA-Z0-9]+"  name="captcha" placeholder="{{__("Security Code")}}">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
						<a onclick="refreshCaptcha(event)" style="cursor: pointer;">{{Captcha::img()}}</a>
					</div>
				  </div>
				</div>
				 <!-- ************** -->
				 <div class="text-center">
			    	<button type="submit" class="btn btn-success">ثبت نام</button>
				</div>
				<br/>
				<div class="text-center">
				  <a href="{{route('redirectToProvider',['locale'=>App::getLocale()])}}" class="socialnet-flex1" id="gp">
			       <img src="{{URL::asset('img/icons/googleicon.svg')}}" alt="Google Login" style="width: 40px;height: auto;"></a>
			    </div>
			</form>
		</div>
		<div id="loginForm">
			<form  method="post" action="{{route('authorizing',['locale'=>App::getLocale()])}}">
			    @foreach($errors->all() as $error)
			      <div class="alert alert-success">{{$error}}</div>
			   @endforeach
			       @if(session()->has('error'))
						<p style="color: red;text-align: right">{{session('error')}}</p>
					@endif
					@if(session()->has('message'))
						<p style="color: green;text-align: right">{{session('message')}}</p>
					@endif
			        <input type="hidden" name="_token" value="{{csrf_token()}}">
			        <input type="hidden" name="action" value="login">
				<div class="form-group">
					<label for="email">ایمیل:</label>
					<input type="email" name="email" class="form-control" id="email" value="{{Request::old('email')}}">
				</div>
				<div class="form-group">
					<label for="pwd">رمز:</label>
					<input type="password"  name="password" placeholder="{{__("Password")}}" class="form-control" id="pwd">
				</div>
				<!-- captcha -->
				<div id="loginCaptcha">
				  <!-- <div class="d-flex justify-content-center" style="margin-bottom: 5%;">
				    <div class="wrap-input100 validate-input pass m-b-5"  style="margin-left: 5%;" data-validate = "Please enter password">
						<input required class="form-control" type="text" pattern="[a-zA-Z0-9]+"  name="captcha" placeholder="{{__("Security Code")}}">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input pass m-b-10" data-validate = "Please enter password">
						<a onclick="refreshCaptcha(event)" style="cursor: pointer;">{{Captcha::img()}}</a>
					</div>
				  </div> -->
				</div>
                <div class="text-center">
				   <button type="submit" class="btn btn-success">ورود</button>
				</div>
				<a href="{{route('passwordReset',['locale'=>App::getLocale()])}}" class="txt2">
							{{__("Forgot Password?")}}
						</a>
				<br/>
				<div class="text-center">
				  <a href="{{route('redirectToProvider',['locale'=>App::getLocale()])}}" class="socialnet-flex1" id="gp">
			       <img src="{{URL::asset('img/icons/googleicon.svg')}}" alt="Google Login" style="width: 40px;height: auto;"></a>
			    </div>
			</form>
		</div>
	</div>
  </div>
  <style>
      body {
         background-color: white;  background-image: url({{asset('img/mining.jpg')}});
         background-repeat: no-repeat;
         background-position: center;
         background-size: cover; 
      }
	  .btnTab {
	      border: 0px;
		  width: 50%;
		  text-align: center;
		  background-color: white; cursor: pointer;
		  font-weight: 700;
		  font-size: 1.5rem;
		  padding-bottom: 5px;
	  }
	  .myCard {
	  	margin-top: 10%;
	  	flex: 0 1 calc(25% - 1em);
        text-align: right;
        margin-bottom: 1%;
        background-color: white;
        color: black;
        padding: 2% 4%;
	  }
	  .borderBottom { border-bottom: 2px solid;}
	  button:focus {outline:0;}
  </style>
 <!--  <div>
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">

					</ul>
				<input type="hidden" name="_token" value="{{csrf_token()}}">
					<span class="login100-form-title">
						ثبت نام مانیتورینگ
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

							<a href="{{route('redirectToProvider',['locale'=>App::getLocale()])}}" class="socialnet-flex1" id="gp">
								<img src="{{URL::asset('img/icons/googleicon.svg')}}" alt="Google Login"></a>


						{{-- alt="Join With Google Account" --}}

					</div>

					<div class="flex-col-c p-t-0 p-b-40">
						<span class="txt1 p-b-9">
                                {{__("do you have an account?")}}
						</span>



					</div>
				</form>
			</div>
		</div>
	</div>
   </div> -->
	<script src="{{URL::asset('js/jquery-3.3.1.js')}}"></script>
<!--===============================================================================================-->
	<!-- <script src="vendor/jquery/jquery-3.2.1.min.js"></script> -->
  <!-- <script src="{{URL::asset('js/jquery-3.3.1.js')}}"></script> -->
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
			axios.get('{{route('refreshCaptcha',['locale'=>App::getLocale()])}}').then(function(response){
				element.target.src = response.data

			});
		}

		$(document).ready(function() {
		        document.getElementById('loginCaptcha').innerHTML  = document.getElementById('signupCaptcha').innerHTML ;
                $('#signupForm').show();
				$('#loginForm').hide();
				$('#signupBtn').addClass('borderBottom');
				$('#loginBtn').removeClass('borderBottom');
			$('#signupBtn').click(function () {
                console.log("sign up btn");
				$('#signupForm').show();
				$('#loginForm').hide();
				$('#signupBtn').addClass('borderBottom');
				$('#loginBtn').removeClass('borderBottom');
			});
			$('#loginBtn').click(function () {
				console.log("log in btn");
				$('#signupForm').hide();
				$('#loginForm').show();
				$('#loginBtn').addClass('borderBottom');
				$('#signupBtn').removeClass('borderBottom');
			});
		});
	</script>
  </body>
</html>
