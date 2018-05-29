import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/depositReportList',
        method: 'get',
        params: query
    })
}

export function updateDepositReportStatus (data) {
    return request({
        url: '/depositReportStatusUpdate',
        method: 'put',
        data
    })
}

export function fetchCapital (query) {
    return request({
        url: '/depositReportCapital',
        method: 'get',
        params: query
    })
}
