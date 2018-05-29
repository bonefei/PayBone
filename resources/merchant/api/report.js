import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/reportList',
        method: 'get',
        params: query
    })
}

export function fetchCapital (query) {
    return request({
        url: '/selectCapital',
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

export function updateMerchantStatus (data) {
    return request({
        url: '/merchantStatusUpdate',
        method: 'put',
        data
    })
}
