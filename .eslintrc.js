// 官网：https://eslint.org/docs/user-guide/configuring
// 配置项中文说明：https://www.jianshu.com/p/22e6197e5cff

module.exports = {
    // 此项是用来告诉eslint找当前配置文件不能往父级查找
    root: true,
    // 此项是用来指定eslint解析器的，解析器必须符合规则，babel-eslint解析器是对babel解析器的包装使其与ESLint解析
    parser: 'babel-eslint',
    // 此项是用来指定javaScript语言类型和风格，sourceType用来指定js导入的方式，默认是script，此处设置为module，指某块导入方式
    parserOptions: {
        sourceType: 'module'
    },
    // 此项指定环境的全局变量，下面的配置指定为浏览器环境
    env: {
        browser: true,
        commonjs: true,
        es6: true,
    },
    // https://github.com/standard/standard/blob/master/docs/RULES-en.md
    // 此项是用来配置标准的js风格，就是说写代码的时候要规范的写，如果你使用vs-code我觉得应该可以避免出错
    extends: 'standard',
    // required to lint *.vue files
    // 此项是用来提供插件的，插件名称省略了eslint-plugin-，下面这个配置是用来规范html的
    plugins: [
        'html'
    ],
    // add your custom rules here
    // 下面这些rules是用来设置从插件来的规范代码的规则，使用必须去掉前缀eslint-plugin-
    // 主要有如下的设置规则，可以设置字符串也可以设置数字，两者效果一致
    // "off" -> 0 关闭规则
    // "warn" -> 1 开启警告规则
    // "error" -> 2 开启错误规则
    // 了解了上面这些，下面这些代码相信也看的明白了
    'rules': {
        // 要求箭头函数体使用大括号
        'arrow-parens': 2,
        // 强制 generator 函数中 * 号周围使用一致的空格
        'generator-star-spacing': [2, { "before": true, "after": true }],
        // 禁止使用debugger
        'no-debugger': process.env.NODE_ENV === 'production' ? 2 : 0,
        // 不允许空格和 tab 混合缩进
        "no-mixed-spaces-and-tabs": 2,
        // 缩进风格
        'indent': [2, 4],
        // 禁用var，用let和const代替
        "no-var": 2,
        // 强制分号之前和之后使用一致的空格
        "semi-spacing": 0,
        // 禁止出现令人困惑的多行表达式
        "no-unexpected-multiline": 2,
        // 要求使用 let 或 const 而不是 var
        "no-var": 0,
        // 箭头函数优先
        "prefer-arrow-callback": "warn",
        // 模板优先
        "prefer-template": "error",
        "no-unused-vars":0
    }
}
