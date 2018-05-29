<?php

namespace App\Http\Controllers\merchant\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SystemController extends Controller
{
    protected static $successStatus = 200;

    /**
     * 获取网关黑名单列表
     */
    public function gatewayBlacklistList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'a.Type' => 1,
            'a.zoroCode' => request('payCode')
        );

        $Rows = DB::table('zoro_disabled_rule AS a')
            ->leftJoin('zoro_user_users AS b', 'a.zoroCode', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->where([
                'c.id' => Auth::user()->id
            ])
            ->select([
                'a.*'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 获取网关黑名单商户号
     */
    public function gatewayBlacklistSelectPayCode(Request $requset)
    {
        $getPayCode = DB::table('zoro_user_info AS a')
            ->leftJoin('zoro_user_users AS b', 'a.id', '=', 'b.user_id')
            ->where([
                'a.roles' => 3,
                'a.fid' => Auth::user()->fid
            ])
            ->select([
                DB::raw('b.user_no as label'),
                DB::raw('b.user_no as value')
            ])
            ->get();

        if ($getPayCode) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayCode ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '请创建账号' ], self::$successStatus);
        }
    }

    /**
     * 添加网关黑名单
     */
    public function gatewayBlacklistCreate(Request $request)
    {
        $getDisabledId = DB::table('zoro_disabled_rule')
            ->where([
                'zoroCode' => request('payCode')
            ])
            ->count('id');

        if ($getDisabledId > 0) {
            return response()->json([ 'Status' => 'GBC101', 'Content' => '当前商户号已有黑名单请进行修改' ], self::$successStatus);
        }

        $insertDisabledArray = array(
            'zoroCode' => request('payCode'),
            'KeyWords' => request('KeyWords'),
            'Ips' => request('Ips')
        );

        $insertDisabled = DB::table('zoro_disabled_rule')->insert($insertDisabledArray);

        if ($insertDisabled) {
            return response()->json([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GBC102', 'Content' => '创建网关黑名单失败' ], self::$successStatus);
        }
    }

    /**
     * 修改网关黑名单
     */
    public function gatewayBlacklistUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'GBU100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $updateDisabledArray = array(
            'KeyWords' => self::makeSemiangle(request('KeyWords')),
            'Ips' => self::makeSemiangle(request('Ips'))
        );

        $updateData = DB::table('zoro_disabled_rule')
            ->where([
                'id' => request('id')
            ])
            ->update($updateDisabledArray);

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '更改成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GBU101', 'Content' => '更改失败' ], self::$successStatus);
        }
    }

    /**
     * 获取网关白名单列表
     */
    public function gatewayWhitelistList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'a.Type' => 1,
            'a.zoroCode' => request('payCode')
        );

        $Rows = DB::table('zoro_enable_rule AS a')
            ->leftJoin('zoro_user_users AS b', 'a.zoroCode', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->where([
                'c.id' => Auth::user()->id
            ])
            ->select([
                'a.id',
                'a.zoroCode',
                'a.Ips',
                'a.Type',
                'c.user_name'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 获取网关白名单商户号
     */
    public function gatewayWhitelistSelectPayCode(Request $requset)
    {
        $getPayCode = DB::table('zoro_user_info AS a')
            ->leftJoin('zoro_user_users AS b', 'a.id', '=', 'b.user_id')
            ->where([
                'a.roles' => 3,
                'a.fid' => Auth::user()->fid
            ])
            ->select([
                DB::raw('b.user_no as label'),
                DB::raw('b.user_no as value')
            ])
            ->get();

        if ($getPayCode) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayCode ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '请创建账号' ], self::$successStatus);
        }
    }

    /**
     * 添加网关白名单
     */
    public function gatewayWhitelistCreate(Request $request)
    {
        $getDisabledId = DB::table('zoro_enable_rule')
            ->where([
                'zoroCode' => request('payCode')
            ])
            ->count('id');

        if ($getDisabledId > 0) {
            return response()->json([ 'Status' => 'GBC101', 'Content' => '当前商户号已有白名单请进行修改' ], self::$successStatus);
        }

        $insertDisabledArray = array(
            'zoroCode' => request('payCode'),
            'Ips' => request('Ips')
        );

        $insertDisabled = DB::table('zoro_enable_rule')->insert($insertDisabledArray);

        if ($insertDisabled) {
            return response()->json([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GBC102', 'Content' => '创建网关白名单失败' ], self::$successStatus);
        }
    }

    /**
     * 修改网关白名单
     */
    public function gatewayWhitelistUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'GBU100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $updateDisabledArray = array(
            'Ips' => self::makeSemiangle(request('Ips'))
        );

        $updateData = DB::table('zoro_enable_rule')
            ->where([
                'id' => request('id')
            ])
            ->update($updateDisabledArray);

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '更改成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GBU101', 'Content' => '更改失败' ], self::$successStatus);
        }
    }

    /**
     * 将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     *
     * @access public
     * @param string $str 待转换字串
     * @return string $str 处理后字串
     */
    protected static function makeSemiangle($str)
    {
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
        '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
        '‘' => '[', "'" => ']', '｛' => '{', '｝' => '}', '《' => '<',
        '》' => '>',
        '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
        '：' => ':', '。' => '.', '、' => ',', '，' => ',', '、' => '.',
        '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
        '”' => '"', "'" => '`', '‘' => '`', '｜' => '|', '〃' => '"',
        '　' => ' ');

        return strtr($str, $arr);
    }

    /**
     * 删除网关黑名单
     * 
     * @params:
     * @return:
    */
    public function gatewayBlacklistDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'GBD101', 'Content' => '请确认操作是否正确' ], self::$successStatus);
        }

        $deleteData = DB::table('zoro_disabled_rule')
            ->where([
                'id' => request('id')
            ])
            ->delete();

        if ($deleteData) {
            return response()->json([ 'Status' => '200', 'Content' => '删除成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GBD102', 'Content' => '删除失败' ], self::$successStatus);
        }
    }

    /**
     * 删除网关白名单
     * 
     * @params：
     * @return:
    */
    public function gatewayWhitelistDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'GWD101', 'Content' => '请确认操作是否正确' ], self::$successStatus);
        }

        $deleteData = DB::table('zoro_enable_rule')
            ->where([
                'id' => request('id')
            ])
            ->delete();

        if ($deleteData) {
            return response()->json([ 'Status' => '200', 'Content' => '删除成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GWD102', 'Content' => '删除失败' ], self::$successStatus);
        }
    }
}
