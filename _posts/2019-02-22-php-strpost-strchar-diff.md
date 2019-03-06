---
layout: post
title: PHP strpos,strstr,strpbrk这几个函数有什么区别
category: php
comments: true
description: PHP strchar,strpos,strstr,strpbrk这几个函数有什么区别
keywords: PHP,源码,字符串,strchar,strpos,strstr,strpbrk,查找字符串
---


确定一个字符串是否在另一个字符串中，在PHP中有很多方法实现。``strpos,strstr,strpbrk``这几个函数都可以实现。那么这几个函数有什么不同呢？


### strstr

strstr — 查找字符串的首次出现,别名strchar

```php
strstr ( string $haystack , mixed $needle [, bool $before_needle = FALSE ] ) : string

```

返回 haystack 字符串从 needle 第一次出现的位置开始到 haystack 结尾的字符串。

如果$before_needle=true则返回第一次出现的位置前面的字符。如果字符不存在，则返回false。 

如果needle不是一个字符串，那么它将被转化为整型并且作为字符的序号来使用。

```c

if (Z_TYPE_P(needle) == IS_STRING) {
	if (!Z_STRLEN_P(needle)) {
		php_error_docref(NULL, E_WARNING, "Empty needle");
		RETURN_FALSE;
	}

	found = (char*)php_memnstr(ZSTR_VAL(haystack), Z_STRVAL_P(needle), Z_STRLEN_P(needle), ZSTR_VAL(haystack) + ZSTR_LEN(haystack));
} else {
	if (php_needle_char(needle, needle_char) != SUCCESS) {
		RETURN_FALSE;
	}
	needle_char[1] = 0;

	found = (char*)php_memnstr(ZSTR_VAL(haystack), needle_char, 1, ZSTR_VAL(haystack) + ZSTR_LEN(haystack));
}

if (found) {
	found_offset = found - ZSTR_VAL(haystack);
	if (part) {
		RETURN_STRINGL(ZSTR_VAL(haystack), found_offset);
	} else {
		RETURN_STRINGL(found, ZSTR_LEN(haystack) - found_offset);
	}
}
```



### strpos
查找字符串首次出现的位置。

```php

strpos ( string $haystack , mixed $needle [, int $offset = 0 ] ) : int

```
返回 needle 在 haystack 中首次出现的数字位置。查询从offset开始。offset不影响输出的数值。只用于跳过不查询的字符串。

官方文档的Note中:

>如果你仅仅想确定 needle 是否存在于 haystack 中，请使用速度更快、耗费内存更少的 strpos() 函数。

以下是strpos 的源码
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
if (found) {
	RETURN_LONG(found - ZSTR_VAL(haystack));
} else {
	RETURN_FALSE;
}

```



对比两个函数的内部实现，除了offset之外，其实际差别在于strstr最后返回了字符串，strpos返回的是一个数。由于字符串返回的时候涉及到字符串复制的过程，因此会有速度和内存上的损耗。在性能上，strpos 会比strstr好一点点。

可以看一下网上的测试效果,[测试效果地址](http://www.spudsdesign.com/benchmark/index.php?t=strpos1&history)

### strpbrk

strpbrk — 在字符串中查找一组字符的任何一个字符。返回一个以找到的字符开始的子字符串。如果没有找到，则返回 FALSE。

```php

strpbrk ( string $haystack , string $char_list ) : string

```

strpbrk() 函数在 haystack 字符串中查找 char_list 中的字符。

```c

for (haystack_ptr = ZSTR_VAL(haystack); haystack_ptr < (ZSTR_VAL(haystack) + ZSTR_LEN(haystack)); ++haystack_ptr) {
	for (cl_ptr = ZSTR_VAL(char_list); cl_ptr < (ZSTR_VAL(char_list) + ZSTR_LEN(char_list)); ++cl_ptr) {
		if (*cl_ptr == *haystack_ptr) {
			RETURN_STRINGL(haystack_ptr, (ZSTR_VAL(haystack) + ZSTR_LEN(haystack) - haystack_ptr));
		}
	}
}
```

相对于上面两个函数，strpbrk相对粗暴些，直接两个循环，实现字符的查找。在性能上，应该是这三个函数垫底的了。



### 总结
以字符串ABCGCAC为例。

strpos 返回的是完整匹配查询字符串的第一次出现位置。strpos('ABCGCAC','CA')返回结果是4。

strpbrk 返回的是字符列表中匹配的任意一个字符第一次出现之后的字符串。 strpbrk('ABCGCAC','CA') 返回的内容是ABCGCAC,如果传入整数，会转成字符类型strpbrk('ABC13G2CAC','123') 返回13G2CAC

strstr 返回的是完整匹配查询字符串第一次后出现后的字符串,strstr('ABCGCAC','CA') 返回结果CAC


道路千万条，性能优化第一条，一点点的提升也是提升，只需要选择函数的时候合理选择。

字符串处理函数中，以下函数传入整数是当做ascii值去查找

1、strpos,stripos,strrpos,strripos
2、strstr,stristr,strrstr,strchar,strrchar
