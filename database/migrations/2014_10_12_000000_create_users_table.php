<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('payname', 50)->comment('商户名称')->unique();
            $table->string('name', 10)->comment('管理员OR用户姓名');
            $table->string('phone', 11)->comment('管理员OR用户手机号码');
            $table->string('email', 30)->comment('管理员OR用户邮箱');
            $table->string('password')->comment('管理员OR用户登录密码');
            $table->tinyInteger('identity')->comment('区分身份 1 为管理员账号 2 为子管理员 3 为商户账号')->default(3);
            $table->text('token')->comment('用户第一次存储token信息');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
