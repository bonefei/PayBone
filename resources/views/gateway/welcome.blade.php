<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="{{asset('css/font.css')}}" rel="stylesheet" type="text/css">

        <style>
            html, body {
                margin: 0;
                padding: 0;
            }
            .header {
                width: 100%;
                height: 60px;
                /*border-bottom: 3px solid #2d5597;*/
            }
            .header-inner {
                height: 100%;
            }
            .content {
                width: 100%;
                padding-top: 10px;
            }
            .header-inner,
            .pay-info,
            .bank-list,
            .middle-desc {
                width: 75%;
                margin: 0 auto;
            }
            .pay-info {
                background-color: #ecf0f1;
                padding: 15px 20px;
                box-sizing: border-box;
                font-size: 0;
            }
            .middle-desc {
                height: 40px;
                color: #f96d1d;
                line-height: 40px;
                font-size: 16px;
                font-weight: bold;
            }
            .bank-list {
                border: 6px solid #c5d6e6;
                box-sizing: border-box;
                padding: 20px 0;
                box-sizing: border-box;
            }
            .logo {
                width: 150px;
                height: 100%;
                float: left;
                /* background: url(../resources/assets/img/bank/logo.jpg) no-repeat left center; */
                background-size: 100% 60%;
            }
            .service {
                float: right;
                height: 100%;
            }
            .service {
                width: 150px;
                /* background: url(../resources/assets/img/bank/phone.jpg) no-repeat left center; */
                background-size: 100% 40%;
            }
            .info-wrap {
                width: 100%;
            }
            .info-item {
                font-size: 14px;
                color: #333;
                margin: 10px 0;
                display: inline-block;
                width: 30%;
            }
            .order-amount {
                font-size: 18px;
                color: #f76d1d;
            }
            .list-wrap {
                width: 100%;
                height: 100%;
                font-size: 0;
                margin-bottom: 20px;
            }
            .bank-item {
                width: 20%;
                height: 50px;
                display: inline-block;
                text-align: center;
                margin: 10px 0;
                position: relative;
            }
            .bank-logo {
                display: inline-block;
                width: 70%;
                height: 100%;
                border: 1px solid #eee;
                background-color: #fff;
                cursor: pointer;
                padding: 8px;
                box-sizing: border-box;
            }
            .btn-wrap {
                width: 20%;
                height: 40px;
            }
            .sure-btn {
                width: 70%;
                height: 100%;
                border: none;
                background-color: #3f6d9a;
                color: #fff;
                font-size: 16px;
                cursor: pointer;
            }
            .bank-logo.active {
                border: 1px solid #3f6d9a;
            }
            .checked-icon {
                position: absolute;
                right: 15%;
                bottom: 0;
                display: inline-block;
                width: 30px;
                height: 30px;
                background: url('./images/bank/checked.png') no-repeat right bottom;
                background-size: 100% 100%;
                opacity: 0;
            }
            .bank-logo.active+.checked-icon {
                opacity: 1;
            }
            .bank-ipt {
                display: none;
            }
            .show-bank {
                width: 100%;
                height: 100%;
                background-repeat: no-repeat;
                background-position: center;
                background-size: 100% 100%;
            }
            @media only screen and (max-width: 1250px) {
                .info-item {
                    width: 50%;
                }
                .bank-item {
                    width: 25%;
                    height: 40px;
                }
                .btn-wrap {
                    width: 25%;
                }
            }
            @media only screen and (max-width: 820px) {
                .info-item {
                    width: 100%;
                }
                .bank-item {
                    width: 33.33%;
                }
                .btn-wrap {
                    width: 33.33%;
                }
            }     
    </style>
    </head>
    <body>
        <input type="hidden" id="is_credit" value="{{$isCredit}}">
        <input type="hidden" id="getBank" value="{{$signalBank}}">
        
        <div class="header">
            <div class="header-inner">
                <div class="logo"></div>
                <div class="service"></div>
            </div>       
        </div>
        <div class="content">
            <div class="pay-info bank-ipt">
                <div class="info-wrap">
                    <p class="info-item">
                        <span class="info-title">订单金额：</span>
                        <span class="info-data">
                            <span class="order-amount">￥{{$param['Amount']}}</span>元
                        </span>
                    </p>
                    <p class="info-item">
                        <span class="info-title">购买商品：</span>
                        <span class="info-data order-name">
                            {{$param['BuyProduct']}}
                        </span>
                    </p>
                    <p class="info-item">
                        <span class="info-title">订单订单编号：</span>
                        <span class="info-data order-number">
                        {{$param['Billno']}}
                        </span>
                    </p>
                    <p class="info-item">
                        <span class="info-title">收款商家：</span>
                        <span class="info-data business-name">
                        AGMTrade
                        </span>
                    </p>
                    <p class="info-item">
                        <span class="info-title">交易日期：</span>
                        <span class="info-data trade-date">
                        {{gmdate('Y/m/d H:i:s',time()+8*3600)}}(GMT+8)
                        </span>
                    </p>
                </div>
            </div>
            <div class="middle-desc">
                选择银行
            </div>
            <div class="bank-list" align="right">
                <form class="list-wrap" align="left" id="payForm" action="gateway/pay_post">  
                    @foreach($bankCode as $value=>$name)
                        <div class="bank-item">
                            <input type="radio" id="{{$value}}" class="bank-ipt" name="bankCode" @if($isCredit != 1 && $value == '0000') checked @elseif($signalBank != '' && $signalBank == $value) checked @endif value="{{$value}}">
                            <label for="{{$value}}" class="bank-logo" bank-name="{{$name}}">
                                <div class="show-bank" style="background-image: url('./images/bank/{{preg_match_all("/(?:\()(.*)(?:\))/i",$name, $result)?$result[1][0]:$name}}.png')"></div>
                            </label>
                            <span class="checked-icon"></span>
                        </div>
                    @endforeach              
                </form>
                <div class="btn-wrap" align="center">
                    <button class="sure-btn" id="paySubmit">确定</button>
                </div>

                <div class="hiddenRow">
                    <input type="hidden" name="payname" id="payname" value="{{$payment_away}}"/>
                    <input type="hidden" name="zoroCode" id="zoroCode" value="{{$zoroCode}}"/>
                    
                    @foreach($param as $k=>$v)
                        <input type="hidden" name="{{$k}}" value="{{$v}}">
                    @endforeach
                </div>

                <div id="formSubmit" style="display:hidden;"></div>
            </div>
        </div>
    </body>
    <script src="{{asset('js/jquery-3.3.1.min.js')}}"></script>
    <script src="{{asset('js/alert.min.js')}}"></script>

    <script>
        function formSubmit(){
            var checkVal = $("input[name=bankCode]:checked").val();
            var radio_id = $("input[name=bankCode]:checked").attr('id');
            var bankName = $("label[for='"+radio_id+"']").attr('bank-name');
            if(!checkVal){
                // $.alert('请选择银行');
                return false;
            }
            //return false;
            var paramPost = {};
            $.each($('.hiddenRow input'),function(i,e){
                console.log($(this).val(),$(this).attr('name'))
                paramPost[$(this).attr('name')] = $(this).val();
            })
            //console.log(paramPost)
            paramPost.bankCode = checkVal;
            paramPost.bankName = bankName;
            $.load('加载中...');
            $.post({
                url:'gateway/makePost',
                type:'POST',
                dataType:'json',
                data:JSON.stringify(paramPost),
                contentType:'application/json;charset=utf-8',
                success:function(res){
                    //res = JSON.parse(res);
                    $.loaded();
                    if(res.status == 200){
                        $('#formSubmit').html(res.msg);
                        $.load('正在跳转银行...')
                        window.setTimeout(function(){
                            $("#formSubmit form").submit();
                        },100)
                    }else{
                        if(res.status == 'E003'){
                            $('.content').html(res.msg)
                        }else{
                            $.alert(res.msg);
                        }
                    }
                }
            })
        }
        $(function(){
            // $(':input').labelauty();
            if($('#is_credit').val() != 1 ||( $('#getBank').val() != ''&&$('#getBank').val() != null)){
                $('#payForm').css('display','none');
                formSubmit();
            }
            $("#paySubmit").click(function(){
                formSubmit();
                
            })
            
        });

        $('.bank-logo').on('click', function(){
            $('.bank-logo').removeClass('active');
            $(this).addClass('active');
        });

    </script>
</html>
