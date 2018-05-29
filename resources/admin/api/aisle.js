import request from '@/admin/utils/request'

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
        params: data
    })
}

export function selectAislePayMerCode (data) {
    return request({
        url: '/aisleSelectPayMerCode',
        method: 'get',
        data
    })
}

export function uploadCertificate (data) {
    return request({
        url: '/uploadMerchantCertificate',
        method: 'post',
        data
    })
}

export function certificateAisleSelect (data) {
    return request({
        url: '/selectCertificateAisle',
        method: 'post',
        data
    })
}

export function modifyAisleFromUrl (data) {
    return request({
        url: '/aisleFromUrlModify',
        method: 'post',
        data
    })
}

export function selectPayUserOption (query) {
    return request({
        url: '/merchantRuleSelectPayUser',
        method: 'get',
        params: query
    })
}

export function modifyAisle (query) {
    return request({
        url: '/modifyAisle',
        method: 'post',
        params: query
    })
}
