<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
     <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <title>Document</title>
</head>
<body>


<div class="container">

    <h1>New Message</h1>
    <ul>
        <li>User Name : {{$name}}</li>
        <li>User Email : {{$email}}</li>
        <li>User Message : </li>

    </ul>

        <p>{{$UserMessage}}</p>

</div>

</body>
</html>