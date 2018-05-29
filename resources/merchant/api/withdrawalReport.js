import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/withdrawalReportList',
        method: 'get',
        params: query
    })
}

export function updatewithdrawalReportStatus (data) {
    return request({
        url: '/withdrawalReportStatusUpdate',
        method: 'put',
        data
    })
}

export function fetchCapital (query) {
    return request({
        url: '/withdrawalReportCapital',
        method: 'get',
        params: query
    })
}
