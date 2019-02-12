---
layout: post
title: 用php实现大小写转换功能
category: php
comments: true
description: ucwords 功能的实现
keywords: PHP,源码,ucwords,单词变大小写
---

字符串的大小写转换功能在日常中经常使用。那么如何实现一个简单的大小写转换功能呢?

在php中，最终使用的是c语言的toupper,tolower函数将字符进行大小写转换。因此需要定义一个字符大小写转换的函数。

```php
//字符转大写
protected function toupper($c){
 $ord = ord($c);
 return $ord>=97 && $ord<=122 ?chr($ord-32):$c;
}
//字符转小写
protected function tolower($c){
 $ord = ord($c);
 return $ord>=65 && $ord<=90 ?chr($ord+32):$c;
}
```
字符的大小写转换就是进行ascii码的转换。A-Z的ASCII码在65-90之间。a-z的ASCII码在97-122之间。对于不在转换区间的字符，应该原样返回

php中字符串大小写转换有下面几个函数``strtolower``,``strtoupper``,
``lcfirst``,``ucfirst``,``ucwords``,``lcfirst``,
这几个函数都是成对的，因此仅以大写转小写为例说明如何实现这几个函数

``strtoupper``实现字符串从大写转小写。无非是遍历字符串的每个字符，将大写字符转换成小写。

```php
public function strtolower($str){
 if($this->checkempty($str))
 {
  return "";
 }
 $len = strlen($str);
 for($i=0;$i<$len;$i++){
  $str[$i] = $this->tolower($str[$i]);
 }
 return $str;
}
```
php字符串可以像数组一样用下标获取每个字符。因此对字符串每个字符遍历，转换成小写字符即可


``lcfirst``实现首字母大写的功能，因此比strtolower还要简单

```php
public function ucfirst($str){
 if($this->checkempty($str))
 {
  return "";
 }
 $str[0] = $this->toupper($str[0]);
 return $str;
}
```

``lcwords`` 实现单词首字母转小写。说单词，其实是空格后面第一个字符。因此只需要在遍历到空格字符后面第一个非空字符串转换成小写即可。

```php
public function lcwords($str){
 if($this->checkempty($str))
 {
  return "";
 }
 $splitchar = [' ',"\n","\r","\f","\v"];
 $len = strlen($str);
 for($i=0;$i<$len;$i++){
  if(in_array($str[$i], $splitchar))
  {
   $i++;
   if($i>=$len)
   {
    break;
   }
   $str[$i] = $this->tolower($str[$i]);
  }
 }
 return $str;
}
```
主要要小心越界的问题。如果最后一个字符串是空字符。

至于为什么单词分割字符是代码中的那几项，主要是php源码就是根据那几项实现的。php源码中ucwords实现方式如下:

```c
PHP_FUNCTION(ucwords)
{
 zend_string *str;
 char *delims = " \t\r\n\f\v";
 register char *r, *r_end;
 size_t delims_len = 6;
 char mask[256];

 ZEND_PARSE_PARAMETERS_START(1, 2)
  Z_PARAM_STR(str)
  Z_PARAM_OPTIONAL
  Z_PARAM_STRING(delims, delims_len)
 ZEND_PARSE_PARAMETERS_END();

 if (!ZSTR_LEN(str)) {
  RETURN_EMPTY_STRING();
 }
 php_charmask((unsigned char *)delims, delims_len, mask);

 ZVAL_STRINGL(return_value, ZSTR_VAL(str), ZSTR_LEN(str));
 r = Z_STRVAL_P(return_value);

 *r = toupper((unsigned char) *r);
 for (r_end = r + Z_STRLEN_P(return_value) - 1; r < r_end; ) {
  if (mask[(unsigned char)*r++]) {
   *r = toupper((unsigned char) *r);
  }
 }
}

```

将分割的字符串放入一个mask中，在遍历字符串的过程中判断是否是mask的字符。如果是则对后面一位字符进行大写转换操作。

代码地址[https://github.com/froyot/froyot.github.io/blob/master/code/php_stringchange.php](https://github.com/froyot/froyot.github.io/blob/master/code/php_stringchange.php)







