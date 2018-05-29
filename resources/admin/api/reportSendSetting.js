import request from '@/admin/utils/request'

export function fetchList (query) {
    return request({
        url: '/reportSendSettingList',
        method: 'get',
        params: query
    })
}

export function selectUserNo (data) {
    return request({
        url: '/selectUserNoOption',
        method: 'get',
        params: data
    })
}

export function userNameSelect (data) {
    return request({
        url: '/selectUserOption',
        method: 'get',
        data
    })
}

export function createReportSend (data) {
    return request({
        url: '/reportSendSettingCreate',
        method: 'put',
        params: data
    })
}

export function updateReportSend (data) {
    return request({
        url: '/updateReportSend',
        method: 'put',
        params: data
    })
}

export function deleteReportSend (data) {
    return request({
        url: '/reportSendDelete',
        method: 'post',
        params: data
    })
}
