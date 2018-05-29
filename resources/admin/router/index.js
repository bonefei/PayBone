import Vue from 'vue'
import Router from 'vue-router'

/**
 * [异步组件加载函数]
 * @param  {[string]} name [组件名称]
 * @return {[promise]}      [组件]
 */
const asyncComponent = (name) => {
    return (resolve) => require([`@/admin/views/${name}`], resolve)
}

let Layout = asyncComponent('layout/Layout')

export const constantRouterMap = [{
    path: '/adminlogin',
    component: asyncComponent('adminlogin/index'),
    hidden: true
}, {
    path: '',
    component: Layout,
    redirect: 'dashboard',
    children: [
        {
            path: 'dashboard',
            component: asyncComponent('dashboard/index'),
            name: 'dashboard',
            meta: {
                title: 'dashboard',
                icon: 'dashboard',
                noCache: true
            }
        }
    ]
}]

Vue.use(Router)

export default new Router({
    mode: 'history',
    base: '/admin/',
    routes: constantRouterMap
})

export const asyncRouterMap = [{
    path: '/merchant',
    component: Layout,
    redirect: 'noredirect',
    name: 'merchant',
    meta: {
        title: 'merchant',
        icon: 'example'
    },
    children: [
        {
            path: 'merchants',
            component: asyncComponent('merchant/merchants'),
            name: 'merchants',
            meta: {
                title: 'merchants'
            }
        },
        {
            path: 'merchantRule',
            component: asyncComponent('merchant/rule'),
            name: 'merchantRule',
            meta: {
                title: 'merchantRule'
            }
        }
    ]
}, {
    path: '/aisle',
    component: Layout,
    redirect: 'noredirect',
    name: 'aisle',
    meta: {
        title: 'aisle',
        icon: 'form'
    },
    children: [
        {
            path: 'aisles',
            component: asyncComponent('aisle/aisles'),
            name: 'aisles',
            meta: {
                title: 'aisles'
            }
        },
        {
            path: 'aisleTemplate',
            component: asyncComponent('aisle/template'),
            name: 'aisleTemplate',
            meta: {
                title: 'aisleTemplate'
            }
        }
    ]
}, {
    path: '/report',
    component: Layout,
    redirect: 'noredirect',
    name: 'report',
    meta: {
        title: 'report',
        icon: 'documentation'
    },
    children: [
        {
            path: 'businessBalance',
            component: asyncComponent('report/businessBalance'),
            name: 'businessBalance',
            meta: {
                title: 'businessBalance'
            }
        },
        {
            path: 'depositReport',
            component: asyncComponent('report/depositReport'),
            name: 'depositReport',
            meta: {
                title: 'depositReport'
            }
        },
        {
            path: 'withdrawalReport',
            component: asyncComponent('report/withdrawalReport'),
            name: 'withdrawalReport',
            meta: {
                title: 'withdrawalReport'
            }
        },
        {
            path: 'applyWithdrawal',
            component: asyncComponent('report/applyWithdrawal'),
            name: 'applyWithdrawal',
            meta: {
                title: 'applyWithdrawal'
            }
        },
        {
            path: 'reportSendSetting',
            component: asyncComponent('report/reportSendSetting'),
            name: 'reportSendSetting',
            meta: {
                title: 'reportSendSetting'
            }
        }
    ]
}, {
    path: '/system',
    component: Layout,
    redirect: 'noredirect',
    name: 'system',
    meta: {
        title: 'system',
        icon: 'international'
    },
    children: [
        {
            path: 'gatewayBlacklist',
            component: asyncComponent('system/gatewayBlacklist'),
            name: 'gatewayBlacklist',
            meta: {
                title: 'gatewayBlacklist'
            }
        },
        {
            path: 'gatewayWhitelist',
            component: asyncComponent('system/gatewayWhitelist'),
            name: 'gatewayWhitelist',
            meta: {
                title: 'gatewayWhitelist'
            }
        }
    ]
}, {
    path: '/auth',
    component: Layout,
    redirect: 'noredirect',
    name: 'auth',
    meta: {
        title: 'auth',
        icon: 'peoples'
    },
    children: [
        {
            path: 'role',
            component: asyncComponent('auth/role'),
            name: 'auth',
            meta: {
                title: 'auth',
                icon: 'peoples'
            }
        }
    ]
}]
