<?php

namespace App\Http\Controllers\admin\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Validator;
use App\Models\User;

class AuthController extends Controller
{
    protected static $successStatus = 200;

    /**
     * 获取平台的角色列表
    */
    public function SelectRoleList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');

        $selectArray = array(
            'display_name' => request('display_name')
        );

        $Rows = DB::table('role')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->where([
                'type' => 1
            ])
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 创建平台新角色
    */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'display_name' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'CR100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        // 验证角色名称是否唯一
        $role = DB::table('role')
            ->where([
                'name' => request('name')
            ])
            ->count('id');

        if ($role > 0) {
            return response()->json([ 'Status' => 'CR101', 'Content' => '角色名称不能重复' ], self::$successStatus);
        }

        $insertArray = array(
            'type' => 1, // 管理员角色
            'name' => request('name'),
            'display_name' => request('display_name'),
            'description' => request('description'),
            'created_at' => date('Y-m-d H:i:s')
        );

        $insertId = DB::table('role')
            ->insertGetId($insertArray);

        if ($insertId) {
            return response()->json([ 'Status' => '200', 'Content' => '创建角色成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'CR102', 'Content' => '创建角色失败' ], self::$successStatus);
        }
    }

    /**
     * 删除平台角色
    */
    public function deleteRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'DR100', 'Content' => '参数缺失' ], self::$successStatus);
        }

        // 检查当前角色下是否有成员存在
        $roleChild = DB::table('role_user')
            ->where([
                'role_id' => request('id')
            ])
            ->count('role_id');

        if ($roleChild > 0) {
            return response()->json([ 'Status' => 'DR101', 'Content' => '请先删除角色下成员再删除角色' ], self::$successStatus);
        }

        $deleteData = DB::table('role')
            ->where([
                'id' => request('id')
            ])
            ->delete();

        if ($deleteData) {
            return response()->json([ 'Status' => '200', 'Content' => '删除角色成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'DR102', 'Content' => '删除角色失败' ], self::$successStatus);
        }
    }

    /**
     * 修改角色信息
    */
    public function modifyRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'display_name' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MR100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $modifyData = DB::table('role')
            ->where([
                'id' => request('id')
            ])
            ->update([
                'name' => request('name'),
                'display_name' => request('display_name'),
                'description' => request('description')
            ]);

        if ($modifyData) {
            return response()->json([ 'Status' => '200', 'Content' => '修改角色成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MR101', 'Content' => '修改角色失败' ], self::$successStatus);
        }
    }

    /**
     * 查询角色的菜单权限
    */
    public function selectRoleAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'SRA100', 'Content' => '参数缺失' ], self::$successStatus);
        }

        // 查询角色的菜单权限
        $menuID = DB::table('permission_role')
            ->where([
                'role_id' => request('id')
            ])
            ->pluck('permission_id');

        // 查询菜单信息
        $menu = DB::table('permission')
            ->where([
                'identity' => 1 // 管理人权限
            ])
            ->select([
                'id',
                'fid',
                'is_menu',
                'display_name AS label',
                'name'
            ])
            ->get();

        $menu = self::formatterMenu($menu, $menuID);
        
        return response()->json([ 'Status' => '200', 'Content' => $menu [0], 'Checked' => $menu [1] ], self::$successStatus);
    }

    /**
     * 格式化菜单格式
    */
    private static function formatterMenu($menu, $menuID)
    {
        if (empty($menu)) {
            return false;
        }

        $menuID = object2array($menuID);
        $menu = object2array($menu);

        foreach ($menu as $key => $value) {
            if ($value ['is_menu'] == 1) {
                $menu [$key] ['label'] = '📁' . $value ['label'];
            } else {
                $menu [$key] ['label'] = '🔐' . $value ['label'];
            }

            if ($value ['fid'] == 0 && $value ['is_menu'] == 1) {
                $tree [$value['id']] = $value;
                $tree [$value['id']] ['children'] = array();

                foreach($menuID as $k => $val) {
                    if ($val == $value ['id'] && $value ['name'] != 'dashboard') {
                        array_splice($menuID, $k, 1);
                    }
                }
            }
        }
        
        foreach ($menu as $key => $value) {
            if ($value['fid'] != 0 && $value ['is_menu'] == 1) {
                $tree [$value ['fid']]['children'][] = $value;
            }
        }

        foreach ($tree as $value) {
            $treeRow [] = $value;
        }

        foreach ($treeRow as $key => $value) {
            if ($value ['children']) {
                foreach ($value ['children'] as $k => $val) {
                    foreach ($menu as $kk => $v) {
                        if ($v ['fid'] == $val ['id'] && $v ['is_menu'] == 0) {
                            $treeRow [$key] ['children'] [$k] ['children'] [] = $v;
                        }
                    }
                }
            }
        }

        $arr [0] = $treeRow;
        $arr [1] = $menuID;

        return $arr;
    }

    /**
     * 修改角色的权限
    */
    public function roleAuthModify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'rule' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'RAM100', 'Content' => '参数缺失' ], self::$successStatus);
        }

        // 查询子菜单的上级菜单
        $findRule = DB::table('permission')
            ->whereIn('id', request('rule'))
            ->pluck('fid');
        
        $ruleArr = array_unique(array_merge(request('rule'), object2array($findRule)));

        $key = array_search(0, $ruleArr);
        if ($key !== false) {
            array_splice($ruleArr, $key, 1);
        }
        
        $rule = [];

        foreach ($ruleArr as $key => $value) {
            $rule[$key]['permission_id'] = $value;
            $rule[$key]['role_id'] = request('id');
        }
        
        DB::beginTransaction();

        // 删除用户老权限然后进行新权限增加
        $deleteRole = DB::table('permission_role')
            ->where([
                'role_id' => request('id')
            ])
            ->delete();

        if ($deleteRole === false) {
            DB::rollback();

            return response()->json([ 'Status' => 'RAM101', 'Content' => '修改菜单和权限失败' ], self::$successStatus);
        }

        // 插入新的权限
        $insertData = DB::table('permission_role')
            ->insert($rule);

        if ($insertData) {
            DB::commit();

            return response()->json([ 'Status' => '200', 'Content' => '修改成功' ], self::$successStatus);
        } else {
            DB::rollback();

            return response()->json([ 'Status' => 'RAM102', 'Content' => '修改失败' ], self::$successStatus);
        }
    }

    /**
     * 获取所有子商户用户列表
    */
    public function selectUserList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');

        $selectArray = array(
            'a.user_name' => request('user_name'),
            'c.id' => request('role_id')
        );

        $Rows = DB::table('zoro_user_info AS a')
            ->leftJoin('role_user AS b', 'b.user_id', '=', 'a.id')
            ->leftJoin('role AS c', 'b.role_id', '=', 'c.id')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereIn('a.roles', [2, 3])
            ->orderBy('a.id', 'desc')
            ->select([
                'a.id',
                'a.user_name',
                'a.user_no',
                'a.email',
                'a.roles',
                'b.role_id',
                'c.display_name'
            ])
            ->paginate($limit);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 获取成员可更改角色id列表
    */
    public function selectRoleId(Request $request)
    {
        $Rows = DB::table('role')
            ->select([
                'id AS value',
                'display_name AS label'
            ])
            ->where([
                'type' => 1 // 管理员角色
            ])
            ->orderBy('id', 'desc')
            ->get();

        if ($Rows) {
            return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);            
        } else {
            return response()->json([ 'Status' => 'SRI101', 'Content' => '查询角色失败' ], self::$successStatus);        
        }
    }

    /**
     * 修改用户的角色权限
    */
    public function userRoleAuthModify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'role_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'URAM100', 'Content' => '参数缺失' ], self::$successStatus);
        }

        DB::beginTransaction();

        // 修改或插入当前用户的角色信息
        if (!empty(request('old_role'))) {
            $updateRole = DB::table('role_user')
                ->where([
                    'user_id' => request('id')
                ])
                ->update([
                    'role_id' => request('role_id')
                ]);
        } else { // 写入角色表
            $updateRole = DB::table('role_user')
                ->insertGetId([
                    'user_id' => request('id'),
                    'role_id' => request('role_id')
                ]);
        }
        
        if ($updateRole === false) {
            DB::rollback();

            return response()->json([ 'Status' => 'URAM101', 'Content' => '修改用户信息失败' ], self::$successStatus);
        }

        // 当用户之前有角色组修改老组的用户成员
        if (!empty(request('old_role'))) {
            $roleOldUser = DB::table('role_user AS a')
                ->leftJoin('zoro_user_info AS b', 'a.user_id', '=', 'b.id')
                ->where([
                    'a.role_id' => request('old_role')
                ])
                ->pluck('b.user_name');

            $roleOldUser = implode(',', object2array($roleOldUser));

            $updateRoleOldData = DB::table('role')
                ->where([
                    'id' => request('old_role')
                ])
                ->update([
                    'user_id' => $roleOldUser
                ]);

            if (!$updateRoleOldData) {
                DB::rollback();

                return response()->json([ 'Status' => 'URAM103', 'Content' => '修改用户老角色失败' ], self::$successStatus);
            }
        }

        // 更新用户当前角色的角色信息
        $roleUser = DB::table('role_user AS a')
            ->leftJoin('zoro_user_info AS b', 'a.user_id', '=', 'b.id')
            ->where([
                'a.role_id' => request('role_id')
            ])
            ->pluck('b.user_name');

        $roleUser = implode(',', object2array($roleUser));

        $updateRoleData = DB::table('role')
            ->where([
                'id' => request('role_id')
            ])
            ->update([
                'user_id' => $roleUser
            ]);

        if (!$updateRoleData) {
            DB::rollback();

            return response()->json([ 'Status' => 'URAM102', 'Content' => '修改角色信息失败' ], self::$successStatus);
        } else {
            DB::commit();

            return response()->json([ 'Status' => '200', 'Content' => '修改用户角色成功' ], self::$successStatus);
        }
    }

    /**
     * 创建新的子管理员用户
    */
    public function userDataCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'password' => 'required|min:6|max:32',
            'email' => 'required',
            'mobile' => 'required',
            'roles' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'UDC100', 'Content' => '参数缺失' ], self::$successStatus);
        }

        // 验证用户名是否唯一
        $userOne = User::where([
                'user_name' => request('user_name')
            ])
            ->count('id');

        if ($userOne > 0) {
            return response()->json([ 'Status' => 'UDC102', 'Content' => '用户名重复，请输入新的用户名' ], self::$successStatus);
        }

        DB::beginTransaction();

        // 创建新的用户
        $user = User::create([
            'user_name' => request('user_name'),
            'password' => bcrypt(request('password')),
            'roles' => 2,
            'create_time' => date('Y-m-d H:i:s'),
            'email' => request('email'),
            'mobile' => request('mobile')
        ]);
        
        if (!$user) {
            DB::rollback();

            return response()->json([ 'Status' => 'UDC103', 'Content' => '创建用户失败' ], self::$successStatus);
        }

        // 创建用户角色信息
        $roleData = DB::table('role_user')
            ->insert([
                'role_id' => request('roles'),
                'user_id' => $user->id
            ]);

        if ($roleData === false) {
            DB::rollback();
            
            return response()->json([ 'Status' => 'UDC104', 'Content' => '创建失败' ], self::$successStatus);
        }

        // 更新角色信息
        $roleUser = DB::table('role_user AS a')
            ->leftJoin('zoro_user_info AS b', 'a.user_id', '=', 'b.id')
            ->where([
                'a.role_id' => request('roles')
            ])
            ->pluck('b.user_name');

        $roleUser = implode(',', object2array($roleUser));

        $updateRoleOldData = DB::table('role')
            ->where([
                'id' => request('roles')
            ])
            ->update([
                'user_id' => $roleUser
            ]);

        if (!$updateRoleOldData) {
            DB::rollback();

            return response()->json([ 'Status' => 'UDC105', 'Content' => '修改角色失败' ], self::$successStatus);
        } else {
            DB::commit();

            return response()->json([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
        }
    }
}
