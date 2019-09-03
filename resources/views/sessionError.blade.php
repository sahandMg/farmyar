@if(session()->has('error'))
    <p style="color: red;">{{session('error')}}</p>
@endif