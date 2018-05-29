<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="用户名" v-model="listQuery.user_name"></el-input>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">{{$t('table.search')}}</el-button>

            <el-button class="filter-item" style="margin-left: 10px;" @click="handleCreate" type="primary" icon="el-icon-edit">{{$t('table.add')}}</el-button>
        </div>

        <el-table :key='tableKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%">
            <el-table-column align="center" label="报表类型">
                <template slot-scope="scope">
                    <span v-if="scope.row.type == 1">入金报表</span>
                    <span v-else-if="scope.row.type == 2">出金报表</span>
                    <span v-else>余额报表</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="周期" width="100">
                <template slot-scope="scope">
                    <span v-if="scope.row.cycle == 1">日</span>
                    <span v-else-if="scope.row.cycle == 2">周</span>
                    <span v-else>月</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户名称" width="100">
                <template slot-scope="scope">
                    <span>{{scope.row.user_name}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="商户号">
                <template slot-scope="scope">
                    <span>{{scope.row.user_no}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="时区">
                <template slot-scope="scope">
                    <span>GMT+{{scope.row.zone}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="收件人">
                <template slot-scope="scope">
                    <span>{{scope.row.accept_email}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="备注">
                <template slot-scope="scope">
                    <span>{{scope.row.remarks}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="状态" width="100">
                <template slot-scope="scope">
                    <span v-if="scope.row.status == 1">启用</span>
                    <span v-else>禁用</span>
                </template>
            </el-table-column>

            <el-table-column align="center" :label="$t('table.actions')" class-name="small-padding fixed-width">
                <template slot-scope="scope">
                    <el-button type="primary" size="mini" @click="handleUpdate(scope.row)">{{$t('table.edit')}}</el-button>
                    <el-button type="danger" size="mini" @click="handleDelete(scope.row.id)" :loading="tempDeleteLoading">{{$t('table.delete')}}</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10,20,30, 50]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>

        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible" @close="resetTemp">
            <el-form :rules="rules" ref="dataForm" :model="temp" label-position="left" label-width="180px" style='width: 500px; margin-left:50px;'>
                <el-form-item label="报表类型" prop="type">
                    <el-select v-model="temp.type" filterable placeholder="请选择">
                        <el-option
                            v-for="item in tempReportTypeOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="报表周期" prop="cycle">
                    <el-select v-model="temp.cycle" filterable placeholder="请选择">
                        <el-option
                            v-for="item in tempReportCycleOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="报表时区" prop="zone">
                    <el-input type="text"  v-model="temp.zone">
                    </el-input>
                </el-form-item>

                <el-form-item label="商户号" prop="user_no">
                    <el-select v-model="temp.user_no" filterable multiple placeholder="请选择" :loading="tempUserNoLoading">
                        <el-option
                            v-for="item in tempUserNoOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="状态" prop="status">
                    <el-select v-model="temp.status" filterable placeholder="请选择">
                        <el-option
                            v-for="item in tempStatusOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="收件人(多个以,分割)" prop="accept_email">
                    <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 4}" v-model="temp.accept_email">
                    </el-input>
                </el-form-item>

                <el-form-item label="备注" prop="remarks">
                    <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 4}" v-model="temp.remarks">
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
import { fetchList, selectUserNo, createReportSend, updateReportSend, deleteReportSend } from '@/merchant/api/reportSendSetting'
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
                user_name: undefined
            },
            props: {
                value: 'label',
                children: 'children'
            },
            tempReportTypeOption: [
                {
                    'label': '入金报表',
                    'value': 1
                },
                {
                    'label': '出金报表',
                    'value': 2
                },
                {
                    'label': '余额报表',
                    'value': 3
                }
            ],
            tempReportCycleOption: [
                {
                    'label': '日',
                    'value': 1
                },
                {
                    'label': '周',
                    'value': 2
                },
                {
                    'label': '月',
                    'value': 3
                }
            ],
            tempStatusOption: [
                {
                    'label': '启用',
                    'value': 1
                },
                {
                    'label': '禁用',
                    'value': 2
                }
            ],
            tempUserNoOption: [],
            temp: {
                type: '',
                cycle: '',
                user_no: '',
                zone: '',
                status: '',
                accept_email: '',
                remarks: ''
            },
            rules: {
                type: [{ required: true, message: '请选择类型', trigger: 'change' }],
                cycle: [{ required: true, message: '请选择周期', trigger: 'change' }],
                user_no: [{ required: true, message: '请选择商户号', trigger: 'change' }],
                zone: [{ required: true, message: '请选填写时区', trigger: 'change' }],
                status: [{ required: true, message: '请选择状态', trigger: 'change' }],
                accept_email: [{ required: true, message: '请填写接收邮箱', trigger: 'change' }],
                remarks: [{ required: true, message: '请填写备注', trigger: 'change' }]
            },
            dialogFormVisible: false,
            dialogStatus: '',
            tempPayCodeSelectLoading: false,
            textMap: {
                create: '添加配置',
                update: '修改配置'
            },
            tempCreateLoading: false,
            tempUpdateLoading: false,
            tempDeleteLoading: false,
            tempUserNoLoading: false
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
        resetTemp () {
            this.temp = {
                type: '',
                cycle: '',
                user_name: '',
                user_no: '',
                zone: '',
                accept_email: '',
                remarks: ''
            }

            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        handleCreate () {
            this.dialogStatus = 'create'
            this.dialogFormVisible = true

            this.selectUserNoOption()
        },
        selectUserNoOption () {
            this.temp.user_no = []
            this.tempUserNoOption = []
            this.tempUserNoLoading = true

            const data = {
                'id': this.temp.user_name
            }

            selectUserNo(data).then((response) => {
                this.tempUserNoLoading = false

                if (response.data.Status === '200') {
                    this.tempUserNoOption = response.data.Content
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
        createData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempCreateLoading = true

                    createReportSend(this.temp).then((response) => {
                        this.tempCreateLoading = false

                        if (response.data.Status === '200') {
                            this.getList()
                            this.resetTemp()
                            this.dialogFormVisible = false
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
        handleUpdate (row) {
            this.temp = Object.assign({}, row) // copy obj
            this.selectUserNoOption()
            this.dialogStatus = 'update'
            this.dialogFormVisible = true
            this.temp.user_no = row.user_no.split(',')
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        updateData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempUpdateLoading = true

                    updateReportSend(this.temp).then((response) => {
                        this.tempUpdateLoading = false

                        if (response.data.Status === '200') {
                            this.getList()
                            this.resetTemp()
                            this.dialogFormVisible = false
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
        handleDelete (id) {
            this.$confirm('是否确定删除当前规则？', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                const data = {
                    id: id
                }

                this.tempDeleteLoading = true

                deleteReportSend(data).then((response) => {
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
        }
    }
}
</script>
