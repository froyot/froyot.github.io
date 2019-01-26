---
layout: post
title: php 中的similar_text如何实现的
category: php
comments: true
description: php 中的similar_text如何实现的
keywords: PHP,源码,similar_text,文本相似
---

PHP字符串处理函数中有一个similar_text用于计算两个字符串的相似程度。今天来看看similar_text如何实现的。


>similar_text — 计算两个字符串的相似度，返回两个字符串中匹配字符的数目

>两个字符串的相似程度计算依据 Programming Classics: Implementing the World's Best Algorithms by Oliver (ISBN 0-131-00413-1) 的描述进行。注意该实现没有使用 Oliver 虚拟码中的堆栈，但是却进行了递归调用，这个做法可能会导致整个过程变慢或变快。也请注意，该算法的复杂度是 O(N\*\*3)，N 是最长字符串的长度。


上面的文档说明还是很绕。

源码中similar_text函数在内部调用了php_similar_char进行处理。ac是参数的个数。函数返回的是两个字符串中匹配字符的数目。如果想要获取相似的百分比，则需要传递一个引用参数获取。

```c

sim = php_similar_char(ZSTR_VAL(t1), ZSTR_LEN(t1), ZSTR_VAL(t2), ZSTR_LEN(t2));

if (ac > 2) {
  Z_DVAL_P(percent) = sim * 200.0 / (ZSTR_LEN(t1) + ZSTR_LEN(t2));
}
RETURN_LONG(sim);
```

在php_similar_char中有调用了php_similar_str，在看php_similar_char前，先看看php_similar_str的功能。

```c

static void php_similar_str(const char *txt1, size_t len1, const char *txt2, size_t len2, size_t *pos1, size_t *pos2, size_t *max)
{
  char *p, *q;
  char *end1 = (char *) txt1 + len1;
  char *end2 = (char *) txt2 + len2;
  size_t l;

  *max = 0;
  for (p = (char *) txt1; p < end1; p++) {
    for (q = (char *) txt2; q < end2; q++) {
      for (l = 0; (p + l < end1) && (q + l < end2) && (p[l] == q[l]); l++);
      if (l > *max) {
        *max = l;
        *pos1 = p - txt1;
        *pos2 = q - txt2;
      }
    }
  }
}
```
php_similar_str内部跑了三个嵌套的循环，这就难怪文档中描述的，时间复杂度是O(N\*\*3)。在最里面的循环中，检查两个字符串连续一致的个数。最里层循环结束之后，判断是否大于已经获取到的最大相似数目。并记录最大相似情况下两个字符串相似处开始的位置。


在php_similar_char，通过php_similar_str拿到最大相似数目，以及两个字符串起始位置。在底下，则把text1,text2分为最大相似字符串前的字符，最大相似字符串，最大相似字符串后面字符串三个部分，分别在递归调用计算两个字符串中相似字符串前后两个部分对应的相似长度。直到字符串长度为0.

```c
static size_t php_similar_char(const char *txt1, size_t len1, const char *txt2, size_t len2)
{
  size_t sum;
  size_t pos1 = 0, pos2 = 0, max;

  php_similar_str(txt1, len1, txt2, len2, &pos1, &pos2, &max);
  if ((sum = max)) {
    if (pos1 && pos2) {
      sum += php_similar_char(txt1, pos1,
                  txt2, pos2);
    }
    if ((pos1 + max < len1) && (pos2 + max < len2)) {
      sum += php_similar_char(txt1 + pos1 + max, len1 - pos1 - max,
                  txt2 + pos2 + max, len2 - pos2 - max);
    }
  }

  return sum;
}
```

看到这，similar_text只能大概计算相似程度。其中有几个小问题。

1.两个空字符串的相似度是0。
2.假设两个字符串'abcdefg','qabdefgabc'，直观上这两个字符串中“匹配字符”的数目有a,b,c,d,e,f,g 但是当你执行similar_text拿到的结果确是6。

看看整个执行过程:

a、获取最常匹配串的长度'defg',长度4，pos1=3,pos2=3
b、获取abc,qab相似长度度2
c、获取空字符串和abc相似度0

所以相似字符串长度为6.

3.顺序敏感
顺序敏感其实也是由于拆分的问题导致的。比如字符串"PHP IS GREAT" 和字符串"WITH MYSQL" 不同的顺序得到的结果分别是2，3。




