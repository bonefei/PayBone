<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="商户号" v-model="listQuery.payCode"></el-input>

            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="支付名称" v-model="listQuery.payName"></el-input>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">{{$t('table.search')}}</el-button>

            <!-- <el-button class="filter-item" style="margin-left: 10px;" @click="handleCreate" type="primary" icon="el-icon-edit">{{$t('table.add')}}</el-button> -->
        </div>

        <el-table :key='tableKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%">
            <el-table-column align="center" label="编号" width="65">
                <template slot-scope="scope">
                    <span>{{scope.row.id}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户号">
                <template slot-scope="scope">
                    <span>{{scope.row.pay_way_code}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="时间">
                <template slot-scope="scope">
                    <span>{{scope.row.create_time}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="支付通道">
                <template slot-scope="scope">
                    <span>{{scope.row.product_name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="当天入金">
                <template slot-scope="scope">
                    <span :decimals="2">{{scope.row.dailyBalance}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="是否禁用">
                <template slot-scope="scope">
                    <span>
                        <span v-if="scope.row.is_on == '1'" style="color: green;">启用</span>
                        <span v-else style="color: red;">禁用</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="是否真实">
                <template slot-scope="scope">
                    <span>
                        <span v-if="scope.row.is_live == '1'" style="color: green;">真实</span>
                        <span v-else style="color: red;">测试</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户备注">
                <template slot-scope="scope">
                    <template v-if="scope.row.edit">
                        <el-input v-model="tempRemark.merchant_remark"></el-input>
                    </template>
                    <span v-else>
                        <span>{{scope.row.merchant_remark}}</span>
                    </span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" class-name="small-padding fixed-width" width="200">
                <template slot-scope="scope">
                    <el-button v-if="scope.row.edit" type="success" @click="confirmEdit(scope.row)" icon="el-icon-circle-check-outline">确认</el-button>
                    <el-button v-if="scope.row.edit" type="warning" @click="cancelEdit(scope.row)" icon="el-icon-circle-check-outline">取消</el-button>
                    <el-button v-else type="primary" @click='confirmEditClick(scope.row)' icon="el-icon-edit">编辑</el-button>
                    <el-button v-if="!scope.row.edit" type="success" @click='handleCheck(scope.row)' icon="el-icon-search">查看</el-button>
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
                <el-form-item label="Zoro商户号" prop="pay_way_code" v-show="dialogStatus=='create'">
                    <el-select v-model="temp.pay_way_code" filterable placeholder="请选择" :loading="tempPayWayCodeLoading">
                        <el-option
                            v-for="item in tempPayWayCodeOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="Zoro商户号" prop="pay_way_code" v-show="dialogStatus=='check'">
                    <el-input v-model="temp.pay_way_code"></el-input>
                </el-form-item>

                <el-form-item label="支付通道" prop="product_name" v-show="dialogStatus=='create'">
                    <el-select v-model="temp.product_name" filterable placeholder="请选择" @change="handleMerCodeChange()" :loading="tempPayMerCodeLoading">
                        <el-option
                            v-for="item in tempPayMerCodeOption"
                            :key="item.payname"
                            :label="item.payname"
                            :value="item.payname">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="商户号" prop="MerCode">
                    <el-input v-model="temp.MerCode"></el-input>
                </el-form-item>

                <el-form-item label="支付通道" prop="product_name" v-show="dialogStatus=='check'">
                    <el-input v-model="temp.product_name"></el-input>
                </el-form-item>

                <el-form-item label="密钥/证书" prop="MerCert">
                    <el-input v-model="temp.MerCert"></el-input>
                </el-form-item>

                <el-form-item label="支付帐号" prop="Account">
                    <el-input v-model="temp.Account"></el-input>
                </el-form-item>

                <el-form-item label="提交地址" prop="PostUrl">
                    <el-input v-model="temp.PostUrl"></el-input>
                </el-form-item>

                <el-form-item label="跳转商城地址" prop="RedirectUrl">
                    <el-input v-model="temp.RedirectUrl"></el-input>
                </el-form-item>

                <el-form-item label="版本号" prop="version">
                    <el-input v-model="temp.version"></el-input>
                </el-form-item>

                <el-form-item label="通知商户地址" prop="s_url">
                    <el-input v-model="temp.s_url"></el-input>
                </el-form-item>

                <el-form-item label="返回商户地址" prop="r_url">
                    <el-input v-model="temp.r_url"></el-input>
                </el-form-item>

                <el-form-item label="订单前戳" prop="order">
                    <el-input v-model="temp.order"></el-input>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button type="primary" @click="createData" :loading="tempCreateLoading" v-show="dialogStatus=='create'">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import { fetchList, createAisle, updateAisle, selectAislePayCode, selectAislePayMerCode, updateMerchantRemark } from '@/merchant/api/aisle'
import waves from '@/merchant/directive/waves' // 水波纹指令

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
                payCode: undefined,
                payName: undefined
            },
            temp: {
                pay_way_code: '',
                product_name: '',
                MerCode: '',
                MerCert: '',
                Account: '',
                PostUrl: '',
                RedirectUrl: '',
                version: '',
                s_url: '',
                r_url: '',
                order: ''
            },
            tempRemark: {
                merchant_remark: ''
            },
            tempPayWayCodeOption: [],
            tempPayMerCodeOption: [],
            tempPayMerCodeInfo: [],
            isOnOption: [{
                value: 1,
                label: '启用'
            }, {
                value: 0,
                label: '禁用'
            }],
            isOnEdit: '',
            isLiveOption: [{
                value: 1,
                label: '真实'
            }, {
                value: 0,
                label: '测试'
            }],
            isLiveEdit: '',
            isCreditOption: [{
                value: 1,
                label: '直连'
            }, {
                value: 0,
                label: '非直连'
            }],
            isCreditEdit: '',
            isOrderOption: [{
                value: 1,
                label: '客户提交'
            }, {
                value: 2,
                label: '重新生成'
            }],
            isOrderEdit: '',
            orderEdit: '',
            cappedEdit: '',
            dialogFormVisible: false,
            dialogStatus: '',
            textMap: {
                check: '查看通道',
                create: '添加通道'
            },
            rules: {
                pay_way_code: [{ required: true, message: '请填写商户号', trigger: 'change,blur' }],
                product_name: [{ required: true, message: '请填写支付商户号', trigger: 'change,blur' }],
                MerCode: [{ required: true, message: '请填写支付商户号', trigger: 'change,blur' }],
                MerCert: [{ required: true, message: '请填写密钥/证书', trigger: 'change,blur' }],
                Account: [{ required: true, message: '请填写支付账号', trigger: 'change,blur' }],
                PostUrl: [{ required: true, message: '请填写支付提交地址', trigger: 'change,blur' }, { type: 'url', message: '请输入完整地址', trigger: 'blur,change' }],
                RedirectUrl: [{ required: true, message: '请填写商城中转地址', trigger: 'change,blur' }, { type: 'url', message: '请输入完整地址', trigger: 'blur,change' }],
                version: [{ required: true, message: '请填写支付版本号', trigger: 'change,blur' }],
                s_url: [{ required: true, message: '请填写通知商户地址', trigger: 'change,blur' }, { type: 'url', message: '请输入完整地址', trigger: 'blur,change' }],
                r_url: [{ required: true, message: '请填写返回商户地址', trigger: 'change,blur' }, { type: 'url', message: '请输入完整地址', trigger: 'blur,change' }],
                order: [{ required: true, message: '请填写订单前戳', trigger: 'change,blur' }]
            },
            tempCreateLoading: false,
            tempUpdateLoading: false,
            downloadLoading: false,
            bankLoading: false,
            tempPayWayCodeLoading: false,
            tempPayMerCodeLoading: false
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
                pay_way_code: '',
                MerCode: '',
                MerCert: '',
                Account: '',
                PostUrl: '',
                RedirectUrl: '',
                version: '',
                s_url: '',
                r_url: '',
                order: '',
                remark: ''
            }
        },
        handleCreate () {
            this.resetTemp()
            this.dialogStatus = 'create'
            this.dialogFormVisible = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })

            if (this.tempPayWayCodeOption.length === 0) {
                this.tempPayWayCodeLoading = true

                selectAislePayCode().then((response) => {
                    this.tempPayWayCodeLoading = false

                    if (response.data.Status === '200') {
                        this.tempPayWayCodeOption = response.data.Content
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

            if (this.tempPayMerCodeOption.length === 0) {
                this.tempPayMerCodeLoading = true

                selectAislePayMerCode().then((response) => {
                    this.tempPayMerCodeLoading = false

                    if (response.data.Status === '200') {
                        this.tempPayMerCodeOption = response.data.Content
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
        handleCheck (row) {
            this.resetTemp()
            this.dialogStatus = 'check'
            this.dialogFormVisible = true

            this.temp = {
                pay_way_code: '',
                MerCode: '',
                MerCert: '',
                Account: '',
                PostUrl: '',
                RedirectUrl: '',
                version: '',
                s_url: '',
                r_url: '',
                order: '',
                remark: ''
            }

            this.temp = row
            this.temp.order = row.order_pfx
        },
        handleMerCodeChange () {
            if (this.temp.MerCode) {
                this.tempPayMerCodeInfo = []
                for (let i = 0; i < this.tempPayMerCodeOption.length;i++) {
                    if (this.temp.MerCode === this.tempPayMerCodeOption[i]['payname']) {
                        this.tempPayMerCodeInfo = this.tempPayMerCodeOption[i]
                    }
                }

                for (let key in this.tempPayMerCodeInfo) {
                    for (let k in this.rules) {
                        if (key === k && this.tempPayMerCodeInfo[key] === 1) {
                            this.rules[k][0]['required'] = true
                        } else if (key === k && this.tempPayMerCodeInfo[key] === 0) {
                            this.rules[k][0]['required'] = false
                        }
                    }
                }
            }
        },
        createData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempCreateLoading = true

                    createAisle(this.temp).then((response) => {
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
            this.tempRemark.merchant_remark = row.merchant_remark

            row.edit = true
        },
        confirmEdit (row) {
            this.$confirm('此操作将永久修改该内容, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                row.edit = false
                row.merchant_remark = this.tempRemark.merchant_remark

                this.tempRemark.merchant_remark = ''

                this.$message({
                    message: '修改成功',
                    type: 'success'
                })

                updateMerchantRemark(row)
            })
        },
        cancelEdit (row) {
            row.edit = false
            this.statusEdit = ''

            this.$message({
                message: '已取消修改',
                type: 'warning'
            })
        }
    }
}
</script>
