<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input style="width: 120px;" class="filter-item" placeholder="角色" v-model="listQuery.display_name"></el-input>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">搜索</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handleCreate" type="primary" icon="el-icon-edit">添加</el-button>
            
            <el-button class="filter-item" type="warning" @click='handleUser' icon="el-icon-edit">成员</el-button>
        </div>

        <el-table :key='tableUserKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%">
            <el-table-column align="center" label="角色">
                <template slot-scope="scope">
                    <span>{{scope.row.display_name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="名称">
                <template slot-scope="scope">
                    <span>{{scope.row.name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="成员">
                <template slot-scope="scope">
                    <span>{{scope.row.user_id}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="描述">
                <template slot-scope="scope">
                    <span>{{scope.row.description}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="创建时间">
                <template slot-scope="scope">
                    <span v-html="scope.row.created_at"></span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" class-name="small-padding fixed-width" width="300">
                <template slot-scope="scope">
                    <el-button type="primary" size="mini" @click='handleUpdate(scope.row)'>编辑</el-button>

                    <el-button type="primary" size="mini" @click='handleAuth(scope.row)'>权限</el-button>

                    <el-button type="danger" size="mini" @click='handleDelete(scope.row.id)'>删除</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10,20,30, 50]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>
        
        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible" @close="resetTemp">
            <el-form :rules="rules" ref="form" :model="temp" label-position="left" label-width="180px" style='width: 500px; margin-left:50px;'>
                <el-form-item label="角色" prop="display_name">
                    <el-input v-model="temp.display_name">
                    </el-input>
                </el-form-item>

                <el-form-item label="名称" prop="name">
                    <el-input v-model="temp.name">
                    </el-input>
                </el-form-item>


                <el-form-item label="角色介绍" prop="description">
                    <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 4}" v-model="temp.description">
                    </el-input>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button v-if="dialogStatus=='create'" type="primary" @click="createRole" :loading="tempCreateLoading">{{$t('table.confirm')}}</el-button>
                <el-button v-else type="primary" @click="updateRole" :loading="tempUpdateLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>

        <el-dialog title="权限" :visible.sync="dialogAuthFormVisible">
            <el-tree
                :data="authData"
                show-checkbox
                node-key="id"
                ref="tree"
                default-expand-all
                :default-checked-keys="tempCheck"
                :props="defaultProps" 
                :loading="tempMenuTreeLoading">
            </el-tree>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogAuthFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button type="primary" @click="updateRoleAuth" :loading="tempUpdateAuthLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>

        <el-dialog title="子商户列表" :visible.sync="dialogUserFormVisible" @close="handleRefreshList">
            <el-form :inline="true" :rules="rules" ref="dataForm" :model="temp">
                <el-form-item label="用户名">
                    <el-input style="width: 120px;" class="filter-item" placeholder="用户名" v-model="listUserQuery.user_name"></el-input>
                </el-form-item>

                <el-form-item label="角色">
                    <el-select v-model="listUserQuery.role_id" filterable placeholder="请选择" :loading="tempUpdateListLoading">
                        <el-option
                            v-for="item in tempRoleInfoList"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>
                
                <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleUserFilter">搜索</el-button>

                <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleUserAdd">添加</el-button>
            </el-form>

            <el-table :key='tableKey' :data="listUser" v-loading="listUserLoading" element-loading-text="给我一点时间" border fit highlight-current-row
                style="width: 100%" ref="multipleTable">

                <el-table-column align="center" label="用户名">
                    <template slot-scope="scope">
                        <span>{{scope.row.user_name}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="类型">
                    <template slot-scope="scope">
                        <span>子商户</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="邮箱">
                    <template slot-scope="scope">
                        <span>{{scope.row.email}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="当前角色">
                    <template slot-scope="scope">
                        <template v-if="scope.row.edit">
                            <el-select v-model="tempUpdateUser.role_id" filterable placeholder="请选择" :loading="tempUpdateListLoading">
                                <el-option
                                    v-for="item in tempRoleInfo"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </template>
                        <span v-else>
                            <span>{{scope.row.display_name}}</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="操作">
                    <template slot-scope="scope">
                        <el-button v-if="scope.row.edit" type="success" @click="confirmEdit(scope.row)" icon="el-icon-circle-check-outline" :loading="tempUpdateUserAuthLoading">确认</el-button>
                        <el-button v-if="scope.row.edit" type="warning" @click="cancelEdit(scope.row)" icon="el-icon-circle-check-outline">取消</el-button>
                        <el-button v-else type="primary" @click='confirmEditClick(scope.row)' icon="el-icon-edit">编辑</el-button>
                    </template>
                </el-table-column>

            </el-table>

            <div class="pagination-container">
                <el-pagination background @size-change="handleUserSizeChange" @current-change="handleUserCurrentChange" :current-page.sync="listUserQuery.page"
                    :page-sizes="[10,20,30, 50]" :page-size="listUserQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="userTotal">
                </el-pagination>
            </div>
        </el-dialog>

        <el-dialog title="添加子商户" :visible.sync="dialogAddFormVisible">
            <el-form :rules="rulesAdd" ref="formAdd" :model="tempAdd" label-position="left" label-width="180px" style='width: 500px; margin-left:50px;'>
                <el-form-item label="用户名" prop="user_name">
                    <el-input v-model="tempAdd.user_name">
                    </el-input>
                </el-form-item>

                <el-form-item label="密码" prop="password">
                    <el-input v-model="tempAdd.password">
                    </el-input>
                </el-form-item>
                
                <el-form-item label="邮箱" prop="email">
                    <el-input v-model="tempAdd.email">
                    </el-input>
                </el-form-item>

                <el-form-item label="手机" prop="mobile">
                    <el-input v-model="tempAdd.mobile">
                    </el-input>
                </el-form-item>

                <el-form-item label="角色" prop="roles">
                    <el-select v-model="tempAdd.roles" filterable placeholder="请选择" :loading="tempUpdateListLoading">
                        <el-option
                            v-for="item in tempRoleInfo"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogAddFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button  type="primary" @click="createUser" :loading="tempCreateUserLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>

    </div>
</template>

<script>
import {
    fetchList,
    createRoleData,
    deleteRoleInfo,
    modifyRoleData,
    roleAuthSelect,
    modifyRoleAuth,
    userListSelect,
    roleIdSelect,
    modifyUserRoleAuth,
    createUserData
} from '@/merchant/api/authRole'
import waves from '@/merchant/directive/waves' // 水波纹指令

export default {
    name: 'complexTable',
    directives: {
        waves
    },
    data () {
        const validatePassword = (rule, value, callback) => {
            if (value.length < 6 || value.length > 32) {
                callback(new Error('密码不能少于6位且不能大于32位'))
            } else {
                callback()
            }
        }

        return {
            tableKey: 0,
            tableUserKey: 0,
            list: null,
            total: null,
            listLoading: true,
            listQuery: {
                page: 1,
                limit: 10,
                display_name: undefined
            },
            listUserQuery: {
                page: 1,
                limit: 10,
                user_name: undefined
            },
            userTotal: null,
            textMap: {
                'update': '修改角色',
                'create': '添加角色'
            },
            dialogFormVisible: false,
            dialogStatus: '',
            temp: {
                id: '',
                name: '',
                display_name: '',
                description: ''
            },
            rules: {
                name: [{ required: true, message: '请输入名称', trigger: 'change' }],
                display_name: [{ required: true, message: '请输入角色', trigger: 'change' }],
                description: [{ required: true, message: '请输入角色介绍', trigger: 'change' }]
            },
            tempAdd: {
                user_name: '',
                password: '',
                roles: '',
                email: '',
                mobile: ''
            },
            rulesAdd: {
                user_name: [{ required: true, message: '请输入用户名', trigger: 'change' }],
                password: [{ required: true, message: '请输入正确的密码', trigger: 'change', validator: validatePassword }],
                email: [{ required: true, message: '请填写邮箱', trigger: 'blur' }, { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur,change' }],
                mobile: [{ required: true, message: '请填写手机号', trigger: 'blur' }],
                roles: [{ required: true, message: '请选择角色', trigger: 'change' }]
            },
            tempAuth: {
                id: ''
            },
            authData: null,
            defaultProps: {
                children: 'children',
                label: 'label'
            },
            tempCheck: [],
            tempUpdateRole: {
                id: '',
                rule: ''
            },
            listUser: null,
            tempUpdateUser: {
                role_id: ''
            },
            tempRoleInfo: [],
            tempRoleInfoList: [],
            tempCreateLoading: false,
            tempUpdateLoading: false,
            tempDeleteLoading: false,
            dialogAuthFormVisible: false,
            tempUpdateAuthLoading: false,
            tempMenuTreeLoading: false,
            dialogUserFormVisible: false,
            listUserLoading: false,
            tempUpdateUserRoleLoading: false,
            tempUpdateListLoading: false,
            tempUpdateUserAuthLoading: false,
            dialogAddFormVisible: false,
            tempCreateUserLoading: false
        }
    },
    created () {
        this.getList()
    },
    methods: {
        getList () {
            this.listLoading = true

            fetchList(this.listQuery).then((response) => {
                this.listLoading = false

                if (response.data.Status !== '200') {
                    this.$message({
                        message: '信息获取错误请稍后再试',
                        type: 'error'
                    })
                } else {
                    this.list = response.data.Content.data
                    this.total = response.data.Content.total
                }
            })
        },
        handleFilter () {
            this.listQuery.page = 1
            this.getList()
        },
        handleSizeChange (val) {
            this.listQuery.limit = val
            this.getList()
        },
        handleCurrentChange (val) {
            this.listQuery.page = val
            this.getList()
        },
        handleCreate () {
            this.dialogFormVisible = true
            this.dialogStatus = 'create'
        },
        createRole () {
            this.$refs['form'].validate((valid) => {
                if (valid) {
                    this.tempCreateLoading = true

                    createRoleData(this.temp).then((response) => {
                        this.tempCreateLoading = false

                        if (response.data.Status === '200') {
                            this.getList()
                            this.resetTemp()
                            this.dialogFormVisible = false

                            this.$notify({
                                title: '成功',
                                message: response.data.Content,
                                type: 'success',
                                duration: 2000
                            })
                        } else {
                            this.$notify({
                                title: '失败',
                                message: response.data.Content,
                                type: 'error',
                                duration: 2000
                            })
                        }
                    }).catch(() => {
                        this.tempCreateLoading = false
                    })
                }
            })
        },
        resetTemp () {
            this.temp.id = ''
            this.temp.name = ''
            this.temp.display_name = ''
            this.temp.description = ''

            this.$nextTick(() => {
                this.$refs['form'].clearValidate()
            })
        },
        handleUpdate (row) {
            this.dialogStatus = 'update'
            this.dialogFormVisible = true

            this.temp.id = row.id
            this.temp.name = row.name
            this.temp.display_name = row.display_name
            this.temp.description = row.description
        },
        updateRole () {
            this.tempUpdateLoading = true

            modifyRoleData(this.temp).then((response) => {
                this.tempUpdateLoading = false

                if (response.data.Status === '200') {
                    this.getList()
                    this.resetTemp()
                    this.dialogFormVisible = false

                    this.$notify({
                        title: '成功',
                        message: response.data.Content,
                        type: 'success',
                        duration: 2000
                    })
                } else {
                    this.$notify({
                        title: '失败',
                        message: response.data.Content,
                        type: 'error',
                        duration: 2000
                    })
                }
            })
        },
        handleDelete (ID) {
            this.$confirm('是否确定删除？', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                const data = {
                    id: ID
                }

                this.tempDeleteLoading = true

                deleteRoleInfo(data).then((response) => {
                    this.tempDeleteLoading = false

                    if (response.data.Status === '200') {
                        this.getList()

                        this.$notify({
                            title: '成功',
                            message: response.data.Content,
                            type: 'success',
                            duration: 2000
                        })
                    } else {
                        this.$notify({
                            title: '失败',
                            message: response.data.Content,
                            type: 'error',
                            duration: 2000
                        })
                    }
                })
            }).catch(() => {
                console.log('取消删除')
            })
        },
        handleAuth (row) {
            this.dialogAuthFormVisible = true
            this.tempMenuTreeLoading = true
            this.tempUpdateRole.id = row.id

            const data = {
                id: row.id,
                menu_id: row.menu_id
            }

            roleAuthSelect(data).then((response) => {
                this.tempMenuTreeLoading = false
                this.authData = []

                if (response.data.Status === '200') {
                    this.authData = response.data.Content
                    this.tempCheck = response.data.Checked
                } else {
                    this.$notify({
                        title: '失败',
                        message: '获取信息失败',
                        type: 'error',
                        duration: 2000
                    })
                }
            })
        },
        updateRoleAuth () {
            this.tempUpdateRole.rule = this.$refs.tree.getCheckedKeys()

            this.$confirm('是否确定更新权限信息？', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                this.tempUpdateAuthLoading = true

                modifyRoleAuth(this.tempUpdateRole).then((response) => {
                    this.tempUpdateAuthLoading = false

                    if (response.data.Status === '200') {
                        this.getList()
                        this.dialogAuthFormVisible = false

                        this.$notify({
                            title: '成功',
                            message: response.data.Content,
                            type: 'success',
                            duration: 2000
                        })
                    } else {
                        this.$notify({
                            title: '失败',
                            message: response.data.Content,
                            type: 'error',
                            duration: 2000
                        })
                    }
                })
            }).catch(() => {
                console.log('取消删除')
            })
        },
        handleUser (row) {
            this.dialogUserFormVisible = true

            this.getUserList()
            this.selectRoleListInfo(2)
        },
        getUserList () {
            this.listUserLoading = true

            userListSelect(this.listUserQuery).then((response) => {
                this.listUserLoading = false

                if (response.data.Status === '200') {
                    this.listUser = response.data.Content.data.map((v) => {
                        this.$set(v, 'edit', false)

                        return v
                    })

                    this.listUser = response.data.Content.data
                    this.userTotal = response.data.Content.total
                } else {
                    this.$notify({
                        title: '失败',
                        message: response.data.Content,
                        type: 'error',
                        duration: 2000
                    })
                }
            })
        },
        handleUserFilter () {
            this.listUserQuery.page = 1
            this.getUserList()
        },
        handleUserSizeChange (val) {
            this.listUserQuery.limit = val
            this.getUserList()
        },
        handleUserCurrentChange (val) {
            this.listUserQuery.page = val
            this.getUserList()
        },
        confirmEditClick (row) {
            this.listUser = this.listUser.map((v) => {
                v.edit = false

                return v
            })

            this.tempUpdateUser.role_id = row.role_id
            row.edit = true
            this.selectRoleListInfo(1)
        },
        selectRoleListInfo (type) {
            if (this.tempRoleInfo.length === 0 || this.tempRoleInfoList.length === 0) {
                this.tempUpdateListLoading = true

                roleIdSelect().then((response) => {
                    this.tempUpdateListLoading = false

                    if (response.data.Status === '200') {
                        if (type === 1) {
                            this.tempRoleInfo = response.data.Content
                        } else {
                            this.tempRoleInfoList = response.data.Content
                            this.tempRoleInfoList.push({'label': '全部', 'value': undefined})
                        }
                    } else {
                        this.$notify({
                            title: '失败',
                            message: response.data.Content,
                            type: 'error',
                            duration: 2000
                        })
                    }
                })
            }
        },
        confirmEdit (row) {
            if (this.tempUpdateUser.role_id === '' || this.tempUpdateUser.role_id === null) {
                this.$notify({
                    title: '提示',
                    message: '用户角色不能为空',
                    type: 'error',
                    duration: 2000
                })
            } else {
                this.$confirm('是否确定修改当前用户角色权限？', '提示', {
                    confirmButtonText: '确定',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.tempUpdateUserAuthLoading = true

                    const data = {
                        id: row.id,
                        role_id: this.tempUpdateUser.role_id,
                        old_role: row.role_id
                    }

                    modifyUserRoleAuth(data).then((response) => {
                        this.tempUpdateUserAuthLoading = false

                        if (response.data.Status === '200') {
                            this.tempUpdateUser.role_id = ''
                            row.edit = false
                            this.getUserList()

                            this.$notify({
                                title: '成功',
                                message: response.data.Content,
                                type: 'success',
                                duration: 2000
                            })
                        } else {
                            this.$notify({
                                title: '失败',
                                message: response.data.Content,
                                type: 'error',
                                duration: 2000
                            })
                        }
                    })
                }).catch(() => {
                    console.log('取消删除')
                })
            }
        },
        cancelEdit (row) {
            row.edit = false
        },
        handleRefreshList () {
            this.getList()
        },
        handleUserAdd () {
            this.dialogAddFormVisible = true
            this.selectRoleListInfo(1)
        },
        resetTempAdd () {
            this.tempAdd.user_name = ''
            this.tempAdd.password = ''
            this.tempAdd.roles = ''
            this.tempAdd.email = ''
            this.tempAdd.mobile = ''
        },
        createUser () {
            this.$refs['formAdd'].validate((valid) => {
                if (valid) {
                    this.tempCreateUserLoading = true

                    createUserData(this.tempAdd).then((response) => {
                        this.tempCreateUserLoading = false

                        if (response.data.Status === '200') {
                            this.getUserList()
                            this.selectRoleListInfo(2)
                            this.resetTempAdd()
                            this.dialogAddFormVisible = false

                            this.$notify({
                                title: '成功',
                                message: response.data.Content,
                                type: 'success',
                                duration: 2000
                            })
                        } else {
                            this.$notify({
                                title: '失败',
                                message: response.data.Content,
                                type: 'error',
                                duration: 2000
                            })
                        }
                    }).catch(() => {
                        this.tempCreateLoading = false
                    })
                }
            })
        }
    }
}
</script>
