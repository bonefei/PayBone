<?php
return [
    //id=000015&url=https://pay.ips.net.cn/ipayment.aspx&key=GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGzO5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ&remark=
    'conf' => [
        'ips' => [
            'id' => '000015',
            'url' => 'http://pay.ips.net.cn/ipayment.aspx',
            'key' => 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGzO5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ',
            'remark' => '',
        ],
        'GfbPay' => [
            'id' => '0000000002000050773',
            'url' => 'https://gateway.gopay.com.cn/Trans/WebClientAction.do',
            'key' => 'pdm123456789',
            'remark' => '0000114780',
        ],
        'ipsv7' => [
            'id' => '205653',
            'url' => 'https://newpay.ips.com.cn/psfp-entry/gateway/payment.do',
            'key' => 'FqFOD3zouvV1CMAkcFfswlMVg6ONrv3mc8d5locJyMVGMw4yen3odfQG5L1C55F1Zo1wBS5DaJJ9l2tqBOJF3X1urDDYewZbxNVwHLqdF5ebVb1QSjdYeBb7dKVNHtra',
            'remark' => '',
            'Version' => 'v1.0.0',
            //交易賬戶號
            'Account' => '2056530013',
        ],
        'QfbPay' => [
            'id' => '0000000002000050773',
            'url' => 'https://gateway.gopay.com.cn/Trans/WebClientAction.do',
            'key' => 'pdm123456789',
            'remark' => '0000114780',
        ],
    ],
    'Info' => [
        'ips' => [
            'r_url' => '',
            's_url' => '',
            "Mer_code" => '', //商户ID
            "Mer_key" => '', //商户证书
            "Billno" => '', //订单号
            "Amount" => 0, //金额
            "Date" => '', //订单日期
            "Currency_Type" => "RMB", //币种
            "Gateway_Type" => "01", //支付方式
            "Lang" => "GB", //语言
            "Merchanturl" => '', //支付结果成功返回的商户URL
            "FailUrl" => '', //支付结果失败返回的商户URL
            "ErrorUrl" => '', //支付结果错误返回的商户URL
            "Attach" => '', //支付结果错误返回的商户URL
            "DispAmount" => 0, //显示金额
            "OrderEncodeType" => "5", //订单支付接口加密方式
            "RetEncodeType" => "17", //交易返回接口加密方式
            "Rettype" => "1", //返回方式
            //"ServerUrl" => $_SESSION['td_url']."/Paynotify/Ips_test_finish",  //Server to Server 返回页面URL
            "ServerUrl" => '',
            'DoCredit' => 1,
            'Bankco' => '',
            'SignMD5' => '',
        ],
        'GfbPay' => [
            'r_url' => '',
            's_url' => '',
            'res_url' => '',
            'version' => '2.2', //版本号
            'charset' => '2', //字符集1:GBK,2:UTF-8 (不填则当成1处理)
            'language' => '1', //网关语言1:ZH,2:EN
            'signType' => '1', //报文加密方式 1:MD5,2:SHA
            'tranCode' => '8888', //交易代码
            'merchantID' => '0000114780', //签约国付宝商户唯一用户 ID
            'merOrderNum' => '', //订单号
            'tranAmt' => 0, //交易金额
            'feeAmt' => '', //商户提取佣金
            'currencyType' => '156', //币种
            'frontMerUrl' => '', //前台通知地址
            'backgroundMerUrl' => '', //商户后台通知地址
            'tranDateTime' => '', //交易时间YYYYmmddHis
            'virCardNoIn' => '0000000002000050773', //国付宝转入账户
            'tranIP' => '127.0.0.1',
            'isRepeatSubmit' => '0', //订单是否允许重复提交 0 否 1 是
            'goodsName' => '', //商品名称
            'goodsDetail' => '', //商品详情
            'buyerName' => '', //买方姓名
            'buyerContact' => '', //买方联系方式
            'merRemark1' => '', //商户备用信息字段
            'merRemark2' => '', //商户备用信息字段
            'buyerRealMobile' => '', //手机号
            'buyerRealName' => '', //姓名
            'buyerRealCertNo' => '', //身份证号
            'buyerRealBankAcct' => '', //银行卡号
            'gopayServerTime' => '', //服务器时间
            'bankCode' => '', //银行
            'userType' => '1', //用户类型 1 个人网银 2 企业网银
        ],
        'ipsv7' => [
            'r_url' => '',
            's_url' => '',
            "RedirectUrl" => '',
            "Version" => 'v1.0.0', //版本
            "MerCode" => '', //商户号
            "Account" => '', //交易账户号
            //证书
            "MerCert" => '',
            "PostUrl" => 'https://newpay.ips.com.cn/psfp-entry/gateway/payment.do', //提交地址
            "S2Snotify_url" => '', //异步S2S返回
            "Return_url" => '',
            "CurrencyType" => '156',
            "Lang" => 'GB',
            "OrderEncodeType" => '5',
            "RetType" => '1', //
            "MerBillNo" => '', //订单号
            "MerName" => '', //商户名
            "MsgId" => '',
            "PayType" => '01', //支付方式 1-借记卡 2 信用卡 3IPS账户
            "FailUrl" => '',
            "Date" => '',
            "ReqDate" => '',
            "Amount" => '0',
            "Attach" => '',
            "RetEncodeType" => '17', //交易返回接口加密方式 17 MD5
            "BillEXP" => '3', //订单有效期 整数 小时为单位
            "GoodsName" => 'Deposit', //商品名称
            "BankCode" => '', //银行代码
            "IsCredit" => '1', //是否直连 1-直连 ''-非直连
            "ProductType" => '1', //产品类型 1个人网银 2企业网银
            "UserRealName" => '',
            "UserId" => '',
            "CardInfo" => '',
        ],
        'QfbPay' => [
            "orderNo" => "", //orderNo 合作方订单号
            "bizcode" => "", //业务编码 3103 网银 3109 网银WAP
            "memberNo" => "", //商户号
            "transAmt" => 0, //订单金额
            "notifyUrl" => "", //异步通知地址
            "bankcode" => "", //银行简称
            "cardTyp" => '', //银行卡类型0:贷记卡，1：借记卡
            "showUrl" => '', //跳转地址
        ],
        'Sun8Pay' => [
            'version' => '1', //固定值1。可能因业务原因改变，若有变更，对接技术支持会特殊强调
            'data' => '', //发起请求时，只需要一个data参数，data为加密后的字符串，加密方法见下
            'payCode' => '', //业务代码，支持的支付方式在商户后台-接口设置中查看，提交不支持的支付方式将会被拒绝交易。
            'merId' => '', //商户号，商户在sun8pay支付系统的唯一身份标识
            'orderNo' => '', //商户订单号
            'payAmt' => '0.00', //支付金额，单位元，小数点后最多两位，必须大于等于0.01
            'productName' => '', //商品名称
            'notifyUrl' => '', //支付成功后页面回调地址。商户亦可在管理后台设置，以提交参数为准，若同时为空，将无法收到支付结果。
            'bankCode' => '', //银行代码  详见银行通道代码 网银支付不填写时，将跳转至sun8pay收银台。非网银支付无须填写。
        ],
        'YinLian' => [
            //以下信息非特殊情况不需要改动
            'version' => '', //版本号
            'encoding' => 'utf-8', //编码方式
            'txnType' => '01', //交易类型
            'txnSubType' => '01', //交易子类
            'bizType' => '000201', //业务类型
            'frontUrl' => '', //前台通知地址
            'backUrl' => '', //后台通知地址
            'signMethod' => '', //签名方法
            'channelType' => '08', //渠道类型，07-PC，08-手机
            'accessType' => '0', //接入类型
            'currencyCode' => '156', //交易币种，境内商户固定156

            //TODO 以下信息需要填写
            'merId' => '', //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => '', //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => '', //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => '', //交易金额，单位分，此处默认取demo演示页面传递的参数

            // 订单超时时间。
            // 超过此时间后，除网银交易外，其他交易银联系统会拒绝受理，提示超时。 跳转银行网银交易如果超时后交易成功，会自动退款，大约5个工作日金额返还到持卡人账户。
            // 此时间建议取支付时的北京时间加15分钟。
            // 超过超时时间调查询接口应答origRespCode不是A6或者00的就可以判断为失败。
            'payTimeout' => '',

            // 请求方保留域，
            // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
            // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
            // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
            //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
            // 2. 内容可能出现&={}[]"'符号时：
            // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
            // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
            //    注意控制数据长度，实际传输的数据长度不能超过1024位。
            //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
            //    'reqReserved' => base64_encode('任意格式的信息都可以'),
        ],
        'RPay' => [
            "version" => '1.0', //版本号
            "sign_type" => 'MD5',
            "mid" => '',
            "return_url" => '',
            "notify_url" => '',
            "order_id" => '',
            "order_amount" => 0,
            "order_time" => '',
            "bank_id" => '',
        ],
        'ZotaPay' => [
            'client_orderid' => '',
            'order_desc' => 'Order Description',
            'first_name' => 'zoroUser',
            'last_name' => 'zoro',
            'ssn' => '1267',
            'birthday' => '19820115',
            'address1' => '100 Main st',
            'city' => 'Beijing',
            'state' => 'WA',
            'zip_code' => '100000',
            'country' => 'CN',
            'phone' => '+8612345678901',
            'cell_phone' => '+8612345678901',
            'amount' => 0,
            'email' => 'zoro@zoro.com',
            'currency' => 'CNY',
            'ipaddress' => '',
            'site_url' => '',
            'destination' => '',
            'redirect_url' => '',
            'server_callback_url' => '',
            'merchant_data' => 'VIP customer',
        ],
        "SDPay" => [
            'version' => '1.0',
            'method' => 'sandpay.trade.pay',
            'productId' => '00000007',
            'accessType' => '1',
            'mid' => '',
            'channelType' => '07',
            'reqTime' => '',
            'orderCode' => '',
            'totalAmount' => '',
            'subject' => '',
            'body' => '',
            'txnTimeOut' => '',
            'payMode' => '',
            'payExtra' => '',
            'clientIp' => '',
            'notifyUrl' => '',
            'frontUrl' => '',
            'extend' => '',
        ],
        "XPay"=>[
            'requestNo' => '',
            'version' => '',
            'productId' => '',
            'transId' => '',
            'merNo' => '',
            'orderDate' => '',
            'orderNo' => '',
            'notifyUrl' => '',
            'returnUrl' => '',
            'transAmt' => '',
            //'userId' => '',
            'commodityName' => '',
            'commodityDesc' => '',
        ]
    ],
    'withdrawal' => [
        'ipsv7' => [
            'Version' => 'v1.0.0',
            'MerCode' => '', //商戶號
            'Account' => '', //交易賬戶號
            'MerCert' => '', //商戶證書
            'PostUrl' => '', //請求地址
            'MsgId' => '', //消息编号
            'DES_KEY' => '', //3DES加密KEY
            'DES_IV' => '', //3DES加密向量
            "ReqDate" => '',
            "MerName" => '',//商戶名稱
            "BizId" => '',//下发类型【下发类型 1.B2C个人银行账号下发  3.B2B公司银行账号下发  】
            "ChannelId" => '',//渠道类型【下发渠道_下发渠道 1、即时渠道；2、快速渠道；3、普通 渠道】
            "Currency" => '156',//币种
            "Date" => '',
            "Attach" => '',//批次备注信息
            "MerBillNo" => '',//商户订单号【商户自定义，只能数字或者大小写英文字符， 保证唯一性 】
            "AccountName" => '',//收款人姓名【委托付款银行卡的账户名 这里的长度是指最大支持25个汉字 】
            "AccountNumber" => '',//收款银行账户号【企业银行账户号，或者个人银行卡号】
            "BankName" => '',//银行名称【银行卡所属银行的全称 这里的长度是指最大支持25个汉字 】
            "BranchBankName" => '',//支行名称【支行名称 这里的长度是指最大支持25个汉字 BizId=1且ChannelId=3时必填， 其它方式非必填 】
            "BankCity" => '',//银行卡所属城市【例如：苏州市 这里的长度是指最大支持25个汉字 BizId=1且ChannelId=3时必填， 其它方式非必填 】
            "BankProvince" => '',//银行卡所属省份【例如：江苏省 这里的长度是指最大支持25个汉字 BizId=1且ChannelId=3时必填， 其它方式非必填 】
            "BillAmount" => '',//下发金额
            "IdCard" => '',//身份证号【BizId=1且ChannelId=3时必填， 其它方式非必填 】
            "MobilePhone" => '',//手机号码【BizId=1且ChannelId=3时必填， 其它方式非必】
            'Remark'=>'',//明细备注 【这里的长度是指最大支持25个汉字 】
        ],
    ],
    'bankCode' => [
        'ips' => [
            '00004' => '中国工商银行',
            '00015' => '中国建设银行',
            '00083' => '中国银行',
            '00017' => '中国农业银行',
            '00005' => '交通银行',
            '00021' => '招商银行',
            '00054' => '中信银行',
            '00057' => '中国光大银行',
            '00016' => '兴业银行',
            '00032' => '浦发银行',
            '00013' => '中国民生银行',
            '00087' => '平安银行',
            '00041' => '华夏银行',
            '00052' => '广发银行',
            '00051' => '中国邮政储蓄银行',
            '00096' => '东亚银行',
            '00056' => '北京农村商业银行',
            '00050' => '北京银行',
            '00095' => '渤海银行',
            '00023' => '深圳发展银行',
            '00122' => '中国农业银行',
        ],
        'ipsv7' => [
            '1100' => '工商银行',
            '1101' => '农业银行',
            '1102' => '招商银行',
            '1103' => '兴业银行',
            '1104' => '中信银行',
            '1107' => '中国银行',
            '1108' => '交通银行',
            '1109' => '浦发银行',
            '1110' => '民生银行',
            '1111' => '华夏银行',
            '1112' => '光大银行',
            '1113' => '北京银行',
            '1114' => '广发银行',
            '1115' => '南京银行',
            '1116' => '上海银行',
            '1117' => '杭州银行',
            '1118' => '宁波银行',
            '1119' => '邮储银行',
            '1120' => '浙商银行',
            '1121' => '平安银行',
            '1122' => '东亚银行',
            '1123' => '渤海银行',
            '1124' => '北京农商行',
            '1127' => '浙江泰隆商业银行',
            '1106' => '中国建设银行',
        ],
        'GfbPay' => [
            'CCB' => '中国建设银行',
            'CMB' => '招商银行',
            'ICBC' => '中国工商银行',
            'BOC' => '中国银行',
            'ABC' => '中国农业银行',
            'BOCOM' => '交通银行',
            'CMBC' => '中国民生银行',
            'HXBC' => '华夏银行',
            'CIB' => '兴业银行',
            'SPDB' => '上海浦东发展银行',
            'GDB' => '广东发展银行',
            'CITIC' => '中信银行',
            'CEB' => '光大银行',
            'PSBC' => '中国邮政储蓄银行',
            'BOBJ' => '北京银行',
            'BOS' => '上海银行',
            'PAB' => '平安银行',
            'NBCB' => '宁波银行',
            'NJCB' => '南京银行',
        ],
    ],
];
