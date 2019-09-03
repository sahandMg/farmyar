@extends('admin.master.header')
@section('content')

    @if(count($logs) > 0)
    <table class="table bordered">
        <thead>
            <th>Message</th>
            <th>Context</th>
            <th>Extra</th>
            <th>Time</th>
        </thead>

        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{$log->message}}</td>
                <td>{{$log->context}}</td>
                <td>{{$log->extra}}</td>
                <td>{{\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($log->created_at))}}</td>

            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
    @else
    <h1>No Log</h1>
    @endif
@endsection