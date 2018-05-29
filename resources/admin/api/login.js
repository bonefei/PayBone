import request from '@/admin/utils/request'

export function loginByUsername (username, password) {
    const data = {
        username,
        password
    }

    return request({
        url: '/adminLogin',
        method: 'post',
        data
    })
}

export function logout () {
    return request({
        url: '/adminLogout',
        method: 'post'
    })
}

export function getUserInfo (token) {
    return request({
        url: '/adminUserInfo',
        method: 'get'
    })
}

export function fetchCapitalInfo (token) {
    return request({
        url: '/adminCapitalInfo',
        method: 'get'
    })
}

export function fetchLineChartInfo (token) {
    return request({
        url: '/adminLineChartInfo',
        method: 'get'
    })
}
