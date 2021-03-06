import { asyncRouterMap, constantRouterMap } from '@/admin/router'

/**
 * 通过meta.role判断是否与当前用户权限匹配
 * @param roles
 * @param route
 */
function hasPermission (roles, route, menus) {
    // console.log(roles, route)
    // if (route.meta && route.meta.roles) {
    //     return roles.some((role) => route.meta.roles.indexOf(role) >= 0)
    // } else {
    //     return true
    // }
    // console.log(menus.some((menu) => {
    //     console.log(menu)
    // }))
    return menus.some((menu) => {
        if (menu.name.indexOf(route.path) >= 0) {
            return menu
        }
    })
}

/**
 * 递归过滤异步路由表，返回符合用户角色权限的路由表
 * @param asyncRouterMap
 * @param roles
 */
function filterAsyncRouter (asyncRouterMap, roles, menu) {
    const accessedRouters = asyncRouterMap.filter((route) => {
        if (hasPermission(roles, route, menu)) {
            if (route.children && route.children.length) {
                route.children = filterAsyncRouter(route.children, roles, menu)
            }
            return true
        }
        return false
    })
    return accessedRouters
}

const permission = {
    state: {
        routers: constantRouterMap,
        addRouters: []
    },
    mutations: {
        SET_ROUTERS: (state, routers) => {
            state.addRouters = routers
            state.routers = constantRouterMap.concat(routers)
        }
    },
    actions: {
        GenerateRoutes ({ commit }, data) {
            return new Promise((resolve) => {
                const { roles } = data
                let accessedRouters
                if (roles.indexOf('admin') >= 0) {
                    accessedRouters = asyncRouterMap
                } else {
                    accessedRouters = filterAsyncRouter(asyncRouterMap, roles, data.menu)
                }
                commit('SET_ROUTERS', accessedRouters)
                resolve()
            })
        }
    }
}

export default permission
