import request from '@/admin/utils/request'

export function fetchList (query) {
    return request({
        url: '/merchantRuleList',
        method: 'get',
        params: query
    })
}

export function fetchDialogList (query) {
    return request({
        url: '/merchantRuleDialogList',
        method: 'get',
        params: query
    })
}

export function selectPayCodeOption (data) {
    return request({
        url: '/merchantRuleSelectPayCode',
        method: 'get',
        params: data
    })
}

export function selectPayNameOption (query) {
    return request({
        url: '/merchantRuleSelectPayName',
        method: 'get',
        params: query
    })
}

export function createMerchantRule (data) {
    return request({
        url: '/merchantRuleCreate',
        method: 'post',
        data
    })
}

export function createDialogMerchantRule (data) {
    return request({
        url: '/merchantRuleDialogCreate',
        method: 'post',
        data
    })
}

export function updateMerchantRule (data) {
    return request({
        url: '/merchantRuleUpdate',
        method: 'put',
        data
    })
}

export function updateMerchantDialogRule (data) {
    return request({
        url: '/merchantRuleDialogUpdate',
        method: 'put',
        data
    })
}

export function deleteMerchantRule (data) {
    return request({
        url: '/merchantRuleDelete',
        method: 'delete',
        data
    })
}

export function deleteDialogMerchantRule (data) {
    return request({
        url: '/merchantRuleDialogDelete',
        method: 'delete',
        data
    })
}

export function forbiddenMerchantRule (query) {
    return request({
        url: '/merchantRuleForbidden',
        method: 'put',
        params: query
    })
}

export function selectPayUserOption (query) {
    return request({
        url: '/merchantRuleSelectPayUser',
        method: 'get',
        params: query
    })
}
