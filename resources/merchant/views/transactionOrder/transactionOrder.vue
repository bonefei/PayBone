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
                    <span>{{scope.row.create_time | parseTime('{y}-{m}-{d} {h}:{i}:{s}')}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.username')">
                <template slot-scope="scope">
                    <span>{{scope.row.user_name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.merchantNumber')">
                <template slot-scope="scope">
                    <span>{{scope.row.user_no}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.balance')">
                <template slot-scope="scope">
                    <span>{{scope.row.balance}}</span>
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

            <el-table-column align="center" :label="$t('table.area')">
                <template slot-scope="scope">
                    <span>{{scope.row.province}}/{{scope.row.city}}/{{scope.row.county}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" class-name="small-padding fixed-width">
                <template slot-scope="scope">
                    <el-button type="primary" size="mini" @click="handleUpdate(scope.row)">{{$t('table.edit')}}</el-button>
                    <el-button v-if="scope.row.status!='published'" size="mini" type="success" @click="handleModifyStatus(scope.row)" :loading="bankLoading">证书</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10,20,30, 50]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>

        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible">
            <el-form :rules="rules" ref="dataForm" :model="temp" label-position="left" label-width="70px" style='width: 400px; margin-left:50px;'>
                <el-form-item :label="$t('table.date')" prop="createTime" v-if="dialogStatus=='create'">
                    <el-date-picker v-model="temp.create_time" type="datetime" format="yyyy-MM-dd HH:mm:ss" value-format="yyyy-MM-dd HH:mm:ss">
                    </el-date-picker>
                </el-form-item>

                <el-form-item :label="$t('table.area')" prop="area">
                    <el-cascader :options="areaInfo" :props="props" v-model="temp.area" :change-on-select="true" :clearable="true" :filterable="true">
                    </el-cascader>
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

                <el-form-item :label="$t('table.password')" prop="password" v-if="dialogStatus=='create'">
                    <el-input v-model="temp.password"></el-input>
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
    </div>
</template>

<script>
import { fetchList, createMerchant, updateMerchant } from '@/merchant/api/merchant'
import waves from '@/merchant/directive/waves' // 水波纹指令
import { parseTime } from '@/merchant/utils'
import areaInfo from '@/merchant/utils/area'

export default {
    name: 'complexTable',
    directives: {
        waves
    },
    data () {
        const validatePassword = (rule, value, callback) => {
            if (value.length < 6) {
                callback(new Error('密码不能少于6位'))
            } else if (value.length > 32) {
                callback(new Error('密码不能大于32位'))
            } else {
                callback()
            }
        }

        return {
            tableKey: 0,
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
            areaInfo: areaInfo,
            props: {
                value: 'label',
                children: 'children'
            },
            temp: {
                create_time: new Date(),
                area: [],
                user_name: '',
                payname: '',
                name: '',
                mobile: '',
                email: '',
                password: '',
                introduction: ''
            },
            dialogFormVisible: false,
            dialogStatus: '',
            textMap: {
                update: '修改商户',
                create: '添加商户'
            },
            rules: {
                create_time: [{ required: true, message: '请填写创建时间', trigger: 'change' }],
                area: [{ required: true, message: '请选择地区', trigger: 'change' }],
                user_name: [{ required: true, message: '请填写用户名(可用于登录使用)', trigger: 'blur' }],
                payname: [{ required: true, message: '请填写商户名', trigger: 'blur' }],
                name: [{ required: true, message: '请填写姓名', trigger: 'blur' }],
                mobile: [{ required: true, message: '请填写手机号', trigger: 'blur' }],
                email: [{ required: true, message: '请填写邮箱', trigger: 'blur' }, { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur,change' }],
                password: [{ required: true, message: '请填写密码', trigger: 'blur' }, { required: true, trigger: 'change', validator: validatePassword }]
            },
            tempCreateLoading: false,
            tempUpdateLoading: false,
            downloadLoading: false,
            bankLoading: false
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
            window.open(`/merchant/v1/merchantDownload?id=${row.id}`)
        },
        resetTemp () {
            this.temp = {
                create_time: new Date(),
                area: [],
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
            let tempArea = [
                row.province,
                row.city,
                row.county
            ]

            this.temp = Object.assign({}, row) // copy obj
            this.temp.area = tempArea
            this.temp.timestamp = new Date(this.temp.timestamp)
            this.dialogStatus = 'update'
            this.dialogFormVisible = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        updateData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempUpdateLoading = true
                    const tempData = Object.assign({}, this.temp)
                    tempData.timestamp = +new Date(tempData.timestamp) // change Thu Nov 30 2017 16:41:05 GMT+0800 (CST) to 1512031311464
                    updateMerchant(tempData).then((response) => {
                        this.tempUpdateLoading = false
                        if (response.data.Status === '200') {
                            for (const v of this.list) {
                                if (v.id === this.temp.id) {
                                    const index = this.list.indexOf(v)
                                    this.temp.province = this.temp.area[0]
                                    this.temp.city = this.temp.area[1]
                                    this.temp.county = this.temp.area[2]
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

            const { exportJsonToExcel } = require('@/merchant/vendor/Export2Excel')

            const tHeader = ['编号', '时间', '用户名', '商户号', '余额', '姓名', '手机号', '邮箱', '地区']
            const filterVal = ['id', 'create_time', 'user_name', 'user_no', 'balance', 'name', 'mobile', 'email', 'province,city,county']
            const data = this.formatJson(filterVal, this.list)
            exportJsonToExcel(tHeader, data, '商户列表')

            setTimeout(() => {
                this.downloadLoading = false
            }, 2000)
        },
        formatJson (filterVal, jsonData) {
            return jsonData.map((v) => filterVal.map((j) => {
                if (j === 'timestamp') {
                    return parseTime(v[j])
                } else {
                    return v[j]
                }
            }))
        }
    }
}
</script>
