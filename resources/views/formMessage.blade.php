@if(session()->has('message'))
    <p style="color: green;">{{session('message')}}</p>
@endif