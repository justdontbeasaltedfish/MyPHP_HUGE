<?php

/**
 * Class Filter
 * 类过滤器
 *
 * This is the place to put filters, usually methods that cleans, sorts and, well, filters stuff.
 * 这是放置过滤器,通常的方法清洗,排序,过滤材料。
 */
class Filter
{
    /**
     * The XSS filter: This simply removes "code" from any data, used to prevent Cross-Site Scripting Attacks.
     * XSS过滤器:这只是从任何数据删除“代码”,用来防止跨站点脚本攻击。
     *
     * A very simple introduction: Let's say an attackers changes its username from "John" to these lines:
     * "<script>var http = new XMLHttpRequest(); http.open('POST', 'example.com/my_account/delete.php', true);</script>"
     * This means, every user's browser would render "John" anymore, instead interpreting this JavaScript code, calling
     * the delete.php, in this case inside the project, in worse scenarios something like performing a bank transaction
     * or sending your cookie data (containing your remember-me-token) to somebody else.
     * 一个非常简单的介绍:假设一个攻击者改变用户名从"John"到这些线:
     * "<script>var http = new XMLHttpRequest(); http.open('POST', 'example.com/my_account/delete.php', true);</script>"
     * 这意味着,每一个用户的浏览器将呈现"John"了,而不是解释这个JavaScript代码,调用delete.php,
     * 在这种情况下,在项目内部,在更糟糕的情况下执行银行事务或发送cookie数据(包含remember-me-token)给其他人。
     *
     * What is XSS ?
     * XSS是什么?
     * @see http://phpsecurity.readthedocs.org/en/latest/Cross-Site-Scripting-%28XSS%29.html
     *
     * Deeper information:
     * 更深层次的信息:
     * @see https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
     *
     * XSSFilter expects a value, checks if the value is a string, and if so, encodes typical script tag chars to
     * harmless HTML (you'll see the code, it wil not be interpreted). Then the method checks if the value is an array,
     * or an object and if so, makes sure all its string content is encoded (recursive call on its values).
     * Note that this method uses reference to the assed variable, not a copy, meaning you can use this methods like this:
     * XSSFilter预计值,检查如果该值是一个字符串,如果是这样,典型的脚本标记字符进行编码的HTML(您将看到代码,它会不解释)。
     * 然后方法检查如果该值是一个数组,或一个对象,如果是,确保所有的字符串内容编码(递归调用的值)。
     * 可行的注意,这个方法使用引用变量,不是复制,这意味着你可以使用这个方法是这样的:
     *
     * CORRECT: Filter::XSSFilter($myVariable);
     * WRONG: $myVariable = Filter::XSSFilter($myVariable);
     * 正确的:Filter::XSSFilter($myVariable);
     * 错误:$myVariable = Filter::XSSFilter($myVariable);
     *
     * This works like some other popular PHP functions, for example sort().
     * 这就像其他流行的PHP函数,例如sort()。
     * @see http://php.net/manual/en/function.sort.php
     *
     * @see http://stackoverflow.com/questions/1676897/what-does-it-mean-to-start-a-php-function-with-an-ampersand
     * @see http://php.net/manual/en/language.references.pass.php
     *
     * FYI: htmlspecialchars() does this (from PHP docs):
     * 通知你:htmlspecialchars()这是(从PHP文档):
     *
     * '&' (ampersand) becomes '&amp;'
     * '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
     * "'" (single quote) becomes '&#039;' (or &apos;) only when ENT_QUOTES is set.
     * '<' (less than) becomes '&lt;'
     * '>' (greater than) becomes '&gt;'
     * '&'(&)成为'&amp;'
     * '"'(双引号)成为'&quot;'当ENT_NOQUOTES没有设置。
     * "'"(单引号)成为'&#039;'(或者&apos;)只有当ENT_QUOTES集。
     * '>'(大于)成为'&gt;'
     * '<'(小于)成为'&lt;'
     *
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     *
     * @param  $value    The value to be filtered 过滤值
     * @return mixed
     */
    public static function XSSFilter(&$value)
    {
        // if argument is a string, filters that string
        // 如果参数是一个字符串,过滤器字符串
        if (is_string($value)) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            // if argument is an array or an object,
            // 如果参数是一个数组或一个对象,
            // recursivly filters its content
            // 递归滤波器其内容
        } else if (is_array($value) || is_object($value)) {

            /**
             * Make sure the element is passed by reference,
             * In PHP 7, foreach does not use the internal array pointer.
             * In order to be able to directly modify array elements within the loop
             * precede $value with &. In that case the value will be assigned by reference.
             * @see http://php.net/manual/en/control-structures.foreach.php
             * 确保元素是通过引用传递,在PHP7中,foreach不使用内部数组指针。
             * 为了能够直接修改数组元素在循环之前$value&。在这种情况下将分配的价值参考。
             */
            foreach ($value as &$valueInValue) {
                self::XSSFilter($valueInValue);
            }
        }

        // other types are untouched
        // 其他都没有
        return $value;
    }
}
