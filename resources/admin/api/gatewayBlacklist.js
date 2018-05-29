import request from '@/admin/utils/request'

export function fetchList (query) {
    return request({
        url: '/gatewayBlacklistList',
        method: 'get',
        params: query
    })
}

export function selectPayCodeOption (data) {
    return request({
        url: '/gatewayBlacklistSelectPayCode',
        method: 'get',
        params: data
    })
}

export function createGatewayBlacklist (data) {
    return request({
        url: '/gatewayBlacklistCreate',
        method: 'post',
        data
    })
}

export function updateGatewayBlacklist (data) {
    return request({
        url: '/gatewayBlacklistUpdate',
        method: 'put',
        data
    })
}

export function deleteGatewayBlacklist (data) {
    return request({
        url: '/gatewayBlacklistDelete',
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
