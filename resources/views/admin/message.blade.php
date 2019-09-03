@extends('admin.master.header')
@section('content')

    @include('formMessage')
    <?php
    $messages = \App\Message::orderBy('created_at','desc')->get();
    ?>

    <div class="container">
        <table class="table">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Answer</th>
                <th>Delete</th>
            </tr>


            @foreach($messages as $message)

                <tr>
                    <td>{{$message->name}}</td>
                    <td>{{$message->email}}</td>
                    <td>{{$message->message}}</td>
                    <td><button data-toggle="modal" data-target="#myModal_{{$message->id}}" id="{{$message->id}}" class="btn btn-primary"> Answer</button></td>
                    <td><button onclick="deleteMessage({{json_encode($message->id)}})" id="delete{{$message->id}}" class="btn btn-danger"> Delete</button></td>
                </tr>
            {{-- Modal --}}

                <!-- The Modal -->
                <div class="modal" id="myModal_{{$message->id}}">
                    <div class="modal-dialog">
                        <div class="modal-content">

                            <!-- Modal Header -->
                            <div class="modal-header">
                                <h4 class="modal-title">{{$message->name}} </h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>

                            <!-- Modal body -->
                            <div class="modal-body">

                                <div id="messagePanel">
                                    <form style="padding: 20px;" method="POST" action="{{route('AdminMessage',['locale'=>session('locale')])}}">
                                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                                        <input type="hidden" name="id" value="{{$message->id}}">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Email</label>
                                            <input name="email" type="text" class="form-control" readonly value="{{$message->email}}" id="email" aria-describedby="emailHelp">
                                        </div>

                                        <div class="form-group">
                                            <label for="body"> Message</label>
                                            <textarea style="text-align: right; direction: rtl"  name="body" type="text"  class="form-control"  aria-describedby="emailHelp" placeholder="متن پیام"></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Send </button>

                                    </form>
                                </div>


                            </div>

                            <!-- Modal footer -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                            </div>

                        </div>
                    </div>
                </div>
                {{-- End Modal --}}
             @endforeach
        </table>


    </div>

    <script>
        function openPanel(id) {
            document.getElementById('email').value = id;
            document.getElementById('messagePanel').hidden = false
        }

        function deleteMessage(id) {
            axios.post('{!! (route('deleteMessage',['locale'=>session('locale')])) !!}',{'id':id}).then(function (response) {
                if(response.data == 200 ){

                    window.location.reload('{{ route('message',['locale'=>session('locale')])}}');
                }
            })

        }
    </script>
@endsection