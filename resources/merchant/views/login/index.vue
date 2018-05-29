<template>
    <div class="login-container">
        <el-form class="login-form" autoComplete="on" :model="merchantLoginForm" :rules="adminLoginRules" ref="merchantLoginForm" label-position="left">
            <div class="title-container">
                <h3 class="title">{{$t('login.title')}}</h3>
                <!-- <lang-select class="set-language"></lang-select> -->
            </div>

            <el-form-item prop="username">
                <span class="svg-container svg-container_login">
                    <svg-icon icon-class="user" />
                </span>

                <el-input name="username" type="text" v-model="merchantLoginForm.username" autoComplete="on" placeholder="商户用户名" />
            </el-form-item>

            <el-form-item prop="password">
                <span class="svg-container">
                    <svg-icon icon-class="password" />
                </span>

                <el-input name="password" :type="passwordType" @keyup.enter.native="handleLogin" v-model="merchantLoginForm.password" autoComplete="on" placeholder="商户密码" />

                <span class="show-pwd" @click="showPwd">
                    <svg-icon icon-class="eye" />
                </span>
            </el-form-item>

            <el-button type="primary" style="width:100%;margin-bottom:30px;" :loading="loading" @click.native.prevent="handleLogin">{{$t('login.logIn')}}</el-button>
        </el-form>

        <el-dialog :title="$t('login.thirdparty')" :visible.sync="showDialog" append-to-body>
            {{$t('login.thirdpartyTips')}}
            <br/>
            <br/>
            <br/>
            <social-sign />
        </el-dialog>
    </div>
</template>

<script>
    // import {
    //     // isvalidUsername,
    //     isvalidUserno
    // } from '@/merchant/common/js/validate'
    import LangSelect from '@/merchant/components/LangSelect'

    export default {
        name: 'login',
        components: { LangSelect },
        data () {
            // const validateUsername = (rule, value, callback) => {
            //     if (!isvalidUsername(value)) {
            //         callback(new Error('请输入正确的商户用户名'))
            //     } else {
            //         callback()
            //     }
            // }
            // const validateUserno = (rule, value, callback) => {
            //     if (!isvalidUserno(value)) {
            //         callback(new Error('请输入正确的商户号'))
            //     } else {
            //         callback()
            //     }
            // }

            const validatePassword = (rule, value, callback) => {
                if (value.length < 6) {
                    callback(new Error('密码不能少于6位'))
                } else {
                    callback()
                }
            }

            return {
                merchantLoginForm: {
                    username: '',
                    password: ''
                },
                adminLoginRules: {
                    username: [{ required: true, message: '请输入商户用户名', trigger: 'blur' }],
                    password: [{ required: true, trigger: 'blur', validator: validatePassword }]
                },
                passwordType: 'password',
                loading: false,
                showDialog: false
            }
        },
        methods: {
            showPwd () {
                if (this.passwordType === 'password') {
                    this.passwordType = ''
                } else {
                    this.passwordType = 'password'
                }
            },
            handleLogin () {
                this.$refs.merchantLoginForm.validate((valid) => {
                    if (valid) {
                        this.loading = true

                        this.$store.dispatch('LoginByUsername', this.merchantLoginForm).then(() => {
                            this.loading = false
                            this.$router.push({
                                path: '/'
                            })
                        }).catch(() => {
                            this.loading = false
                        })
                    } else {
                        return false
                    }
                })
            }
        }
    }
</script>

<style rel="stylesheet/scss" lang="scss">
    $bg: #2d3a4b;
    $light_gray: #eee;

    /* reset element-ui css */
    .login-container {
        .el-input {
            display: inline-block;
            height: 47px;
            width: 85%;
            input {
                background: transparent;
                border: 0px;
                -webkit-appearance: none;
                border-radius: 0px;
                padding: 12px 5px 12px 15px;
                color: $light_gray;
                height: 47px;
                &:-webkit-autofill {
                    -webkit-box-shadow: 0 0 0px 1000px $bg inset !important;
                    -webkit-text-fill-color: #fff !important;
                }
            }
        }
        .el-form-item {
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            color: #454545;
        }
    }
</style>

<style rel="stylesheet/scss" lang="scss" scoped>
    $bg: #2d3a4b;
    $dark_gray: #889aa4;
    $light_gray: #eee;

    .login-container {
        position: fixed;
        height: 100%;
        width: 100%;
        background-color: $bg;
        .login-form {
            position: absolute;
            left: 0;
            right: 0;
            width: 520px;
            padding: 35px 35px 15px 35px;
            margin: 120px auto;
        }
        .tips {
            font-size: 14px;
            color: #fff;
            margin-bottom: 10px;
            span {
                &:first-of-type {
                    margin-right: 16px;
                }
            }
        }
        .svg-container {
            padding: 6px 5px 6px 15px;
            color: $dark_gray;
            vertical-align: middle;
            width: 30px;
            display: inline-block;
            &_login {
                font-size: 20px;
            }
        }
        .title-container {
            position: relative;
            .title {
                font-size: 26px;
                font-weight: 400;
                color: $light_gray;
                margin: 0px auto 40px auto;
                text-align: center;
                font-weight: bold;
            }
            .set-language {
                color: #fff;
                position: absolute;
                top: 5px;
                right: 0px;
            }
        }
        .show-pwd {
            position: absolute;
            right: 10px;
            top: 7px;
            font-size: 16px;
            color: $dark_gray;
            cursor: pointer;
            user-select: none;
        }
        .thirdparty-button {
            position: absolute;
            right: 35px;
            bottom: 28px;
        }
    }
</style>
