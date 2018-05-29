<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="updateBatch">批量出金</el-button>
        </div>
        <div class="filter-container">
            <el-row :gutter="20">
                <el-col :span="12" :offset="2">
                    <el-form  ref="form" :model="temp" :rules="rules" label-width="80px">
                        <el-form-item label="申请用户" prop="payUser">
                            <el-select v-model="temp.payUser" filterable placeholder="请选择" @change="payUserChange" :loading="tempPayUserSelectLoading">
                                <el-option
                                    v-for="item in tempPayUserOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </el-form-item>
                        
                        <el-form-item label="申请商户" prop="pay_way_code">
                            <el-select v-model="temp.pay_way_code" filterable placeholder="请选择" @change="payWayCodeChange()"  :loading="tempPayWayCodeLoading">
                                <el-option
                                    v-for="item in tempPayWayCodeOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </el-form-item>

                        <el-form-item label="出金通道" prop="pay_aisle">
                            <el-select v-model="temp.pay_aisle" filterable placeholder="请选择"  :loading="tempPayAisleLoading">
                                <el-option
                                    v-for="item in tempPayAisleOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </el-form-item>

                        <el-form-item label="出金银行" prop="payType">
                            <el-input v-model="temp.payType" style="width: 300px;" class="filter-item" placeholder="出金银行"></el-input>
                        </el-form-item>

                        <el-form-item label="银行卡号" prop="payBankAcc">
                            <el-input v-model="temp.payBankAcc" style="width: 300px;" class="filter-item" placeholder="银行卡号"></el-input>
                        </el-form-item>

                        <el-form-item label="户名" prop="payBankName">
                            <el-input v-model="temp.payBankName" style="width: 300px;" class="filter-item" placeholder="银行卡户名"></el-input>
                        </el-form-item>

                        <el-form-item label="出金金额" prop="payamount">
                            <el-input type="number" min="0" v-model="temp.payamount" style="width: 300px;" class="filter-item" placeholder="出金金额"></el-input>
                        </el-form-item>

                        <el-form-item label="出金状态" prop="status">
                            <el-select v-model="temp.status" filterable placeholder="请选择">
                                <el-option
                                    v-for="item in statusEditOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>
                        </el-form-item>
                        
                        <el-form-item>
                            <el-button type="primary" @click="sumitApply()" :loading="tempApplyLoading">确定</el-button>
                            <el-button @click="resetApply()">重置</el-button>
                        </el-form-item>
                    </el-form>
                </el-col>
            </el-row>
        </div>

        <el-dialog title="批量上传" :visible.sync="dialogUploadVisible">
            <el-form ref="modifyPassForm" label-position="left" label-width="150px" style='width: 400px; margin-left:50px;'>
                
                <el-form-item label="选择文件">
                    <input type="file" accept=".xls,.xlsx" @change="getFile($event, 'batch')">
                </el-form-item>

            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogUploadVisible = false">{{$t('table.cancel')}}</el-button>
                <el-button type="primary" @click="submitForm($event)" :loading="tempUploadCertificateLoading">{{$t('table.confirm')}}</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import { fetchList, payAisleSelect, merchantWithdrawalApply, selectPayUserOption, uploadBatchExcel } from '@/admin/api/applyWithdrawal'
import waves from '@/admin/directive/waves' // 水波纹指令

export default {
    name: 'complexTable',
    directives: {
        waves
    },
    data () {
        return {
            statusEditOption: [{
                value: 2,
                label: '已出金'
            }, {
                value: 1,
                label: '待出金'
            }],
            temp: {
                payUser: '',
                pay_way_code: '',
                pay_aisle: '',
                payType: '',
                payamount: '',
                payBankAcc: '',
                payBankName: '',
                status: ''
            },
            rules: {
                payUser: [{ required: true, message: '请选择用户', trigger: 'change' }],
                pay_way_code: [{ required: true, message: '请选择出金商户', trigger: 'change' }],
                pay_aisle: [{ required: true, message: '请选择出金通道', trigger: 'change' }],
                payType: [{ required: true, message: '请填写出金银行', trigger: 'change,blur' }],
                payBankAcc: [{ required: true, message: '请填写银行卡号', trigger: 'change,blur' }],
                payBankName: [{ required: true, message: '请填写银行卡户名', trigger: 'change,blur' }],
                payamount: [{ required: true, message: '请填写出金金额', trigger: 'change,blur' }],
                status: [{ required: true, message: '请选择出金状态', trigger: 'change,blur' }]
            },
            tempPayUserOption: [],
            tempPayWayCodeOption: [],
            tempPayAisleOption: [],
            tempUpload: {
                excel: ''
            },
            tempPayWayCodeLoading: false,
            tempPayAisleLoading: false,
            tempApplyLoading: false,
            tempPayUserSelectLoading: false,
            dialogUploadVisible: false,
            tempUploadCertificateLoading: false
        }
    },
    created () {
        this.getPayUserList()
    },
    methods: {
        getPayUserList () {
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
        payUserChange () {
            if (this.temp.payUser) {
                this.tempPayWayCodeLoading = true

                const data = {
                    user_name: this.temp.payUser
                }

                fetchList(data).then((response) => {
                    this.tempPayWayCodeLoading = false

                    if (response.data.Status !== '200') {
                        this.$message({
                            message: '信息获取错误请稍后再试',
                            type: 'error'
                        })
                    } else {
                        if (response.data.Content[0]['label'] === null) {
                            this.tempPayWayCodeOption = []
                            this.temp.pay_way_code = ''

                            this.$message({
                                message: '当前用户没有下级商户',
                                type: 'error'
                            })
                        } else {
                            this.tempPayWayCodeOption = response.data.Content
                        }
                    }
                })
            }
        },
        payWayCodeChange () {
            if (this.temp.pay_way_code) {
                this.tempPayAisleOption = []
                this.temp.pay_aisle = ''
                this.tempPayAisleLoading = true

                payAisleSelect(this.temp).then((response) => {
                    this.tempPayAisleLoading = false

                    if (response.data.Status === '200') {
                        this.tempPayAisleOption = response.data.Content
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
        resetApply () {
            this.temp = {
                pay_way_code: '',
                pay_aisle: '',
                payType: '',
                payamount: '',
                payBankAcc: '',
                payBankName: '',
                status: ''
            }

            this.$nextTick(() => {
                this.$refs['form'].clearValidate()
            })
        },
        sumitApply () {
            this.$refs['form'].validate((valid) => {
                if (valid) {
                    this.$confirm('是否确定出金申请?', '提示', {
                        confirmButtonText: '确定',
                        cancelButtonText: '取消',
                        type: 'warning'
                    }).then(() => {
                        this.tempApplyLoading = true

                        merchantWithdrawalApply(this.temp).then((response) => {
                            this.tempApplyLoading = false

                            if (response.data.Status === '200') {
                                this.dialogFormVisible = false
                                this.$notify({
                                    title: '成功',
                                    message: response.data.Content,
                                    type: 'success',
                                    duration: 2000
                                })

                                this.resetApply()
                            } else {
                                this.$notify({
                                    title: '失败',
                                    message: response.data.Content,
                                    type: 'error',
                                    duration: 2000
                                })
                            }
                        }).catch(() => {
                            this.tempApplyLoading = false
                        })
                    })
                }
            })
        },
        confirmEditClick (row) {
            this.statusEdit = row.status

            this.list = this.list.map((v) => {
                v.edit = false

                return v
            })

            row.edit = true
        },
        getFile (event, type) {
            if (type === 'batch') {
                this.tempUpload.excel = event.target.files[0]
            }
        },
        updateBatch () {
            this.dialogUploadVisible = true
        },
        submitForm () {
            if (this.tempUpload.excel === '') {
                this.$notify({
                    title: '失败',
                    message: '请先选择要上传的证书',
                    type: 'error',
                    duration: 2000
                })
            } else {
                let formData = new FormData()

                formData.append('excel', this.tempUpload.excel)

                this.tempUploadCertificateLoading = true

                uploadBatchExcel(formData).then((response) => {
                    this.tempUploadCertificateLoading = false

                    if (response.data.Status === '200') {
                        this.dialogUploadVisible = false

                        this.$notify({
                            title: '成功',
                            message: response.data.Content,
                            type: 'success',
                            duration: 2000
                        })

                        this.tempUpload.excel = ''
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
        }
    }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
    el-form-item {
        margin-top: 50px;
    }
</style>