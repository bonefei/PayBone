import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/aisleList',
        method: 'get',
        params: query
    })
}

export function createAisle (data) {
    return request({
        url: '/aisleCreate',
        method: 'post',
        data
    })
}

export function updateAisle (data) {
    return request({
        url: '/aisleUpdate',
        method: 'put',
        data
    })
}

export function selectAislePayCode (data) {
    return request({
        url: '/aisleSelectPayCode',
        method: 'get',
        data
    })
}

export function selectAislePayMerCode (data) {
    return request({
        url: '/aisleSelectPayMerCode',
        method: 'get',
        data
    })
}

export function updateMerchantRemark (data) {
    return request({
        url: '/updateMerchantRemark',
        method: 'post',
        params: data
    })
}
