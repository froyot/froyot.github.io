---
layout: post
title: Android FragmentTabhost 截断tab点击事件
category: 技术
comments: true
description: "Android FragmentTabhost 截断tab点击事件"
---


## Android FragmentTabhost 可以很方便的实现tabhost布局，但是如果一个场景:
某个tab需要验证用户登录才能够显示，如果用户没登录，跳转到登陆activity。这个时候
就需要截断tab的点击事件。

以上面的场景为例

```java
mTabHost.getTabWidget().getChildTabViewAt(needInteraptTab).setOnClickListener(new View.OnClickListener() {
   @Override
   public void onClick(View v) {
       if(isLogin())
       {
            //如果已经登录，执行默认点击操作
            //由于已经覆写了点击方法，所以需要实现tab切换
           mTabHost.setCurrentTab(count-1);
           mTabHost.getTabWidget().requestFocus(View.FOCUS_FORWARD);
       }
       else
       {
            //如果没有登陆
           Intent intent = new Intent();
           intent.setClass(context,LoginActivity.class);
           startActivity(intent);
       }
   }
});

```
上面就实现了tab点击前判断用户是否登陆，并根据登陆状态执行不同的操作。
