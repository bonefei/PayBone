<template>
    <div class="app-container calendar-list-container">
        <el-row class="panel-group" :gutter="40">
            <el-col :xs="12" :sm="12" :lg="12" class="card-panel-col" v-loading="capitalLoading">
                <div class="card-panel">
                    <div class="card-panel-icon-wrapper icon-money">
                        <svg-icon icon-class="money" class-name="card-panel-icon" />
                    </div>
                    <div class="card-panel-description">
                        <div class="card-panel-text">入金金额</div>
                        <count-to class="card-panel-num" :startVal="0" :endVal="receivedNum" :duration="3200" :decimals="2"></count-to>
                    </div>
                </div>
            </el-col>

            <el-col :xs="12" :sm="12" :lg="12" class="card-panel-col" v-loading="capitalLoading">
                <div class="card-panel">
                    <div class="card-panel-icon-wrapper icon-shoppingCard">
                        <svg-icon icon-class="shoppingCard" class-name="card-panel-icon" />
                    </div>
                    <div class="card-panel-description">
                        <div class="card-panel-text">出金金额</div>
                        <count-to class="card-panel-num" :startVal="0" :endVal="unreceivedNum" :duration="3200" :decimals="2"></count-to>
                    </div>
                </div>
            </el-col>
        </el-row>

        <div class="filter-container">
            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="商户号" v-model="listQuery.payName"></el-input>

            <el-input @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="订单号" v-model="listQuery.payNo"></el-input>

            <el-select @keyup.enter.native="handleFilter" style="width: 120px;" class="filter-item" placeholder="请选择" v-model="listQuery.payType">
                <el-option label="入金" value="1"></el-option>
                <el-option label="出金" value="2"></el-option>
                <el-option label="全部" value=""></el-option>
            </el-select>

            <el-date-picker v-model="listQuery.payCreateTime" type="datetimerange" class="filter-item" range-separator="至" start-placeholder="订单开始日期" 
            :picker-options="pickerOptions2" end-placeholder="订单结束日期" unlink-panels value-format="yyyy-MM-dd HH:mm:ss">
            </el-date-picker>

            <el-button class="filter-item" type="primary" v-waves icon="el-icon-search" @click="handleFilter">{{$t('table.search')}}</el-button>

            <el-button class="filter-item" type="primary" :loading="downloadLoading" v-waves icon="el-icon-download" @click="handleDownload">{{$t('table.export')}}</el-button>
        </div>

        <el-table :key='tableKey' :data="list" v-loading="listLoading" element-loading-text="给我一点时间" border fit highlight-current-row
        style="width: 100%">
            <el-table-column align="center" label="商户号">
                <template slot-scope="scope">
                    <span>{{scope.row.user_no}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="动作">
                <template slot-scope="scope">
                    <span v-if="scope.row.action == '1'">入金</span>
                    <span v-else>出金</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="余额(RMB)">
                <template slot-scope="scope">
                    <span :class="{amount_color_red: scope.row.peybalance > 0, amount_color_green: scope.row.peybalance < 0}">{{scope.row.peybalance}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="金额(RMB)">
                <template slot-scope="scope">
                    <span :class="{amount_color_red: scope.row.action == 1, amount_color_green: scope.row.action == 2}">{{scope.row.payamount}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="手续费(RMB)">
                <template slot-scope="scope">
                    <span>{{scope.row.peyfee}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="通道">
                <template slot-scope="scope">
                    <span>{{scope.row.payname}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="订单号" width="250">
                <template slot-scope="scope">
                    <span>{{scope.row.payno}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="订单时间">
                <template slot-scope="scope">
                    <span>{{scope.row.create_time}}</span>
                </template>
            </el-table-column>

            <el-table-column align="center" label="完成时间">
                <template slot-scope="scope">
                    <span>{{scope.row.complete_time}}</span>
                </template>
            </el-table-column>
        </el-table>

        <div class="pagination-container">
            <el-pagination background @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="listQuery.page"
                :page-sizes="[10, 20, 30, 50, total]" :page-size="listQuery.limit" layout="total, sizes, prev, pager, next, jumper" :total="total">
            </el-pagination>
        </div>
    </div>
</template>

<script>
import { fetchList, fetchCapital } from '@/merchant/api/businessBalance'
import waves from '@/merchant/directive/waves' // 水波纹指令
import { parseTime } from '@/merchant/utils'
import CountTo from 'vue-count-to'

export default {
    name: 'complexTable',
    directives: {
        waves
    },
    components: {
        CountTo
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
                payName: undefined,
                payNo: undefined,
                payType: undefined,
                payCreateTime: undefined
            },
            downloadLoading: false,
            receivedNum: 0.00,
            unreceivedNum: 0.00,
            capitalLoading: true,
            pickerOptions2: {
                shortcuts: [{
                    text: '最近一天',
                    onClick (picker) {
                        const end = new Date()
                        const start = new Date()
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 1)
                        picker.$emit('pick', [start, end])
                    }
                }, {
                    text: '最近一周',
                    onClick (picker) {
                        const end = new Date()
                        const start = new Date()
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
                        picker.$emit('pick', [start, end])
                    }
                }, {
                    text: '最近一个月',
                    onClick (picker) {
                        const end = new Date()
                        const start = new Date()
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
                        picker.$emit('pick', [start, end])
                    }
                }, {
                    text: '最近三个月',
                    onClick (picker) {
                        const end = new Date()
                        const start = new Date()
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90)
                        picker.$emit('pick', [start, end])
                    }
                }]
            }
        }
    },
    created () {
        this.getList()
        this.getCapital()
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
        getCapital () {
            this.capitalLoading = true

            fetchCapital(this.listQuery).then((response) => {
                this.capitalLoading = false

                if (response.data.Status !== 200) {
                    this.$message({
                        message: '信息获取错误请稍后再试',
                        type: 'error'
                    })
                } else {
                    this.receivedNum = Number(response.data.Content.receivedNum)
                    this.unreceivedNum = Number(response.data.Content.unreceivedNum)
                }
            })
        },
        handleFilter () {
            this.listQuery.page = 1
            this.getList()
            this.getCapital()
        },
        handleSizeChange (val) {
            this.listQuery.limit = val
            this.getList()
        },
        handleCurrentChange (val) {
            this.listQuery.page = val
            this.getList()
        },
        handleDownload () {
            this.downloadLoading = true

            const { exportJsonToExcel } = require('@/admin/vendor/Export2Excel')

            const tHeader = ['商家', '余额', '金额', '方式', '订单号', '订单时间', '完成时间']
            const filterVal = ['merchant_name', 'now_balance', 'payer_pay_amount', 'pay_type_name', 'merchant_order_no', 'create_time', 'complete_time']
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
        },
        sumAmount (one, two) {
            return Number(Number(one) + Number(two)).toFixed(2)
        }
    }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
    .panel-group {
        margin-top: 18px;
        .card-panel-col{
            margin-bottom: 32px;
        }
        .card-panel {
            height: 108px;
            cursor: pointer;
            font-size: 12px;
            position: relative;
            overflow: hidden;
            color: #666;
            background: #fff;
            box-shadow: 4px 4px 40px rgba(0, 0, 0, .05);
            border-color: rgba(0, 0, 0, .05);
            &:hover {
                .card-panel-icon-wrapper {
                    color: #fff;
                }
                .icon-people {
                    background: #40c9c6;
                }
                .icon-message {
                    background: #36a3f7;
                }
                .icon-money {
                    background: #f4516c;
                }
                .icon-shoppingCard {
                    background: #34bfa3
                }
            }
            .icon-people {
                color: #40c9c6;
            }
            .icon-message {
                color: #36a3f7;
            }
            .icon-money {
                color: #f4516c;
            }
            .icon-shoppingCard {
                color: #34bfa3
            }
            .card-panel-icon-wrapper {
                float: left;
                margin: 14px 0 0 14px;
                padding: 16px;
                transition: all 0.38s ease-out;
                border-radius: 6px;
            }
            .card-panel-icon {
                float: left;
                font-size: 48px;
            }
            .card-panel-description {
                float: right;
                font-weight: bold;
                margin: 26px;
                margin-left: 0px;
                .card-panel-text {
                    line-height: 18px;
                    color: rgba(0, 0, 0, 0.45);
                    font-size: 16px;
                    margin-bottom: 12px;
                }
                .card-panel-num {
                    font-size: 20px;
                }
            }
        }
    }
    .amount_color_red {
        color: red
    }
    .amount_color_green {
        color: green
    }
</style>
