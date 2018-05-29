# 聚合支付系统

## 技术框架
- 前台vue
- 后台laravel

## 运行环境
- mysql5.6
- php7.1

## 安装步骤
```
#php依赖
composer install 
#js依赖
npm install
#js打包
npm run dev
#运行内置服务器
php ./artisan serve
#浏览器访问地址
127.0.0.1:8000/admin
```

## 目录结构
- admin 管理后台，单独部署web服务
- merchant 商户后台，单独部署web服务
- gateway 支付网关，单独部署web服务
- reconciliation 对账应用
- settlement 结算应用
- notify 通知应用
- shop 模拟商城
- doc 文档

## js/json/vue开启eslint格式如下

1、禁止混用tab和空格

2、缩进风格4个空格

3、禁用var，用let和const代替

4、箭头函数用小括号括起来

5、生成器函数*的前后空格

6、禁止使用debugger

7、只允许使用===或!== 不能使用==或!=

8、禁止在最后加分号(;)

9、方法括号前后加空格

10、只能使用ES6语法

11、箭头函数用小括号括起来

12、文件最后保留一个换行

## 测试服务器启动关闭

```
#进入容器内部
cd /webgroup/www/ZoroPay/docker
#启动
sudo docker-compose up -d nginx redis

#关闭
sudo docker-compose down

#重启
sudo docker-compose restart

```

## 测试服务器检出版本


```
#使用ssh连接远程Linux服务器后切换目录
cd /webgroup/www/ZoroPay

#检出项目（服务器已配置git公钥）
git pull 

```

## 测试安装依赖

- 安装php依赖 (初次和依赖变化时,需要提交.lock文件)

```
#切换目录
cd /webgroup/www/ZoroPay/docker

#进入容器
sudo docker-compose exec workspace  bash

#安装php依赖
composer install

```

- 安装js依赖 (初次和依赖变化时,需要提交.lock文件)

```
#切换目录
cd /webgroup/www/ZoroPay/docker

#进入容器
sudo docker-compose exec workspace  bash

#安装依赖
npm install

```

- 打包js代码

```
#切换目录
cd /webgroup/www/ZoroPay/docker

#进入容器
sudo docker-compose exec workspace  bash

#打包
npm run dev

```







## 文档参考
http://laradock.io/getting-started/
