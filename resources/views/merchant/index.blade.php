<!DOCTYPE html>
<html lang="zh-cn">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>商户后台</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" media="screen"  />
    </head>

    <body>
        <div id="app"></div>
        <script src="{{ mix('dist/manifest.js') }}"></script>
        <script src="{{ mix('dist/vendor.js') }}"></script>
        <script src="{{ mix('dist/merchant.js') }}"></script>
    </body>
</html>
