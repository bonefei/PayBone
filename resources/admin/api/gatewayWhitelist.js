import request from '@/admin/utils/request'

export function fetchList (query) {
    return request({
        url: '/gatewayWhitelistList',
        method: 'get',
        params: query
    })
}

export function selectPayCodeOption (data) {
    return request({
        url: '/gatewayWhitelistSelectPayCode',
        method: 'get',
        params: data
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

export function selectPayUserOption (data) {
    return request({
        url: '/merchantRuleSelectPayUser',
        method: 'get',
        params: data
    })
}
