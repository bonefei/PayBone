<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" :placeholder="$t('table.username')" v-model="listQuery.username"></el-input>

            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" :placeholder="$t('table.merchantNumber')" v-model="listQuery.merchantNumber"></el-input>

            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" :placeholder="$t('table.name')" v-model="listQuery.name"></el-input>

            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" :placeholder="$t('table.mobile')" v-model="listQuery.mobile"></el-input>

            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" :placeholder="$t('table.email')" v-model="listQuery.email"></el-input>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">{{$t('table.search')}}</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handleCreate" type="primary" icon="el-icon-edit">{{$t('table.add')}}</el-button>

            <el-button class="filter-item" type="primary" :loading="downloadLoading" v-waves icon="el-icon-download" @click="handleDownload">{{$t('table.export')}}</el-button>
        </div>

        <el-table :key='tableKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%">
            <el-table-column align="center" :label="$t('table.id')" width="65">
                <template slot-scope="scope">
                    <span>{{scope.row.id}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.date')">
                <template slot-scope="scope">
                    <span>{{scope.row.create_time}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户名">
                <template slot-scope="scope">
                    <span>{{scope.row.user_name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.name')">
                <template slot-scope="scope">
                    <span>{{scope.row.name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.mobile')">
                <template slot-scope="scope">
                    <span>{{scope.row.mobile}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.email')">
                <template slot-scope="scope">
                    <span>{{scope.row.email}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.baseCurrency')">
                <template slot-scope="scope">
                    <span v-if="scope.row.baseCurrency == 1">人民币</span>
                    <span v-if="scope.row.baseCurrency == 2">美元</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" width="380px" class-name="small-padding fixed-width">
                <template slot-scope="scope">
                    <el-button type="primary" size="mini" @click="handleUpdate(scope.row)">{{$t('table.edit')}}</el-button>
                    <el-button size="mini" @click="handleChangePass(scope.row)">{{$t('table.password')}}</el-button>
                    <el-button type="primary" size="mini" @click="handleMerchant(scope.row)">{{$t('table.merchant')}}</el-button>
                    <el-button type="primary" size="mini" @click="handleAddMerchant(scope.row)">{{$t('table.add')}}</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10,20,30, 50]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>

        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible">
            <el-form :rules="rules" ref="dataForm" :model="temp" label-position="left" label-width="100px" style='width: 400px; margin-left:50px;'>
                <el-form-item :label="$t('table.date')" prop="createTime" v-if="dialogStatus=='create'">
                    <el-date-picker v-model="temp.create_time" type="datetime" format="yyyy-MM-dd HH:mm:ss" value-format="yyyy-MM-dd HH:mm:ss">
                    </el-date-picker>
                </el-form-item>

                <el-form-item :label="$t('table.username')" prop="user_name">
                    <el-input v-model="temp.user_name"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.merchantName')" prop="payname">
                    <el-input v-model="temp.payname"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.name')" prop="name">
                    <el-input v-model="temp.name"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.mobile')" prop="mobile">
                    <el-input v-model="temp.mobile"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.email')" prop="email">
                    <el-input v-model="temp.email"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.baseCurrency')" prop="baseCurrency">
                    <el-select v-model="temp.baseCurrency" filterable placeholder="请选择">
                        <el-option
                            v-for="item in tempBaseCurrencyOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value" 
                            :disabled="item.disabled">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item :label="$t('table.password')" v-show="dialogStatus=='create'" prop="password">
                    <el-input v-model="temp.password"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.payPassword')" v-show="dialogStatus=='create'" prop="payPassword">
                    <el-input v-model="temp.payPassword"></el-input>
                </el-form-item>

                <el-form-item :label="$t('table.remark')">
                    <el-input type="textarea" :autosize="{ minRows: 2, maxRows: 4}" v-model="temp.introduction">
                    </el-input>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button v-if="dialogStatus=='create'" type="primary" @click="createData" :loading="tempCreateLoading">{{$t('table.confirm')}}</el-button>
                <el-button v-else type="primary" @click="updateData" :loading="tempUpdateLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>


        <el-dialog title="重置商户密码" :visible.sync="dialogPassFormVisible">
            <el-form :rules="modifyPassRules" ref="modifyPassForm" :model="tempPass" label-position="left" label-width="170px" style='width: 400px; margin-left:50px;'>
                <el-form-item :label="$t('table.newPassword')" prop="newPassword">
                    <el-input :type="passwordType" v-model="tempPass.newPassword"></el-input>

                    <span class="show-pwd" @click="showPwd">
                        <svg-icon icon-class="eye" />
                    </span>
                </el-form-item>

                <el-form-item :label="$t('table.newPayPassword')" prop="newPayPassword">
                    <el-input :type="passwordType" v-model="tempPass.newPayPassword"></el-input>

                    <span class="show-pwd" @click="showPwd">
                        <svg-icon icon-class="eye" />
                    </span>
                </el-form-item>

            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogPassFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button type="primary" @click="modifyPassword" :loading="tempModifyPasswordLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>

        <el-dialog title="商户信息" :visible.sync="dialogMerchantVisible" width="80%" @close='closeMerchantDialog'>
            <el-form :inline="true" :rules="rules" ref="dataForm" :model="temp">
                <el-form-item label="商户号">
                    <el-input @keyup.enter.native="handleMerchantFilter" style="width: 120px;" class="filter-item" :placeholder="$t('table.merchantNumber')" v-model="listMerchantQuery.username"></el-input>
                </el-form-item>

                <span>
                    <el-button type="primary" @click="handleMerchantSearch" icon="el-icon-search">搜索</el-button>
                </span>
            </el-form>

            <el-table :key='tableDialogKey' :data="listMerchant" v-loading="listMerchantLoading" element-loading-text="给我一点时间" border fit 
            highlight-current-row style="width: 100%">
                <el-table-column align="center" label="用户名">
                    <template slot-scope="scope">
                        <span>{{scope.row.user_name}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="商户号">
                    <template slot-scope="scope">
                        <span>{{scope.row.user_no}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="创建时间">
                    <template slot-scope="scope">
                        <span>{{scope.row.create_time}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="余额">
                    <template slot-scope="scope">
                        <span>{{scope.row.balance}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="状态">
                    <template slot-scope="scope">
                        <span v-if="scope.row.status == 1">启用</span>
                        <span v-else style="color: red">禁用</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="操作">
                    <template slot-scope="scope">
                        <el-button v-if="scope.row.status!='published'" size="mini" type="success" @click="handleModifyStatus(scope.row)" :loading="bankLoading">证书</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination-container">
                <el-pagination background @size-change="handleMerchantSizeChange" @current-change="handleMerchantCurrentChange" :current-page.sync="listMerchantQuery.page"
                    :page-sizes="[10,20,30, 50]" :page-size="listMerchantQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="merchantTotal">
                </el-pagination>
            </div>
        </el-dialog>

        <el-dialog title="添加商户" :visible.sync="dialogAddMerchantVisible">
            <el-form ref="addMerchantForm" :model="tempMerchant" label-position="left" label-width="170px" style='width: 400px; margin-left:50px;'>
                <el-form-item label="商户号">
                    <el-input type="text" v-model="tempMerchant.merchant" placeholder="系统随机生成" disabled></el-input>
                </el-form-item>

                <el-form-item label="商户备注" prop="remark">
                    <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 4}" v-model="tempMerchant.remark">
                    </el-input>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogAddMerchantVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button type="primary" @click="handleMerchantAdd" :loading="tempAddMerchantLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import { fetchList, createMerchant, updateMerchant, merchantModifyPassword, userMerchantSelect, createMerchantInfo } from '@/admin/api/merchant'
import waves from '@/admin/directive/waves' // 水波纹指令

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
            tableDialogKey: 0,
            list: null,
            total: null,
            listLoading: true,
            listQuery: {
                page: 1,
                limit: 10,
                importance: undefined,
                username: undefined,
                merchantNumber: undefined,
                name: undefined,
                mobile: undefined,
                email: undefined
            },
            props: {
                value: 'label',
                children: 'children'
            },
            tempBaseCurrencyOption: [{
                value: 1,
                label: '人名币'
            }, {
                value: 2,
                label: '美元'
            }],
            temp: {
                create_time: new Date(),
                user_name: '',
                payname: '',
                name: '',
                mobile: '',
                email: '',
                baseCurrency: '',
                password: '',
                payPassword: '',
                introduction: ''
            },
            tempPass: {
                newPassword: '',
                newPayPassword: '',
                modifyMerchant: ''
            },
            dialogFormVisible: false,
            dialogStatus: '',
            textMap: {
                update: '修改商户',
                create: '添加商户'
            },
            rules: {
                create_time: [{ required: true, message: '请填写创建时间', trigger: 'change' }],
                user_name: [{ required: true, message: '请填写用户名(可用于登录使用)', trigger: 'blur' }],
                payname: [{ required: true, message: '请填写商户名', trigger: 'blur' }],
                name: [{ required: true, message: '请填写姓名', trigger: 'blur' }],
                mobile: [{ required: true, message: '请填写手机号', trigger: 'blur' }],
                email: [{ required: true, message: '请填写邮箱', trigger: 'blur' }, { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur,change' }],
                baseCurrency: [{ required: true, message: '请选择基础货币', trigger: 'blur' }],
                password: [{ required: true, message: '请填写密码', trigger: 'blur' }],
                payPassword: [{ required: true, message: '请填写支付密码', trigger: 'blur' }]
            },
            modifyPassRules: {
                newPassword: [{ required: true, message: '请填写新密码', trigger: 'blur', validator: validatePassword }],
                newPayPassword: [{ required: true, message: '请填写新支付密码', trigger: 'blur', validator: validatePassword }]
            },
            passwordType: 'password',
            tempCreateLoading: false,
            tempUpdateLoading: false,
            downloadLoading: false,
            bankLoading: false,
            dialogPassFormVisible: false,
            tempModifyPasswordLoading: false,
            dialogMerchantVisible: false,
            listMerchantQuery: {
                page: 1,
                limit: 10,
                username: undefined,
                id: ''
            },
            listMerchant: [],
            listMerchantLoading: false,
            merchantTotal: null,
            dialogAddMerchantVisible: false,
            tempMerchant: {
                id: '',
                remark: ''
            },
            tempAddMerchantLoading: false,
            merchantRand: '系统随机生成'
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

                if (response.data.Status !== 200) {
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
        handleModifyStatus (row, status) {
            window.open(`/admin/v1/merchantDownload?id=${row.id}&user_id=${row.user_id}`)
        },
        resetTemp () {
            this.temp = {
                create_time: new Date(),
                user_name: '',
                payname: '',
                name: '',
                mobile: '',
                email: '',
                password: '',
                introduction: ''
            }
        },
        handleCreate () {
            this.resetTemp()
            this.dialogStatus = 'create'
            this.dialogFormVisible = true
            this.rules.password[0].required = true
            this.rules.payPassword[0].required = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        createData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempCreateLoading = true

                    createMerchant(this.temp).then((response) => {
                        this.tempCreateLoading = false

                        if (response.data.Status === '200') {
                            this.getList()
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
        handleUpdate (row) {
            this.temp = Object.assign({}, row) // copy obj
            this.dialogStatus = 'update'
            this.dialogFormVisible = true
            this.rules.password[0].required = false
            this.rules.payPassword[0].required = false
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        updateData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempUpdateLoading = true
                    const tempData = Object.assign({}, this.temp)
                    updateMerchant(tempData).then((response) => {
                        this.tempUpdateLoading = false
                        if (response.data.Status === '200') {
                            for (const v of this.list) {
                                if (v.id === this.temp.id) {
                                    const index = this.list.indexOf(v)
                                    this.list.splice(index, 1, this.temp)
                                    break
                                }
                            }
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
                }
            })
        },
        handleDownload () {
            this.downloadLoading = true

            const { exportJsonToExcel } = require('@/admin/vendor/Export2Excel')

            const tHeader = ['编号', '时间', '商户号', '姓名', '手机号', '邮箱', '结算货币']
            const filterVal = ['id', 'create_time', 'user_name', 'name', 'mobile', 'email', 'baseCurrency']
            const data = this.formatJson(filterVal, this.list)
            exportJsonToExcel(tHeader, data, '商户列表')

            setTimeout(() => {
                this.downloadLoading = false
            }, 2000)
        },
        formatJson (filterVal, jsonData) {
            return jsonData.map((v) => filterVal.map((j) => {
                if (j === 'baseCurrency') {
                    switch (v[j]) {
                    case 1:
                        return '人民币'
                    case 2:
                        return '美元'
                    }
                } else {
                    return v[j]
                }
            }))
        },
        showPwd () {
            if (this.passwordType === 'password') {
                this.passwordType = ''
            } else {
                this.passwordType = 'password'
            }
        },
        handleChangePass (row) {
            this.tempPass.modifyMerchant = row.id
            this.dialogPassFormVisible = true
        },
        modifyPassword () {
            this.$refs['modifyPassForm'].validate((valid) => {
                if (valid) {
                    this.tempModifyPasswordLoading = true

                    merchantModifyPassword(this.tempPass).then((response) => {
                        this.tempModifyPasswordLoading = false

                        if (response.data.Status === '200') {
                            this.dialogPassFormVisible = false
                            this.tempPass.modifyMerchant = ''

                            this.getList()
                        } else {
                            this.$notify({
                                title: '错误',
                                message: response.data.Content,
                                type: 'error',
                                duration: 2000
                            })
                        }
                    })
                }
            })
        },
        handleMerchant (row) {
            this.dialogMerchantVisible = true

            if (row.id) {
                this.listMerchantLoading = true

                this.listMerchantQuery.id = row.id

                this.selectMerchantlist()
            }
        },
        selectMerchantlist () {
            this.listMerchantLoading = true

            userMerchantSelect(this.listMerchantQuery).then((response) => {
                this.listMerchantLoading = false

                if (response.data.Status === '200') {
                    this.listMerchant = response.data.Content.data
                    this.merchantTotal = response.data.Content.total
                } else {
                    this.$notify({
                        title: '错误',
                        message: response.data.Content,
                        type: 'error',
                        duration: 2000
                    })
                }
            })
        },
        handleMerchantSizeChange (val) {
            this.listMerchantQuery.limit = val
            this.selectMerchantlist()
        },
        handleMerchantCurrentChange (val) {
            this.listMerchantQuery.page = val
            this.selectMerchantlist()
        },
        handleMerchantFilter () {
            this.listMerchantQuery.page = 1
            this.selectMerchantlist()
        },
        handleMerchantSearch () {
            this.selectMerchantlist()
        },
        closeMerchantDialog () {
            this.listMerchantQuery.id = ''
        },
        handleAddMerchant (row) {
            this.dialogAddMerchantVisible = true

            this.tempMerchant.id = row.id
        },
        handleMerchantAdd () {
            this.tempAddMerchantLoading = true

            createMerchantInfo(this.tempMerchant).then((response) => {
                this.tempAddMerchantLoading = false

                if (response.data.Status === '200') {
                    this.dialogAddMerchantVisible = false
                    this.resetTempMerchant()
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
            }).catch(() => {
                this.tempAddMerchantLoading = false
            })
        },
        resetTempMerchant () {
            this.tempMerchant.id = ''
            this.tempMerchant.merchant = ''
            this.tempMerchant.remark = ''
        }
    }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
    $dark_gray: #889aa4;

    .show-pwd {
        position: absolute;
        right: 10px;
        top: 7px;
        font-size: 16px;
        color: $dark_gray;
        cursor: pointer;
        user-select: none;
    }
</style>
