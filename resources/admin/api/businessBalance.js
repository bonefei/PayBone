import request from '@/admin/utils/request'

export function fetchList (query) {
    return request({
        url: '/businessBalanceList',
        method: 'get',
        params: query
    })
}

export function fetchCapital (query) {
    return request({
        url: '/businessBalanceCapital',
        method: 'get',
        params: query
    })
}
