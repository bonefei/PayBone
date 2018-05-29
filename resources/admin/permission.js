import router from './router'
import store from './store'
import { Message } from 'element-ui'
import NProgress from 'nprogress' // Progress 进度条
import 'nprogress/nprogress.css'// Progress 进度条样式
import { getToken, removeToken } from '@/admin/utils/auth' // 验权

NProgress.configure({ showSpinner: false })// NProgress Configuration

// permissiom judge function
function hasPermission (roles, permissionRoles) {
    if (roles.indexOf('admin') >= 0) return true // admin permission passed directly
    if (!permissionRoles) return true
    return roles.some((role) => permissionRoles.indexOf(role) >= 0)
}

const whiteList = ['/adminlogin'] // 不重定向白名单

router.beforeEach((to, from, next) => {
    NProgress.start() // start progress bar
    if (getToken()) { // determine if there has token
        // has token
        if (to.path === '/adminlogin') {
            next({ path: '/' })
            NProgress.done() // if current page is dashboard will not trigger afterEach hook, so manually handle it
        } else {
            if (store.getters.roles === undefined) {
                removeToken()
                next({ path: '/adminlogin' })
            }
            if (store.getters.roles.length === 0) { // 判断当前用户是否已拉取完user_info信息
                store.dispatch('GetUserInfo').then((res) => { // 拉取user_info
                    if (res.data.Status === '200') {
                        const roles = res.data.Content.roles // note: roles must be a array! such as: ['editor','develop']
                        const menu = res.data.Content.menu
                        store.dispatch('GenerateRoutes', { roles, menu }).then(() => { // 根据roles权限生成可访问的路由表
                            router.addRoutes(store.getters.addRouters) // 动态添加可访问路由表
                            next({ ...to, replace: true }) // hack方法 确保addRoutes已完成 ,set the replace: true so the navigation will not leave a history record
                        })
                    } else {
                        removeToken()
                        Message.error(res.data.Content)
                        next({ path: '/adminlogin' })
                    }
                }).catch(() => {
                    store.dispatch('FedLogOut').then(() => {
                        Message.error('Verification failed, please adminlogin again')
                        next({ path: '/adminlogin' })
                    })
                })
            } else {
                // 没有动态改变权限的需求可直接next() 删除下方权限判断 ↓
                if (hasPermission(store.getters.roles, to.meta.roles)) {
                    next()//
                } else {
                    next({ path: '/401', replace: true, query: { noGoBack: true } })
                }
                // 可删 ↑
            }
        }
    } else {
        // has no token
        if (whiteList.indexOf(to.path) !== -1) { // 在免登录白名单，直接进入
            next()
        } else {
            next('/adminlogin') // 否则全部重定向到登录页
            NProgress.done() // if current page is login will not trigger afterEach hook, so manually handle it
        }
    }
})

router.afterEach(() => {
    NProgress.done() // 结束Progress
})
