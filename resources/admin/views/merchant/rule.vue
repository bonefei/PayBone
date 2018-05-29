<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input style="width: 120px;" class="filter-item" placeholder="用户名" v-model="listQuery.payUserName"></el-input>

            <el-input style="width: 120px;" class="filter-item" placeholder="商户号" v-model="listQuery.payWayCode"></el-input>

            <el-input style="width: 120px;" class="filter-item" placeholder="支付编号" v-model="listQuery.payProductName"></el-input>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">搜索</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handleCreate" type="primary" icon="el-icon-edit">添加</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handleDelete" type="danger" icon="el-icon-delete" :loading="handlerDeleteLoading">删除</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handlerForbidden" type="warning" icon="el-icon-warning" :loading="handlerForbiddenLoading">禁用/启用</el-button>
        </div>

        <el-table :key='tableKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%" @selection-change="listSelectionChange" ref="table">
            <el-table-column type="selection" width="55">
            </el-table-column>

            <el-table-column align="center" label="排序" width="65">
                <template slot-scope="scope">
                    <span>{{scope.row.order}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="用户">
                <template slot-scope="scope">
                    <span>{{scope.row.user_name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户号">
                <template slot-scope="scope">
                    <span>{{scope.row.payCode}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="支付编号">
                <template slot-scope="scope">
                    <span>{{scope.row.payName}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="路由规则" width="800">
                <template slot-scope="scope">
                    <span v-html="scope.row.comment"></span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="是否禁用">
                <template slot-scope="scope">
                    <span v-if="scope.row.disable == 0" style="color: red;">禁用</span>
                    <span v-else style="color: green;">启用</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" class-name="small-padding fixed-width" width="200">
                <template slot-scope="scope">
                    <el-button type="primary" @click='handleUpdate(scope.row)' icon="el-icon-edit">编辑</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10,20,30, 50]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>

        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible" width="80%" @close='closeDialog'>
            <el-form :inline="true" :rules="rules" ref="dataForm" :model="temp">
                <el-form-item label="用户名" prop="payUser">
                    <el-select v-model="temp.payUser" filterable placeholder="请选择" @change="payUserChange()" :loading="tempPayUserSelectLoading">
                        <el-option
                            v-for="item in tempPayUserOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="商户号" prop="payCode">
                    <el-select v-model="temp.payCode" filterable placeholder="请选择" @change="payCodeChange()" :loading="tempPayCodeSelectLoading">
                        <el-option
                            v-for="item in tempPayCodeOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="支付编号" prop="payName">
                    <el-select v-model="temp.payName" filterable placeholder="请选择" :loading="tempPayNameSelectLoading">
                        <el-option
                            v-for="item in tempPayNameSelect"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="支付排序" prop="payOrder">
                    <el-input type="number" min="0" v-model="temp.payOrder"></el-input>
                </el-form-item>

                <span v-if="dialogStatus=='create'">
                    <el-button type="primary" @click="handlerDialogCreate" icon="el-icon-edit">添加</el-button>
                    <el-button type="danger" @click="handlerDialogDelete" icon="el-icon-delete">删除</el-button>
                </span>
                <span v-else>
                    <el-button type="primary" @click="handlerDialogUpdateInsert" :loading="handlerDialogUpdateInsertLoading" icon="el-icon-edit">添加</el-button>
                    <el-button type="danger" @click="handlerDialogUpdateDelete" :loading="handlerDialogUpdateDeleteLoading" icon="el-icon-delete">删除</el-button>
                </span>
            </el-form>

            <el-table :key='tableDialogKey' :data="listDialog" v-loading="listDialogLoading" element-loading-text="给我一点时间" border fit 
            highlight-current-row style="width: 100%" @selection-change="listDialogSelectionChange" ref="dialogTable">
                <el-table-column type="selection" width="55">
                </el-table-column>

                <el-table-column align="center" label="默认参数">
                    <template slot-scope="scope">
                        <template v-if="scope.row.edit">
                            <el-select v-model="temp.payType" filterable placeholder="请选择" @change="payTypeChange()">
                                <el-option
                                    v-for="item in tempPayTypeSelect"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value" 
                                    :disabled="item.disabled">
                                </el-option>
                            </el-select>
                        </template>
                        <span v-else>
                            <span v-if="scope.row.payType == 1">单笔金额</span>
                            <span v-else-if="scope.row.payType == 2">当日总额</span>
                            <span v-else-if="scope.row.payType == 3">时间段</span>
                            <span v-else>代理</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="过滤器类型">
                    <template slot-scope="scope">
                        <template v-if="scope.row.edit">
                            <el-select v-model="temp.operator" filterable placeholder="请选择">
                                <el-option
                                    v-for="item in tempOperatorSelect"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </template>
                        <span v-else>
                            <span v-if="scope.row.operator == 1">=</span>
                            <span v-else-if="scope.row.operator == 2">&lt;&gt;</span>
                            <span v-else-if="scope.row.operator == 3">&lt;</span>
                            <span v-else-if="scope.row.operator == 4">&lt;=</span>
                            <span v-else-if="scope.row.operator == 5">&gt;</span>
                            <span v-else-if="scope.row.operator == 6">&gt;=</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="值">
                    <template slot-scope="scope">
                        <template v-if="scope.row.edit">
                            <el-input type="number" min="0" v-model="temp.value" v-show="temp.payType==1||temp.payType==2"></el-input>
                            <!-- <el-input v-model="temp.value" v-show="dialogInputStatus=='agent'"></el-input> -->
                            <el-time-picker v-model="temp.value" format="HH:mm:ss" value-format="HH:mm:ss" v-show="temp.payType==3"></el-time-picker>
                        </template>
                        <span v-else>{{scope.row.value}}</span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="连接符">
                    <template slot-scope="scope">
                        <template v-if="scope.row.edit">
                            <el-select v-model="temp.connector" filterable placeholder="请选择">
                                <el-option
                                    v-for="item in tempConnectorSelect"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </template>
                        <span v-else>
                            <span v-if="scope.row.connector == 1">或者</span>
                            <span v-if="scope.row.connector == 2">并且</span>
                            <span v-if="scope.row.connector == 3">空</span>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column align="center" label="操作" class-name="small-padding fixed-width" width="200"
                v-if="dialogStatus!='create'">
                    <template slot-scope="scope">
                        <el-button v-if="scope.row.edit" type="success" @click="confirmEdit(scope.row)" icon="el-icon-circle-check-outline" :loading="tempUpdateRuleLoading">确认</el-button>
                        <el-button v-if="scope.row.edit" type="warning" @click="cancelEdit(scope.row)" icon="el-icon-circle-check-outline">取消</el-button>
                        <el-button v-else type="primary" @click='confirmEditClick(scope.row)' icon="el-icon-edit">编辑</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">取消</el-button>
                <el-button type="primary" v-show="dialogStatus=='create'" @click="createData" :loading="tempCreateLoading">保存</el-button>
                <el-button type="primary" v-show="dialogStatus=='update'" @click="updateData" :loading="tempUpdateLoading">保存</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import {
    fetchList,
    fetchDialogList,
    selectPayCodeOption,
    selectPayNameOption,
    createMerchantRule,
    // createDialogMerchantRule,
    updateMerchantRule,
    updateMerchantDialogRule,
    deleteMerchantRule,
    deleteDialogMerchantRule,
    forbiddenMerchantRule,
    selectPayUserOption
} from '@/admin/api/merchantRule'
import waves from '@/admin/directive/waves' // 水波纹指令
import areaInfo from '@/admin/utils/area'

export default {
    name: 'complexTable',
    directives: {
        waves
    },
    data () {
        return {
            tableKey: 0,
            list: null,
            total: null,
            listLoading: true,
            listQuery: {
                page: 1,
                limit: 10,
                payWayCode: undefined,
                payProductName: undefined,
                payUserName: undefined
            },
            tableDialogKey: 0,
            listDialog: [],
            totalDialog: null,
            listDialogLoading: false,
            areaInfo: areaInfo,
            props: {
                value: 'label',
                children: 'children'
            },
            tempPayUserOption: [],
            tempPayCodeOption: [],
            tempPayNameSelect: [],
            tempPayTypeSelect: [
                {
                    label: '单笔金额',
                    value: 1,
                    disabled: false
                },
                {
                    label: '当日总额',
                    value: 2,
                    disabled: false
                },
                {
                    label: '时间段',
                    value: 3
                }
                // {
                //     label: '代理',
                //     value: 4
                // }
            ],
            tempOperatorSelect: [
                {
                    label: '=',
                    value: 1
                },
                {
                    label: '<>',
                    value: 2
                },
                {
                    label: '<',
                    value: 3
                },
                {
                    label: '<=',
                    value: 4
                },
                {
                    label: '>',
                    value: 5
                },
                {
                    label: '>=',
                    value: 6
                }
            ],
            tempConnectorSelect: [
                {
                    label: '空',
                    value: 3
                },
                {
                    label: '或者',
                    value: 1
                },
                {
                    label: '并且',
                    value: 2
                }
            ],
            temp: {
                payUser: '',
                payCode: '',
                payName: '',
                payOrder: '',
                payType: 1,
                operator: 1,
                value: 0,
                connector: 1
            },
            dialogFormVisible: false,
            dialogStatus: '',
            dialogInputStatus: 'number',
            textMap: {
                create: '添加规则',
                update: '修改规则'
            },
            rules: {
                payUser: [{ required: true, message: '请选择用户', trigger: 'change' }],
                payCode: [{ required: true, message: '请填写商户号', trigger: 'change' }],
                payName: [{ required: true, message: '请选择支付编号', trigger: 'change' }],
                payOrder: [{ required: true, message: '请填写通道排序', trigger: 'change,blur' }]
            },
            tempPayUserSelectLoading: false,
            tempPayCodeSelectLoading: false,
            tempPayNameSelectLoading: false,
            tempCreateLoading: false,
            tempUpdateLoading: false,
            dialogPushId: 0,
            listSelf: [],
            listDialogSelf: [],
            listDialogAdd: [],
            editId: '',
            handlerDeleteLoading: false,
            handlerForbiddenLoading: false,
            handlerDialogUpdateInsertLoading: false,
            handlerDialogUpdateDeleteLoading: false,
            tempUpdateRuleLoading: false
        }
    },
    created () {
        this.getList()
        this.selectPayUser()
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
                    this.list = response.data.Content.data.map((v) => {
                        this.$set(v, 'edit', true)

                        return v
                    })

                    this.list = response.data.Content.data
                    this.total = response.data.Content.total
                }
            })
        },
        selectPayUser () {
            if (this.tempPayUserOption.length === 0) {
                this.tempPayUserSelectLoading = true

                selectPayUserOption().then((response) => {
                    this.tempPayUserSelectLoading = false

                    if (response.data.Status === '200') {
                        this.tempPayUserOption = response.data.Content
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
        getDialogList (id) {
            this.listDialogLoading = true

            fetchDialogList({'id': id}).then((response) => {
                this.listDialogLoading = false

                if (response.data.Status !== '200') {
                    this.$message({
                        message: response.data.Content,
                        type: 'error'
                    })
                } else {
                    this.listDialog = response.data.Content.data.map((v) => {
                        this.$set(v, 'edit', false)

                        return v
                    })

                    this.listDialog = response.data.Content.data
                }
            })
        },
        closeDialog () {
            this.getList()
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
        resetTemp () {
            this.tempPayNameSelect = []
            this.dialogInputStatus = 'number'
            this.temp = {
                payCode: '',
                payName: '',
                payType: 1,
                operator: 1,
                value: 0,
                connector: 1
            }
        },
        handleCreate () {
            this.listDialog = []
            this.resetTemp()
            this.dialogStatus = 'create'
            this.dialogFormVisible = true

            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        handleDelete () {
            let delId = this.listSelf.map((v) => {
                return v.id
            })

            if (delId.length > 0) {
                this.handlerDeleteLoading = true

                deleteMerchantRule({'id': delId}).then((response) => {
                    this.handlerDeleteLoading = false

                    if (response.data.Status !== '200') {
                        this.$notify({
                            title: '失败',
                            message: response.data.Content,
                            type: 'error',
                            duration: 2000
                        })
                    } else {
                        this.getList()
                    }
                })
            }
        },
        handlerForbidden () {
            let disId = ''

            this.listSelf.map((v) => {
                disId += `${v.id},`
            })

            if (disId) {
                this.handlerForbiddenLoading = true

                forbiddenMerchantRule({'id': disId}).then((response) => {
                    this.handlerForbiddenLoading = false

                    if (response.data.Status !== '200') {
                        this.$notify({
                            title: '失败',
                            message: response.data.Content,
                            type: 'error',
                            duration: 2000
                        })
                    } else {
                        this.getList()
                    }
                })
            }
        },
        handlerDialogCreate () {
            this.dialogPushId = this.dialogPushId + 1

            this.listDialog.map((v) => {
                if (v.id === this.dialogPushId - 1) {
                    v.edit = false
                    v.payType = this.temp.payType
                    v.operator = this.temp.operator
                    v.value = this.temp.value
                    v.connector = this.temp.connector

                    return v
                }
            })

            let dialogPushArray = {
                'id': this.dialogPushId,
                'edit': true,
                'payType': 3,
                'operator': 1,
                'value': 0,
                'connector': 1,
                'link_operator': 1,
                'link_value': 0,
                'link_connector': 1
            }

            this.listDialog.push(dialogPushArray)
        },
        handlerDialogUpdateInsert () {
            if (this.listDialogAdd.length > 0) {
                this.$notify({
                    title: '失败',
                    message: '添加新规则前请先保存上一条内容',
                    type: 'error',
                    duration: 2000
                })
            } else {
                let dialogPushArray = {
                    'id': 'add',
                    'edit': true,
                    'payType': 3,
                    'operator': 1,
                    'value': 0,
                    'connector': 1,
                    'link_operator': 1,
                    'link_value': 0,
                    'link_connector': 1,
                    'group_id': this.editId
                }

                this.temp.payType = 3

                this.listDialog.push(dialogPushArray)
                this.listDialogAdd.push(dialogPushArray)
            }

            // this.handlerDialogUpdateInsertLoading = true

            // createDialogMerchantRule({'id': this.editId}).then((response) => {
            //     this.handlerDialogUpdateInsertLoading = false

            //     if (response.data.Status !== '200') {
            //         this.$notify({
            //             title: '失败',
            //             message: response.data.Content,
            //             type: 'error',
            //             duration: 2000
            //         })
            //     } else {
            //         let dialogPushArray = {
            //             'id': response.data.Content,
            //             'edit': false,
            //             'payType': 3,
            //             'operator': 1,
            //             'value': 0,
            //             'connector': 1,
            //             'link_operator': 1,
            //             'link_value': 0,
            //             'link_connector': 1
            //         }

            //         this.listDialog.push(dialogPushArray)
            //     }
            // })
        },
        listSelectionChange (self) {
            this.listSelf = self
        },
        listDialogSelectionChange (self) {
            this.listSelf = self
            this.listDialogSelf = self
        },
        handlerDialogDelete () {
            if (this.listDialogSelf.length > 0) {
                this.listDialogSelf.map((v) => {
                    this.listDialog.splice(v, 1)
                })
            }
        },
        handlerDialogUpdateDelete () {
            let delId = this.listDialogSelf.map((v) => {
                return v.id
            })

            if (delId.length > 0) {
                this.handlerDialogUpdateDeleteLoading = true

                deleteDialogMerchantRule({'id': delId}).then((response) => {
                    this.handlerDialogUpdateDeleteLoading = false

                    if (response.data.Status !== '200') {
                        this.$notify({
                            title: '失败',
                            message: response.data.Content,
                            type: 'error',
                            duration: 2000
                        })
                    } else {
                        this.getDialogList(this.editId)
                    }
                })
            }
        },
        payUserChange () {
            if (this.temp.payUser) {
                this.tempPayCodeSelectLoading = true

                const data = {
                    payUser: this.temp.payUser
                }

                selectPayCodeOption(data).then((response) => {
                    this.tempPayCodeSelectLoading = false

                    if (response.data.Status === '200') {
                        this.tempPayCodeOption = response.data.Content
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
        payCodeChange () {
            if (this.temp.payCode) {
                this.tempPayNameSelect = []
                this.temp.payName = ''
                this.tempPayNameSelectLoading = true

                selectPayNameOption(this.temp).then((response) => {
                    this.tempPayNameSelectLoading = false

                    if (response.data.Status === '200') {
                        if (response.data.Content[0]['label'] === null) {
                            this.tempPayNameSelect = []
                            this.temp.payName = ''
                        } else {
                            this.tempPayNameSelect = response.data.Content
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
        createData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.listDialog.map((v, k) => {
                        this.$refs.dialogTable.toggleRowSelection(v)

                        if ((k + 1) === this.listDialog.length) {
                            v.edit = false
                            v.payType = this.temp.payType
                            v.operator = this.temp.operator
                            v.value = this.temp.value
                            v.connector = this.temp.connector

                            return v
                        }
                    })

                    let createArray = {
                        'rule': this.listDialogSelf,
                        'payCode': this.temp.payCode,
                        'payName': this.temp.payName,
                        'payOrder': this.temp.payOrder
                    }

                    this.tempCreateLoading = true

                    createMerchantRule(createArray).then((response) => {
                        this.tempCreateLoading = false

                        if (response.data.Status === '200') {
                            this.dialogFormVisible = false
                            this.$notify({
                                title: '成功',
                                message: response.data.Content,
                                type: 'success',
                                duration: 2000
                            })
                        } else {
                            this.listDialog.map((v, k) => {
                                this.$refs.dialogTable.toggleRowSelection(v)
                            })

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
        updateData () {
            let dialogUpdateArray = {
                'id': this.editId,
                'payCode': this.temp.payCode,
                'payName': this.temp.payName,
                'payOrder': this.temp.payOrder
            }

            this.tempUpdateLoading = true

            updateMerchantDialogRule(dialogUpdateArray).then((response) => {
                this.tempUpdateLoading = false

                if (response.data.Status === '200') {
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
        handleUpdate (row) {
            this.temp.payUser = row.user_name
            this.temp.payCode = row.payCode
            this.selectPayUser()
            this.payUserChange()
            this.payCodeChange()
            this.temp.payName = row.payName
            this.temp.payOrder = row.order
            this.editId = row.id
            this.getDialogList(row.id)
            this.dialogStatus = 'update'
            this.dialogFormVisible = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        confirmEditClick (row) {
            this.list = this.list.map((v) => {
                v.edit = false

                return v
            })

            this.temp.payType = row.payType
            this.temp.operator = row.operator
            this.temp.value = row.value
            this.temp.connector = row.connector

            row.edit = true
        },
        confirmEdit (row) {
            this.$confirm('此操作将永久修改该内容, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                row.payType = this.temp.payType
                row.operator = this.temp.operator
                row.value = this.temp.value
                row.connector = this.temp.connector

                if (this.listDialogAdd.length > 0) {
                    row.id = this.listDialogAdd[0]['id']
                    row.group_id = this.listDialogAdd[0]['group_id']
                }

                this.tempUpdateRuleLoading = true

                updateMerchantRule(row).then((response) => {
                    row.edit = false
                    this.tempUpdateRuleLoading = false

                    if (response.data.Status === '200') {
                        this.$message({
                            message: '修改成功',
                            type: 'success'
                        })

                        if (this.listDialogAdd.length > 0) {
                            this.listDialogAdd = []
                            this.listDialog[this.listDialog.length - 1]['id'] = response.data.RuleId
                        }
                    } else {
                        this.$message({
                            message: response.data.Content,
                            type: 'error'
                        })
                    }
                })
            })
        },
        cancelEdit (row) {
            row.edit = false

            if (this.listDialogAdd.length > 0) {
                this.listDialogAdd = []
                this.listDialog.pop()
            }

            this.$message({
                message: '已取消修改',
                type: 'warning'
            })
        },
        payTypeChange () {
            switch (this.temp.payType) {
            case 1:
            case 2:
                this.dialogInputStatus = 'number'
                break
            case 3:
                this.dialogInputStatus = 'time'
                break
            case 4:
                this.dialogInputStatus = 'agent'
                break
            }
        }
    }
}
</script>
