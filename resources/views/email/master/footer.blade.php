<footer>
    @if(\Illuminate\Support\Facades\Config::get('app.locale') == 'fa')
        <a href="https://www.instagram.com/hashbazaar/" target="_blank">اینستاگرام</a>
        {{--  <a href="{{url('/blog')}}" target="_blank">مجله</a>  --}}
        <a href="{{route('remoteDashboard',['locale'=>session('locale')])}}" target="_blank">میزکار</a>
        <a href="{{route('index',['locale'=>session('locale')])}}" target="_blank">وب سایت</a>
    @else
        <a href="{{route('index',['locale'=>session('locale')])}}" target="_blank"> Website</a>
        <a href="{{route('remoteDashboard',['locale'=>session('locale')])}}" target="_blank">Dashboard</a>
        {{--  <a href="{{url('/blog')}}" target="_blank">Blog</a>  --}}
        <a href="https://www.instagram.com/hashbazaar/" target="_blank">Instagram</a>
    @endif
</footer>

