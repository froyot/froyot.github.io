---
layout: post
title: CSS规则的specificity
category: CSS
comments: true
---


*	当Speficity值相等时，后来选择符居上。
*	当Speficity值不相等时，Speficity值高的选择符生效。
*	越具体的选择符越有更高的优先级数
*	最后的CSS规则将覆盖任何之前或冲突的CSS规则。
*	嵌入式样式的Speficity值高于其它。
*	ID选择符比属性选择符Speficity值要高。
*	可用IDs去提高选择符的Speficity值
*	另外，!important规则高于一切，慎用；继承的样式属式不参与优先级数值计算，低于其它规则

#### 关于specificity的具体计算在各种情况下的数字加成有如下一般规则：

*	每个ID选择符(#someid)，加100
*	每个class选择符(.someclass)、每个属性选择符(形如[attr=”"]等)、每个伪类(形如:hover等)加10
*	每个元素或伪元素(:firstchild)等，加1 
*	其他选择符包括全局选择符*，加0相当于没加，不过这也是一种specificity，后面会解释。 
*	按这些规则将数字串逐位相加，就得到最终计算得的specificity，然后在比较取舍时按照从左到右的顺序逐位比较


```

h1 {color: red;} 
//只有一个普通元素加成，结果是 1 
body h1 {color: green;} 
//两个普通元素加成，结果是 2 */ ——后者胜出 
h2.grape {color: purple;} 
//一个普通元素、一个class选择符加成，结果是 11*/ 
h2 {color: silver;} 
//一个普通元素，结果是 01 */ ——前者胜出 
html > body table tr[id=”totals”] td ul > li {color: maroon;} 
//7个普通元素、一个属性选择符、两个其他选择符，结果是17 */ 
li#answer {color: navy;} 
//一个ID选择符，一个普通选择符，结果是101 */ ——后者胜出

```

看一下实际的效果:
```
<head>
<meta charset="utf-8">
<title>demo</title>
<style>

.demoa {color:#777 !important;}

/* 10+1+10 = 21*/
.colortest a[id=testa] {color:#ccc;}

/*10+1+10 = 21*/
.colortest p .demoa {color:#666;}

/*10*/
.colortest {color:red;}

/*10+1 = 11*/
.colortest a {color:green;}

p a {color:#222;}

/* 10 + 10 = 20*/
.colortest .demoa {color:yellow;}	



</style>
</head>
<body>

<div class="colortest">
<p style="color:#999;">
<a id="testa" class="demoa" style="color:#111;"> a color test</a>
</p>
</div>
</body>

```

![avatar](http://7xkcoe.com1.z0.glb.clouddn.com/css%20specifiy.PNG)

浏览器中显示的顺序就是样式优先级别的顺序。important除外。从结果可以看出:

*	important具有最高优先级别

*	行内的specificity高于其他的样式specificity

*	具有相同specificity，后面的样式覆盖前面的样式

*	父元素的行内样式不影响specificity
