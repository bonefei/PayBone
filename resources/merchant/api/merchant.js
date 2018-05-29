import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/merchantList',
        method: 'get',
        params: query
    })
}

export function createMerchant (data) {
    return request({
        url: '/merchantCreate',
        method: 'post',
        data
    })
}

export function updateMerchant (data) {
    return request({
        url: '/merchantUpdate',
        method: 'post',
        data
    })
}

export function downloadMerchant (data) {
    return request({
        url: '/merchantDownload',
        method: 'post',
        data
    })
}

export function userMerchantSelect (query) {
    return request({
        url: '/selectUserMerchant',
        method: 'get',
        params: query
    })
}
