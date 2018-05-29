import hmacSHA512 from 'crypto-js/hmac-sha512'

/**
 * [加密]
 * @param  {[string]} pwd [密码]
 * @return {[string]}     [加密后的密码]
 */
export function encryptionPwd (pwd) {
    return hmacSHA512(pwd, 'my-qq').toString().substr(20, 30)
}

/**
 * [字符串加密]
 * @param  {[string]} str [字符串]
 * @return {[string]}     [加密后的字符串]
 */
export function encryptionPwdStr (str) {
    return hmacSHA512(str, 'my-qq').toString().substr(20, 30)
}
