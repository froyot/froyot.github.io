---
layout: post
title: sql语句执行顺序(mysql为例)
category: sql
comments: true
description: sql语句执行顺序
keywords: join,sql,mysql
---


### 测试数据表

* slugs

```
CREATE TABLE `slugs` (
  `slug_id` int(11) NOT NULL AUTO_INCREMENT,
  `slug_name` varchar(255) DEFAULT '',
  `slug_type` tinyint(1) DEFAULT '1' COMMENT '类型1分类，2标签，3地区',
  PRIMARY KEY (`slug_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8
```

| slug_id   |      slug_name      |  slug_type |
|----------|:-------------:|------:|
|	3	|	资讯		|	1	|
|	4	|	旅游		|	3	|
|	5	|	海外		|	2	|
|	6	|	香港		|	2	|
|	7	|	内地		|	2	|
|	8	|	汽车		|	3	|
|	9	|	财经		|	3	|
|	10	|	民生		|	3	|


<!-- more -->

*	posts

| post_id	|post_name	|
|:----------|:---------:|
| 1	|日本汽车公司造假召回|
| 2	|河南多地联系白天无降水|
| 3	|港币压提前加息|
| 4	|台湾十一旅游人数减少|
| 5	|北京长城到处是人|
| 6	|特朗普又发推文退群了|
| 7	|测试|

*	post_slugs

| post_id	| slug_id | 
|:----------|:---------:|
| 1		| 3 | 
| 1		| 5 | 
| 1		| 8 | 
| 2		| 3 | 
| 2		| 7 | 
| 2		| 10 | 
| 3		| 6 | 
| 3		| 9 | 
| 4		| 2 | 
| 4		| 3 | 
| 5		| 3 | 
| 5		| 7 | 
| 6		| 3 | 
| 6		| 5 | 


### 几种连表方式
*	inner join,可缩写成join. ``SELECT columns FROM TableA INNER JOIN TableB ON A.columnName = B.columnName;`` 返回A，B数据columnName 相同的数据，做等值连接。

*	left outer join,常用缩写 left join

	``SELECT columns FROM TableA LEFT OUTER JOIN TableB ON A.columnName = B.columnName`` 返回以A为主表所有数据以及B表满足条件的数据，以NULL补足数据

	``SELECT columns FROM TableA LEFT OUTER JOIN TableB ON A.columnName = B.columnName WHERE B.columnName IS NULL``返回在A表出现，B表不出现的数据。

*	right outer join,常用缩写 right join

	``SELECT columns FROM TableA RIGHT OUTER JOIN TableB ON A.columnName = B.columnName`` 返回以A为主表所有数据以及B表满足条件的数据，以NULL补足数据

	``SELECT columns FROM TableA RIGHT OUTER JOIN TableB ON A.columnName = B.columnName WHERE B.columnName IS NULL``返回在A表出现，B表不出现的数据。

*	Full [Outer] Join

	``SELECT columns FROM TableA FULL JOIN TableB ON A.columnName = B.columnName`` 返回A,B表数据并集。

	``SELECT columns FROM TableA FULL JOIN TableB ON A.columnName = B.columnName`` WHERE A.columnName IS NULL OR B.columnName IS NULL 返回A,B表数据并集-A,B表交集。

参考文章:(THE SEVEN TYPES OF SQL JOINS)[https://teamsql.io/blog/?p=923]

### sql语句执行顺序

1.FROM：对FROM子句中前两个表执行笛卡尔积 生成虚拟表VT1
2.ON：对VT1表应用ON筛选器 只有满足 <join_condition>为真的行才被插入VT2
3.OUTER(JOIN)：如果指定了OUTER JOIN 保留表(preserved table)中未找到的行将行作为外部行添加到VT2 生成T3
如果FROM包含两个以上表 则对上一个联结生成的结果表和下一个表重复执行步骤1和步骤3 直接结束
4.WHERE：对VT3应用WHERE筛选器 只有使 <where_condition>为TRUE的行才被插入VT4
5.GROUP BY：按GROUP BY子句中的列列表 对VT4中的行分组 生成VT5
6.CUBE|ROLLUP：把超组(Supergroups)插入VT6 生成VT6
7.HAVING：对VT6应用HAVING筛选器 只有使 <having_condition>为TRUE的组才插入VT7
8.SELECT：处理SELECT列表 产生VT8
9.DISTINCT：将重复的行从VT8中去除 产生VT9
10.ORDER BY：将VT9的行按ORDER BY子句中的列列表排序 生成一个游标 VC10
11.TOP：从VC10的开始处选择指定数量或比例的行 生成VT11 并返回调用者

(8) SELECT (9) DISTINCT (11) <TOP_specification> <select_list>
(1) FROM <left_table>
(3) <join_type> JOIN <right_table>
(2) ON <join_condition>
(4) WHERE <where_condition>
(5) GROUP BY <group_by_list>
(6) WITH {CUBE | ROLLUP}
(7) HAVING <having_condition>
(10) ORDER BY <order_by_list>

参考文章:(sql 语句的执行顺序)[https://segmentfault.com/a/1190000009456758]

### 连表操作

*	 select a,b,select a,b产生的结果是a,b两个表的笛卡尔积。
*	 select a join b,select a join b产生的结果是a,b两个表的笛卡尔积。(inner join 也是)
*	left join,right join 不能没有on条件。select a left join b on 1=1,select a right join b on 1=1产生的结果是a,b两个表的笛卡尔积。
*	select a,b的过滤:``SELECT * from posts,post_slugs where post_slugs.post_id=posts.post_id`` 生成的结果和 ``SELECT * from posts join post_slugs on post_slugs.post_id=posts.post_id``结果相同。但是根据sql语句执行顺序，from的两个表会先生成迪卡尔积虚表。在实例中，虚表的大小7x14=98。再从98条记录中根据where条件过滤。但是以join方式的连表操作，执行顺序是先从posts表获取数据，再根据on条件过滤posts数据，post_slugs数据，根据join方式插入保留数据。所以join连表操作where需要过滤的数据是7+14条记录。




