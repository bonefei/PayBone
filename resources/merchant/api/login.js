import request from '@/merchant/utils/request'

export function loginByUsername (username, password) {
    const data = {
        username,
        password
    }

    return request({
        url: '/login',
        method: 'post',
        data
    })
}

export function logout () {
    return request({
        url: '/merchantLogout',
        method: 'post'
    })
}

export function getUserInfo (token) {
    return request({
        url: '/merchantInfo',
        method: 'get'
    })
}

export function fetchCapitalInfo (token) {
    return request({
        url: '/merchantCapitalInfo',
        method: 'get'
    })
}

export function fetchLineChartInfo (token) {
    return request({
        url: '/merchantLineChartInfo',
        method: 'get'
    })
}
