<template>
    <div :class="className" :style="{height:height,width:width}" v-loading="tempLineLoading" element-loading-text="玩命加载中"></div>
</template>

<script>
    import { fetchLineChartInfo } from '@/merchant/api/login'
    import echarts from 'echarts'
    require('echarts/theme/macarons') // echarts theme

    export default {
        props: {
            className: {
                type: String,
                default: 'chart'
            },
            width: {
                type: String,
                default: '100%'
            },
            height: {
                type: String,
                default: '350px'
            }
        },
        data () {
            return {
                chart: null,
                lineData: {
                    data: [],
                    time: []
                },
                tempLineLoading: false
            }
        },
        created () {
            this.getLineChartInfo()
        },
        methods: {
            getLineChartInfo () {
                this.tempLineLoading = true
                fetchLineChartInfo().then((response) => {
                    this.tempLineLoading = false

                    if (response.data.Status === '200') {
                        this.lineData.data = response.data.Content.data
                        this.lineData.time = response.data.Content.time

                        this.initChart()
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
            setOptions ({ expectedData, actualData } = {}) {
                this.chart.setOption({
                    xAxis: {
                        data: this.lineData.time,
                        boundaryGap: false,
                        axisTick: {
                            show: false
                        }
                    },
                    grid: {
                        left: 10,
                        right: 10,
                        bottom: 20,
                        top: 30,
                        containLabel: true
                    },
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'cross'
                        },
                        padding: [0, 10]
                    },
                    yAxis: {
                        axisTick: {
                            show: false
                        }
                    },
                    legend: {
                        data: ['入金', '出金']
                    },
                    series: [{
                        name: '入金',
                        itemStyle: {
                            normal: {
                                color: '#FF005A',
                                lineStyle: {
                                    color: '#FF005A',
                                    width: 2
                                }
                            }
                        },
                        smooth: true,
                        type: 'line',
                        data: this.lineData.data,
                        animationDuration: 2800,
                        animationEasing: 'cubicInOut'
                    }
                    // {
                    //     name: '出金',
                    //     smooth: true,
                    //     type: 'line',
                    //     itemStyle: {
                    //         normal: {
                    //             color: '#3888fa',
                    //             lineStyle: {
                    //                 color: '#3888fa',
                    //                 width: 2
                    //             },
                    //             areaStyle: {
                    //                 color: '#f3f8ff'
                    //             }
                    //         }
                    //     },
                    //     data: actualData,
                    //     animationDuration: 2800,
                    //     animationEasing: 'quadraticOut'
                    // }
                    ]
                })
            },
            initChart () {
                this.chart = echarts.init(this.$el, 'macarons')
                this.setOptions(this.lineData)
            }
        }
    }
</script>
