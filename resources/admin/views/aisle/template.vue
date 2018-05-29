<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="支付编号" v-model="listQuery.payName"></el-input>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">{{$t('table.search')}}</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handleCreate" type="primary" icon="el-icon-edit">{{$t('table.add')}}</el-button>
        </div>

        <el-table :key='tableKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%">
            <el-table-column align="center" label="编号" width="65">
                <template slot-scope="scope">
                    <span>{{scope.row.id}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="支付编号">
                <template slot-scope="scope">
                    <span>{{scope.row.payname}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户号">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="merCodeEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.MerCode == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="秘钥/证书">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="merCertEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.MerCert == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="支付账号">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="accountEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.Account == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="提交地址" width="400">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-input v-model="postUrlEdit"></el-input>
                    </template>
                    <span v-else>{{scope.row.PostUrl}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="备注">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="remarkEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.remark == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="跳转商城地址">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="redirectUrlEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.RedirectUrl == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="版本号">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="versionEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.version == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="通知商户地址">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="surlEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.s_url == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="返回商户地址">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="rurlEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.r_url == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="订单号前戳">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-select v-model="orderEdit" placeholder="请选择">
                            <el-option
                                v-for="item in editOption"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value">
                            </el-option>
                        </el-select>
                    </template>
                    <span v-else>
                        <span v-if="scope.row.order == '0'" style="color: green;">不必填</span>
                        <span v-else style="color: red;">必填</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" class-name="small-padding fixed-width" width="200">
                <template slot-scope="scope">
                    <el-button v-if="scope.row.edit" type="success" @click="confirmEdit(scope.row)" icon="el-icon-circle-check-outline">确认</el-button>
                    <el-button v-if="scope.row.edit" type="warning" @click="cancelEdit(scope.row)" icon="el-icon-circle-check-outline">取消</el-button>
                    <el-button v-else type="primary" @click='confirmEditClick(scope.row)' icon="el-icon-edit">编辑</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10,20,30, 50]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>

        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible">
            <el-form :rules="rules" ref="dataForm" :model="temp" label-position="left" label-width="120px" style='width: 400px; margin-left:50px;'>
                <el-form-item label="支付编号" prop="payname">
                    <el-input v-model="temp.payname"></el-input>
                </el-form-item>

                <el-form-item label="商户号" prop="MerCode">
                    <el-select v-model="temp.MerCode">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="秘钥/证书" prop="MerCert">
                    <el-select v-model="temp.MerCert">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="支付账号" prop="Account">
                    <el-select v-model="temp.Account">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="提交地址" prop="PostUrl">
                    <el-input v-model="temp.PostUrl"></el-input>
                </el-form-item>

                <el-form-item label="备注" prop="remark">
                    <el-select v-model="temp.remark">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="商城地址" prop="RedirectUrl">
                    <el-select v-model="temp.RedirectUrl">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="版本号" prop="version">
                    <el-select v-model="temp.version">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="通知商户地址" prop="surl">
                    <el-select v-model="temp.surl">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="返回商户地址" prop="rurl">
                    <el-select v-model="temp.rurl">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="订单号前戳" prop="order">
                    <el-select v-model="temp.order">
                        <el-option label="必填" :value="1"></el-option>
                        <el-option label="不必填" :value="0"></el-option>
                    </el-select>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button type="primary" @click="createData" :loading="tempCreateLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import { fetchList, createAisleTemplate, updateAisleTemplate } from '@/admin/api/aisleTemplate'
import waves from '@/admin/directive/waves' // 水波纹指令

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
                payName: undefined
            },
            temp: {
                payname: '',
                MerCode: '',
                MerCert: '',
                Account: '',
                PostUrl: '',
                remark: '',
                RedirectUrl: '',
                version: '',
                surl: '',
                rurl: '',
                order: ''
            },
            editOption: [{
                value: 1,
                label: '必填'
            }, {
                value: 0,
                label: '不必填'
            }],
            merCodeEdit: '',
            merCertEdit: '',
            accountEdit: '',
            postUrlEdit: '',
            remarkEdit: '',
            redirectUrlEdit: '',
            versionEdit: '',
            surlEdit: '',
            rurlEdit: '',
            orderEdit: '',
            dialogFormVisible: false,
            dialogStatus: '',
            textMap: {
                update: '修改模板',
                create: '添加模板'
            },
            rules: {
                payname: [{ required: true, message: '请填写支付编号', trigger: 'change,blur' }],
                MerCode: [{ required: true, message: '请选择商户号', trigger: 'change,blur' }],
                MerCert: [{ required: true, message: '请选择秘钥/证书', trigger: 'change,blur' }],
                Account: [{ required: true, message: '请选择支付账号', trigger: 'change,blur' }],
                PostUrl: [{ required: true, message: '请填写提交地址', trigger: 'change,blur' }, { type: 'url', message: '请输入完整地址', trigger: 'blur,change' }],
                remark: [{ required: true, message: '请选择备注', trigger: 'change,blur' }],
                RedirectUrl: [{ required: true, message: '请选择商城地址', trigger: 'change,blur' }],
                version: [{ required: true, message: '请选择版本号', trigger: 'change,blur' }],
                surl: [{ required: true, message: '请选择通知商户地址', trigger: 'change,blur' }],
                rurl: [{ required: true, message: '请选择返回商户地址', trigger: 'change,blur' }],
                order: [{ required: true, message: '请选择订单号前戳', trigger: 'change,blur' }]
            },
            tempCreateLoading: false
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
                    this.list = response.data.Content.data.map((v) => {
                        this.$set(v, 'edit', false)

                        v.originalStatus = v.status

                        return v
                    })

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
        resetTemp () {
            this.temp = {
                payname: '',
                MerCode: '',
                MerCert: '',
                Account: '',
                PostUrl: '',
                remark: '',
                RedirectUrl: '',
                version: '',
                surl: '',
                rurl: '',
                order: ''
            }
        },
        handleCreate () {
            this.resetTemp()
            this.dialogStatus = 'create'
            this.dialogFormVisible = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        createData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempCreateLoading = true

                    createAisleTemplate(this.temp).then((response) => {
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
        confirmEditClick (row) {
            this.merCodeEdit = row.MerCode
            this.merCertEdit = row.MerCert
            this.accountEdit = row.Account
            this.postUrlEdit = row.PostUrl
            this.remarkEdit = row.remark
            this.redirectUrlEdit = row.RedirectUrl
            this.versionEdit = row.version
            this.surlEdit = row.s_url
            this.rurlEdit = row.r_url
            this.orderEdit = row.order

            this.list = this.list.map((v) => {
                v.edit = false

                return v
            })

            row.edit = true
        },
        confirmEdit (row) {
            this.$confirm('此操作将永久修改该内容, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                row.edit = false

                row.MerCode = this.merCodeEdit
                row.MerCert = this.merCertEdit
                row.Account = this.accountEdit
                row.PostUrl = this.postUrlEdit
                row.remark = this.remarkEdit
                row.RedirectUrl = this.redirectUrlEdit
                row.version = this.versionEdit
                row.s_url = this.surlEdit
                row.r_url = this.rurlEdit
                row.order = this.orderEdit

                this.merCodeEdit = ''
                this.merCertEdit = ''
                this.accountEdit = ''
                this.postUrlEdit = ''
                this.remarkEdit = ''
                this.redirectUrlEdit = ''
                this.versionEdit = ''
                this.surlEdit = ''
                this.rurlEdit = ''
                this.orderEdit = ''

                updateAisleTemplate(row).then((response) => {
                    if (response.data.Status === '200') {
                        this.$message({
                            message: '修改成功',
                            type: 'success'
                        })
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

            this.$message({
                message: '已取消修改',
                type: 'warning'
            })
        }
    }
}
</script>
