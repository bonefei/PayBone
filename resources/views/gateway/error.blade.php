<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Error</title>
        <!-- Fonts -->
        <link href="{{asset('css/font.css')}}" rel="stylesheet" type="text/css">

        <!-- Styles -->
       
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
                padding:0 5% 0 5%;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
                text-align: left;
            }
            .title2{
                text-align: right;
            }
            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
            .order_info{
                margin-top:-283px;
                height:160px;
                width:100%;
                padding:0 50px 0 50px;
                background:#cccccc61;
            }
            .OrderFont{
                color:#e49212;
                font-size:34px;
                font-weight:500
            }
            .row{
                width:70%;
                float:right;
            }
            .col-50{
                width:49.9%;
                text-align:left;
            }
            .lf{
                font-weight:bold;
            }
            a{
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title m-b-md">
                    代码：{{$error['status']}}
                    <br/>
                    错误：{{$error['msg']}}
                    <br/>
                </div>    
                <div class="title2 links">
                    <a href="{{$error['from']}}">返回</a>
                </div>     
                
            </div>
        </div>
    </body>
    <script src="{{asset('js/jquery-3.3.1.min.js')}}"></script>
</html>