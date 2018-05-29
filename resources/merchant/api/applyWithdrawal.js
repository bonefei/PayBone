import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/merchantWithdrawalList',
        method: 'get',
        params: query
    })
}

export function merchantWithdrawalApply (data) {
    return request({
        url: '/applyMerchantWithdrawal',
        method: 'post',
        data
    })
}

export function payAisleSelect (query) {
    return request({
        url: '/selectPayAisle',
        method: 'get',
        params: query
    })
}

export function payWayCodeSelect (query) {
    return request({
        url: '/selectPayWayCode',
        method: 'get',
        query
    })
}

export function uploadBatchExcel (data) {
    return request({
        url: '/uploadBatchExcel',
        method: 'post',
        data
    })
}
