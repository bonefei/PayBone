/**
 * Created by jiachenpan on 16/11/18.
 */
export function isvalidUsername (str) {
    const validMap = ['test2', 'editor']
    return validMap.indexOf(str.trim()) >= 0
}

export function isvalidUserno (str) {
    const validMap = ['Zoro1000002', 'Zoro1000003', 'Zoro1000004', 'editor']
    return validMap.indexOf(str.trim()) >= 0
}

/**
 * 合法uri
 */
export function validateURL (textval) {
    const urlregex = /^(https?|ftp):\/\/([a-zA-Z0-9.-]+(:[a-zA-Z0-9.&%$-]+)*@)*((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}|([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(:[0-9]+)*(\/($|[a-zA-Z0-9.,?'\\+&%$#=~_-]+))*$/
    return urlregex.test(textval)
}

/**
 * 小写字母
 */
export function validateLowerCase (str) {
    const reg = /^[a-z]+$/
    return reg.test(str)
}

/**
 * 大写字母
 */
export function validateUpperCase (str) {
    const reg = /^[A-Z]+$/
    return reg.test(str)
}

/**
 * 大小写字母
 */
export function validatAlphabets (str) {
    const reg = /^[A-Za-z]+$/
    return reg.test(str)
}

/**
 * validate email
 * @param email
 * @returns {boolean}
 */
export function validateEmail (email) {
    const re = /^(([^<>()\\[\]\\.,;:\s@"]+(\.[^<>()\\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    return re.test(email)
}

/**
 * 返回快捷时间信息
*/
export function getDateStr (email) {
    const re = /^(([^<>()\\[\]\\.,;:\s@"]+(\.[^<>()\\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    return re.test(email)
}