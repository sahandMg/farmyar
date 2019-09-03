<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
         <script src="https://cdn.jsdelivr.net/npm/vue@2.5.21/dist/vue.js"></script>
             <script  src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js" ></script>
                <script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.js"></script>
                 <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
         <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
             <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
             <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <title>Document</title>
</head>
<body>

    <div class="container">

        <h1>Your Payment Information</h1>

        <table class="table table-striped">
            <thead>
            <tr>
                <td>OrderID</td>
                <td>Amount</td>
                <td>Time</td>
                <td>Status</td>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{$data['orderID']}}</td>
                <td>{{$data['amount']}}</td>
                <td>{{$data['created_at']}}</td>
                <td>Not Confirmed</td>
            </tr>
            </tbody>
        </table>
    </div>




</body>
</html>