<template>
    <div class="app-container calendar-list-container">
        <div class="filter-container">
            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="商户号" v-model="listQuery.payCode"></el-input>

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

            <el-table-column align="center" label="商户号" width="100">
                <template slot-scope="scope">
                    <span>{{scope.row.zoroCode}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="敏感词">
                <template slot-scope="scope">
                    <span>{{scope.row.KeyWords}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="黑名单(IP)">
                <template slot-scope="scope">
                    <span>{{scope.row.Ips}}</span>
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

        <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible">
            <el-form :rules="rules" ref="dataForm" :model="temp" label-position="left" label-width="180px" style='width: 500px; margin-left:50px;'>
                <el-form-item label="商户号" prop="payCode">
                    <el-select v-model="temp.payCode" filterable placeholder="请选择" :loading="tempPayCodeSelectLoading">
                        <el-option
                            v-for="item in tempPayCodeOption"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item label="敏感词(多个已,分割)">
                    <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 4}" v-model="temp.KeyWords">
                    </el-input>
                </el-form-item>

                <el-form-item label="IP(多个已,分割)">
                    <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 4}" v-model="temp.Ips">
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
import { fetchList, createGatewayBlacklist, updateGatewayBlacklist, deleteGatewayBlacklist, selectPayCodeOption } from '@/merchant/api/gatewayBlacklist'
import waves from '@/merchant/directive/waves' // 水波纹指令
import areaInfo from '@/merchant/utils/area'

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
                payCode: undefined
            },
            areaInfo: areaInfo,
            props: {
                value: 'label',
                children: 'children'
            },
            temp: {
                payCode: '',
                KeyWords: '',
                Ips: ''
            },
            dialogFormVisible: false,
            dialogStatus: '',
            textMap: {
                update: '修改网关黑名单',
                create: '添加网关黑名单'
            },
            rules: {
                payCode: [{ required: true, message: '请选择商户号', trigger: 'change' }]
            },
            tempPayCodeOption: [],
            tempCreateLoading: false,
            tempUpdateLoading: false,
            tempDeleteLoading: false,
            tempPayCodeSelectLoading: false
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
                payCode: '',
                KeyWords: '',
                Ips: ''
            }
        },
        handleCreate () {
            this.selectPayWayCode()

            this.resetTemp()
            this.dialogStatus = 'create'
            this.dialogFormVisible = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        selectPayWayCode () {
            if (this.tempPayCodeOption.length === 0) {
                this.tempPayUserSelectLoading = true

                selectPayCodeOption().then((response) => {
                    this.tempPayUserSelectLoading = false

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
        createData () {
            this.$refs['dataForm'].validate((valid) => {
                if (valid) {
                    this.tempCreateLoading = true

                    createGatewayBlacklist(this.temp).then((response) => {
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
                            if (response.data.Status === 'GBC101') {
                                this.listQuery.payCode = this.temp.payCode
                                this.dialogFormVisible = false
                                this.getList()
                            }

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
            this.selectPayWayCode()
            this.temp = Object.assign({}, row) // copy obj
            this.temp.payCode = row.zoroCode
            this.dialogStatus = 'update'
            this.dialogFormVisible = true
            this.$nextTick(() => {
                this.$refs['dataForm'].clearValidate()
            })
        },
        updateData () {
            this.tempUpdateLoading = true
            const tempData = Object.assign({}, this.temp)
            updateGatewayBlacklist(tempData).then((response) => {
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

                deleteGatewayBlacklist(data).then((response) => {
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
