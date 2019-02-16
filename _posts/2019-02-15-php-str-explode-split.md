---
layout: post
title: PHP 字符串分割成数组函数explode,str_split 内部实现
category: php
comments: true
description: PHP 字符串分割成数组函数explode,str_split 内部实现
keywords: PHP,源码,explode,str_split,字符串分割
---

将一个字符串分割成数组在日常开发中的应用应该是很多的。如果指定分割符，可以使用explode，如果没有分割符，可以使用split实现。
那么两个函数内部如何实现，有什么不同呢？

<!-- more -->

### str_split

str_split — 将字符串转换为数组

如果指定了可选的 split_length 参数，返回数组中的每个元素均为一个长度为 split_length 的字符块。

没有split_length参数,每个字符块为单个字符。

如果 split_length 小于 1，返回 FALSE。

如果 split_length 参数超过了 string 超过了字符串 string 的长度，整个字符串将作为数组仅有的一个元素返回。

```php

str_split ( string $string [, int $split_length = 1 ] ) : array

```

对于字符串直接按长度切分，一般的做法就是直接遍历字符串，以指定的长度为步长截取子串放入数组中。

```c
if (split_length <= 0) {
	php_error_docref(NULL, E_WARNING, "The length of each segment must be greater than zero");
	RETURN_FALSE;
}
if (0 == ZSTR_LEN(str) || (size_t)split_length >= ZSTR_LEN(str)) {
	array_init_size(return_value, 1);
	add_next_index_stringl(return_value, ZSTR_VAL(str), ZSTR_LEN(str));
	return;
}
array_init_size(return_value, (uint32_t)(((ZSTR_LEN(str) - 1) / split_length) + 1));
n_reg_segments = ZSTR_LEN(str) / split_length;
p = ZSTR_VAL(str);
while (n_reg_segments-- > 0) {
	add_next_index_stringl(return_value, p, split_length);
	p += split_length;
}
if (p != (ZSTR_VAL(str) + ZSTR_LEN(str))) {
	add_next_index_stringl(return_value, p, (ZSTR_VAL(str) + ZSTR_LEN(str) - p));
}

```

首先截取长度进行判断，如果小于1直接返回空。

然后判断需要分割字符串的长度与截取长度关系，如果截取长度大于等于字符串长度，则返回一个只包含一个元素的数组。

那原字符串长度与截取长度确定循环次数。每次循环中截取一个子串添加到数组中。```while```在没有整除的时候，会遗漏最后一串字符。所以在最后使用一个``if``进行判断。


### explode

explode — 使用一个字符串分割另一个字符串

```php

explode ( string $delimiter , string $string [, int $limit ] ) : array

```
如果设置了 limit 参数并且是正数，则返回的数组包含最多 limit 个元素，而最后那个元素将包含 string 的剩余部分。

如果 limit 参数是负数，则返回除了最后的 -limit 个元素外的所有元素。

如果 limit 是 0，则会被当做 1。

```c

char *p1 = ZSTR_VAL(str);
char *endp = ZSTR_VAL(str) + ZSTR_LEN(str);
char *p2 = (char *) php_memnstr(ZSTR_VAL(str), ZSTR_VAL(delim), ZSTR_LEN(delim), endp);
zval  tmp;

if (p2 == NULL) {
	ZVAL_STR_COPY(&tmp, str);
	zend_hash_next_index_insert_new(Z_ARRVAL_P(return_value), &tmp);
} else {
	do {
		ZVAL_STRINGL(&tmp, p1, p2 - p1);
		zend_hash_next_index_insert_new(Z_ARRVAL_P(return_value), &tmp);
		p1 = p2 + ZSTR_LEN(delim);
		p2 = (char *) php_memnstr(p1, ZSTR_VAL(delim), ZSTR_LEN(delim), endp);
	} while (p2 != NULL && --limit > 1);

	if (p1 <= endp) {
		ZVAL_STRINGL(&tmp, p1, endp - p1);
		zend_hash_next_index_insert_new(Z_ARRVAL_P(return_value), &tmp);
	}
}

```

``php_memnstr``获取字符串在另一个字符串第一次出现的位置。

如果不存在分割字符串，则直接返回包含原字符串组成的数组

通过``do``循环分别获取分隔符之间的字符串。``limit>1``保证最后一个数组元素包含字符串剩下部分。



### 两个函数内部实现异同

str_split 使用``add_next_index_stringl``截取字符添加到数组中。explode使用``zend_hash_next_index_insert_new``。

内部都是循环截取字符串实现分割字符。




