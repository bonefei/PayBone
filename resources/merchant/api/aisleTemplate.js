import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/aisleTemplateList',
        method: 'get',
        params: query
    })
}

export function createAisleTemplate (data) {
    return request({
        url: '/aisleTemplateCreate',
        method: 'post',
        data
    })
}

export function updateAisleTemplate (data) {
    return request({
        url: '/aisleTemplateUpdate',
        method: 'put',
        data
    })
}
