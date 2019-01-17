---
layout: post
title: 如果让 strpos 查找一个整数类型的数字会发生什么？
category: php
comments: true
description: 如果让 strpos 查找一个整数类型的数字会发生什么
keywords: PHP,源码,strpos,字符串查找
---

每次数据来了，想要查找这个字符串中某个字符，上来就是使用strpos。strpos用于查找字符串中某个子串第一次出现的位置。

那么，如果不小心给strpos传入的是一个整数类型又会怎么样呢？

假设有一个字符串"I don't happy ! xxxx585xxx",现在需要把585以及后面的全部去掉。585是文件，或者数据库读取出来的，且做了数字类型格式化。

```php
$str = "I don't happy ! xxxx585xxx";
$find = 585;
$s = substr($str, 0,strpos($str, $find));
var_dump($s);

```

直接使用```strpop($str,$find);```获取字符串的起始位置，然后再使用substr做一个截取。看似没有错误，但实际上跑完之后却是把整个字符串都删掉了。上面得到的是一个空字符串

<!-- more -->


查看php 源码中string.c的文件，找到strpos的代码。strpos对于非字符串类型的数据使用``php_needle_char``做了一次类型转换，强制类型转换。

```c
switch (Z_TYPE_P(needle)) {
	case IS_LONG:
		*target = (char)Z_LVAL_P(needle);
		return SUCCESS;
	case IS_NULL:
	case IS_FALSE:
		*target = '\0';
		return SUCCESS;
	case IS_TRUE:
		*target = '\1';
		return SUCCESS;
	case IS_DOUBLE:
		*target = (char)(int)Z_DVAL_P(needle);
		return SUCCESS;
	case IS_OBJECT:
		*target = (char) zval_get_long(needle);
		return SUCCESS;
	default:
		php_error_docref(NULL, E_WARNING, "needle is not a string or an integer");
		return FAILURE;
}

```

从 C 代码中可以看到，如果是整数类型，则强制转换成char类型。所以当你传入585的时候，使用char进行强转之后得到的结果是字符串"I",所以实际上截取之后的字符串长度为0。

类型转换分为下列几种情况:

* 整形，长整型直接转成char类型
* 布尔值，分别转成字符'1'，'0'，所以strpost('e1',true);返回内容为1
* double类型数据，先强转为长整型再转换成char类型
* 对象则对对象id进行char的转换
* 其他类型触发E_WARNING的警告


到这里就了解了为什么给一个整数，strpos会有意向不到的结果。

strpos里的代码还是比较简单，读起来也不费劲。

```c

if (offset < 0) {
	offset += (zend_long)ZSTR_LEN(haystack);
}
if (offset < 0 || (size_t)offset > ZSTR_LEN(haystack)) {
	php_error_docref(NULL, E_WARNING, "Offset not contained in string");
	RETURN_FALSE;
}

```
对offset参数进行验证，在这一步过滤越界的offset。同时对负数的offset进行处理，转换成正数，在下面的处理统一安正数处理

```c
if (Z_TYPE_P(needle) == IS_STRING) {
	if (!Z_STRLEN_P(needle)) {
		php_error_docref(NULL, E_WARNING, "Empty needle");
		RETURN_FALSE;
	}
	found = (char*)php_memnstr(ZSTR_VAL(haystack) + offset,
		                Z_STRVAL_P(needle),
		                Z_STRLEN_P(needle),
		                ZSTR_VAL(haystack) + ZSTR_LEN(haystack));
} else {
	if (php_needle_char(needle, needle_char) != SUCCESS) {
		RETURN_FALSE;
	}
	needle_char[1] = 0;
	found = (char*)php_memnstr(ZSTR_VAL(haystack) + offset,
						needle_char,
						1,
	                    ZSTR_VAL(haystack) + ZSTR_LEN(haystack));
}
```
处理分为字符和非字符两种方式进行处理。非字符类型进行一次数据类型转换，最终根据查找字符的长度在原始字符串中搜索位置。







