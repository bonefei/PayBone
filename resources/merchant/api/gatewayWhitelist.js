import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/gatewayWhitelistList',
        method: 'get',
        params: query
    })
}

export function selectPayCodeOption () {
    return request({
        url: '/gatewayWhitelistSelectPayCode',
        method: 'get'
    })
}

export function createGatewayWhitelist (data) {
    return request({
        url: '/gatewayWhitelistCreate',
        method: 'post',
        data
    })
}

export function updateGatewayWhitelist (data) {
    return request({
        url: '/gatewayWhitelistUpdate',
        method: 'put',
        data
    })
}

export function deleteGatewayWhitelist (data) {
    return request({
        url: '/gatewayWhitelistDelete',
        method: 'post',
        data
    })
}
