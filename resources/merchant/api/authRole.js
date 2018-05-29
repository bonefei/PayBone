import request from '@/merchant/utils/request'

export function fetchList (query) {
    return request({
        url: '/SelectRoleList',
        method: 'get',
        params: query
    })
}

export function createRoleData (query) {
    return request({
        url: '/createRole',
        method: 'put',
        params: query
    })
}

export function deleteRoleInfo (data) {
    return request({
        url: '/deleteRole',
        method: 'post',
        params: data
    })
}

export function modifyRoleData (query) {
    return request({
        url: '/modifyRole',
        method: 'put',
        params: query
    })
}

export function roleAuthSelect (data) {
    return request({
        url: '/selectRoleAuth',
        method: 'post',
        params: data
    })
}

export function modifyRoleAuth (data) {
    return request({
        url: '/roleAuthModify',
        method: 'post',
        params: data
    })
}

export function userListSelect (data) {
    return request({
        url: '/selectUserList',
        method: 'post',
        params: data
    })
}

export function roleIdSelect (data) {
    return request({
        url: '/selectRoleId',
        method: 'get',
        data
    })
}

export function modifyUserRoleAuth (data) {
    return request({
        url: '/userRoleAuthModify',
        method: 'put',
        params: data
    })
}

export function createUserData (data) {
    return request({
        url: '/userDataCreate',
        method: 'put',
        params: data
    })
}
