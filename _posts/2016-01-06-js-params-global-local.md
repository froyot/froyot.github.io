---
layout: post
title: js 变量作用域
category: Javascript
comments: true
description: "js 变量作用域"
---

## js 变量作用域
js的变量作用域，根据不同的定义位置分为全局变量以及局部变量。同事，
任何没有用var定义的变量都是全局变量。如果没有局部变量，则寻找全局变量。
但是需要注意一点，函数域优于全局域，当变量调用语句在函数域内，同时函数域中也存在
局部变量，则使用的是局部变量，不管调用时，变量是否定义。

```js
var a = 123;//全局变量
function test(){
    alert(a);//弹出undefined,因为此时局部变量a还为定义;
    var a = 1;
    alert(a);//弹出1;
}
```

如果把函数中```var a = 1;```注释掉，则会弹出两次123




