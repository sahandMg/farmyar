<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

<table>
        <tr>
                <td style="font-weight:bold">Currency</td>
                <td></td>
                <td></td>
                <td></td>
                <td style="font-weight:bold">Price</td>

            </tr>

    @foreach($CryptoCrawl as $name => $item)

        <tr>
<td style="color:blue">{{$name}}</td>
<td></td>
<td></td>
<td></td>
<td style="color:red">{{$item}}</td>

            </tr>
@endforeach
</table>



</body>
</html>
