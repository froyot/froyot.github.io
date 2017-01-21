---
layout: post
title: js 脑补
category: Javascript
comments: true
description: "js 脑补"
---


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


## 函数和闭包

```js

function test()
{
    var a = [];
    var i = 0;
    for(i=0;i<3;i++)
    {
        a[i] = function(){
            return i;
        }
    }
    return a;
}

var f = test();
console.log(f[0]);//输出3
console.log(f[1]);//输出3
console.log(f[2]);//输出3
```
控制台输出
3,3,3

说明：
a[i],这里的i是循环的时候就已经确定了。

```

function(){
    return i;
}

```
这里的i是test函数执行完之后，即循环三次之后的i，因此i=3;

如果想要是想a[0] = 0;需要在函数定义的时候，将i作为参数传入函数中。

```js

function test()
{
    var a = [];
    var i = 0;
    for(i=0;i<3;i++)
    {
        a[i] = (function(x){
            return x;
        })(i)
    }
    return a;
}

var f = test();
console.log(f[0]);//输出0
console.log(f[1]);//输出1
console.log(f[2]);//输出2
```









