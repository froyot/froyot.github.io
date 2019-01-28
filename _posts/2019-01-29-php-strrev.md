---
layout: post
title: 简单聊聊字符串的翻转问题
category: php
comments: true
description: php 字符串翻转
keywords: PHP,源码,strrev,字符串翻转
---

字符串的翻转在日常开发使用程度比较少，但是面试过程中却是常有的。最近看php 源码中strrev，因此写一篇文记录对字符串翻转问题的一些学习。

<!-- more -->

对于字符串"Hello word" 翻转成"drow olleH"的问题，php有现成函数``strrev``可以解决。先看看php如何实现的

```c
PHP_FUNCTION(strrev)
{
	zend_string *str;
	char *e, *p;
	zend_string *n;
	if (zend_parse_parameters(ZEND_NUM_ARGS(), "S", &str) == FAILURE) {
		return;
	}
	n = zend_string_alloc(ZSTR_LEN(str), 0);
	p = ZSTR_VAL(n);
	e = ZSTR_VAL(str) + ZSTR_LEN(str);
	while (--e >= ZSTR_VAL(str)) {
		*p++ = *e;
	}
	*p = '\0';

	RETVAL_NEW_STR(n);
}
```

这其实对应一种解决方案。在一个循环中，把字符串从后往前复制到一个新的变量中去，然后返回。时间复制度是O(n),空间复制度O(n)。

另一种方案则是在原有字符串上做修改。分别设置两个标记变量。分别从字符串的前面，后面向中间靠拢，当两个标记相遇则结束。时间复制度O(n)，空间复杂度O(1)

```php
$str = "Hello word";
$i = 0;
$j = strlen($str)-1;
while ($i <$j) {
	$tmp = $str[$i];
	$str[$i] = $str[$j];
	$str[$j] = $tmp;
	$i++;
	$j--;
}
```

网络上还有一种思路是使用异或运算交换两个字符，A^B^B = A,A^B^A = B。其实跟第二种思路类似，只是改变了赋值操作，不引入临时变量。这就跟"不引入其他变量，交换两个变量的值"一样（数值变量，或者等长度字符串变量）

```php
$str = "Hello word";
$i = 0;
$j = strlen($str)-1;
while ($i <$j) {
	$tmp = $str[$i];
	$str[$i] = $str[$i]^$str[$j];
	$str[$j] = $str[$i]^$str[$j];
	$str[$i] = $str[$i]^$str[$j];
	$i++;
	$j--;
}
```


那么对于问题"student. a am I" 翻转成"I am a student."这类问题呢？这种问题，单次本身的顺序是正确的。单词之间的顺序是错误的。上面的问题处理单元是"字符",而这里的问题处理单元是"单词"

这类字符翻转有两种办法，一个先使用strrev翻转整个句子，然后再对里面的单词依次翻转。

```php
$str = "student. a am I";
$str = strrev($str);
$str = implode(' ', array_map(function($word){
	return strrev($word);
}, explode(' ', $str)));

```

第二类，则是直接调换单词顺序。

```php
$str = "student. a am I";
$words = explode(' ', $str);
$i=0;
$j = count($words)-1;
while ($i <$j) {
	$tmp = $words[$i];
	$words[$i] = $words[$j];
	$words[$j] = $tmp;
	$i++;
	$j--;
}
$str = implode(' ', $words);
```






