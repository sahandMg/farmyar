@extends('master.layout')
@section('title')
    <title>Login</title>
@endsection
@section('content')
    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">

                <form method="post" action="{{route('AdminLogin',['locale'=>session('locale')])}}" class="login100-form validate-form p-l-55 p-r-55 p-t-178">

                    @include('formError')

                    @if(session()->has('error'))
                        <p style="color: red">{{session('error')}}</p>
                    @endif
                    <input type="hidden" name="_token" value="{{csrf_token()}}">
                    <span class="login100-form-title">
						{{__("Login")}}
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

                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn">
                            {{__("Login")}}
                        </button>
                    </div>
                    <br><br><br>

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
        function refreshCaptcha(e){
            var element = e;
            axios.get('/captcha-refresh').then(function(response){
                element.target.src = response.data

            });
        }
    </script>

@endsection