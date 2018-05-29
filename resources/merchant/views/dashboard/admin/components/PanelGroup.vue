<template>
    <el-row class="panel-group" :gutter="40">
        <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col" v-loading="capitalInfoLoading">
            <div class='card-panel'>
                <div class="card-panel-icon-wrapper icon-people">
                    <svg-icon icon-class="peoples" class-name="card-panel-icon" />
                </div>
                <div class="card-panel-description">
                    <div class="card-panel-text">当天入金量</div>
                    <count-to class="card-panel-num" :startVal="0" :endVal="listInfo.todayDeposit" :duration="2000" :decimals="2"></count-to>
                </div>
            </div>
        </el-col>
        <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col" v-loading="capitalInfoLoading">
            <div class="card-panel">
                <div class="card-panel-icon-wrapper icon-message">
                    <svg-icon icon-class="message" class-name="card-panel-icon" />
                </div>
                <div class="card-panel-description">
                    <div class="card-panel-text">当天交易量</div>
                    <count-to class="card-panel-num" :startVal="0" :endVal="listInfo.todayTicket" :duration="2000"></count-to>
                </div>
            </div>
        </el-col>
        <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col" v-loading="capitalInfoLoading">
            <div class="card-panel">
                <div class="card-panel-icon-wrapper icon-money">
                    <svg-icon icon-class="money" class-name="card-panel-icon" />
                </div>
                <div class="card-panel-description">
                    <div class="card-panel-text">当月入金量</div>
                    <count-to class="card-panel-num" :startVal="0" :endVal="listInfo.monthDeposit" :duration="2000" :decimals="2"></count-to>
                </div>
            </div>
        </el-col>
        <el-col :xs="12" :sm="12" :lg="6" class="card-panel-col" v-loading="capitalInfoLoading">
            <div class="card-panel">
                <div class="card-panel-icon-wrapper icon-shoppingCard">
                    <svg-icon icon-class="shoppingCard" class-name="card-panel-icon" />
                </div>
                <div class="card-panel-description">
                    <div class="card-panel-text">当月交易量</div>
                    <count-to class="card-panel-num" :startVal="0" :endVal="listInfo.monthTicket" :duration="2000"></count-to>
                </div>
            </div>
        </el-col>
    </el-row>
</template>

<script>
import { fetchCapitalInfo } from '@/merchant/api/login'
import CountTo from 'vue-count-to'

export default {
    components: {
        CountTo
    },
    data () {
        return {
            capitalInfoLoading: true,
            listInfo: {
                todayDeposit: 0.0,
                todayTicket: 0,
                monthDeposit: 0.0,
                monthTicket: 0
            }
        }
    },
    created () {
        this.getMerchantInfo()
    },
    methods: {
        getMerchantInfo () {
            this.capitalInfoLoading = true

            fetchCapitalInfo().then((response) => {
                this.capitalInfoLoading = false

                if (response.data.Status === '200') {
                    this.listInfo.todayDeposit = Number(response.data.Content.todayDeposit)
                    this.listInfo.todayTicket = Number(response.data.Content.todayTicket)
                    this.listInfo.monthDeposit = Number(response.data.Content.monthDeposit)
                    this.listInfo.monthTicket = Number(response.data.Content.monthTicket)
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
</style>
