<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Zizaco\Entrust\Traits\EntrustUserTrait;

class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;
    //This will enable the relation with Role and add the following methods roles(), hasRole($name), withRole($name), can($permission), and ability($roles, $permissions, $options) within your User model.
    use EntrustUserTrait;  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'zoro_user_info';

    /** 
     * 指定主鍵 
     */ 
    // protected $primaryKey = 'id'; 

    /**
     * 指定是否模型應該被戳記時間。
     *
     * @var bool
     */
    // protected $timestamps = true;

    /**
     * 模型的日期欄位的儲存格式。
     *
     * @var string
     */
    // protected $dateFormat = 'U';

    /**
     * 此模型的連接名稱。
     *
     * @var string
     */
    // protected $connection = 'connection-name';

    /**
     * 可以被批量賦值的屬性。
     * 
     * @var array
     */
    /* protected $fillable = [
        'mobile', 'email', 'password',
    ]; */

    /**
     * 不可以被批量賦值的屬性。
     *
     * @var array
     */
    protected $guarded = [

    ];

    /**
     * 数组隐藏的属性
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'token',
    ];

    /**
     * Return passport need info.
     * 
    */
    public function findForPassport($username) {
        return $this->where('mobile', $username)->first();
    }
}
