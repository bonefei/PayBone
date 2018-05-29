import request from '@/admin/utils/request'

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

export function selectPayUserOption (data) {
    return request({
        url: '/merchantRuleSelectPayUser',
        method: 'get',
        params: data
    })
}

export function payAisleVersion (data) {
    return request({
        url: '/getPayAisleVersion',
        method: 'get',
        params: data
    })
}

export function uploadBatchExcel (data) {
    return request({
        url: '/uploadBatchExcel',
        method: 'post',
        data
    })
}
