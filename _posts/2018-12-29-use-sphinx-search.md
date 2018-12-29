---
layout: post
title: shpinx的安装使用
category: php
comments: true
description: shpinx的安装使用
keywords: php,sphinx,mysql
---

Sphinx 在2018年的搜索引擎中排名第五，但它仍然是一种强大且流行的技术，在排名方面让位于Elasticsearch和Solr。
Sphinx用于如此着名的系统中 Joomla.org， CouchSurfing.org， Wikimapia.org， Tumblr.com， 优酷土豆 以及数百种其他应用。

>Sphinx支持高速建立索引（可达10MB/秒，而Lucene建立索引的速度是1.8MB/秒） 
>高性能搜索（在2-4 GB的文本上搜索，平均0.1秒内获得结果） 
>高扩展性（实测最高可对100GB的文本建立索引，单一索引可包含1亿条记录） 
>支持分布式检索 
>支持基于短语和基于统计的复合结果排序机制 
>支持任意数量的文件字段（数值属性或全文检索属性） 
>支持不同的搜索模式（“完全匹配”，“短语匹配”和“任一匹配”） 
>支持作为Mysql的存储引擎


### 安装
从官网[http://sphinxsearch.com](http://sphinxsearch.com/downloads/current/)下载最新版本。windows版本是一个压缩包，本地解压可以直接使用。
目录结构如下:
```
\
|-api\  各种语言api文件
|-bin\  
|   |-indexer.exe   索引创建程序
|   |-searchd.exe   搜索服务程序
|-doc\  文档
|-etc\  配置文件夹
|   |-sphinx-min.conf.dist
|   |-sphinx.conf.dist
|-misc\ 
|-src\
```

### 配置

sphinx的示例配置文件在etc文件夹中。复制sphinx-min.conf.dict 到sphinx.conf

主索引配置。对于中文，source中需要指定sql连接字符集，避免拿到的数据是乱码。在生成主索引的同时，我们还需要更新所以最后创建时间，因此需要一些额外操作。可以使用sql_query_post执行指定的sql语句，将数据保存到数据库中。

source 一些配置说明:

|配置|说明|
|:---|:---|
|sql_query_pre | 前置sql操作，用户设置连接字符集，定义一些sql变量 |
|sql_query | 数据获取sql语句 |
|sql_query_post | 数据获取之后的sql操作，用于保存一些状态数据等 |
|sql_query_killlist| 屏蔽索引id数据源，用来告诉sphinx，哪些索引id要屏蔽,配合kbatch使用|


index 配置说明:

|配置|说明|
|:---|:---|
|source | 使用数据配置名，对应source配置名称|
|path | 索引数据保存路径 |
|mlock | 索引缓存设置，0不使用 | 
|min_word_len | 索引的词的最小长度 设为1 既可以搜索单个字节搜索,越小 索引越精确,但建立索引花费的时间越长 | 
|ngram_len | 对于非字母型数据的长度切割(默认已字符和数字切割,设置1为按没个字母切割) |
|ngram_chars | ngram 字符集，中文需要配置 | 
|kbatch|屏蔽索引的列表|


### 使用


先来看如何把sphinx集成到业务当中。sphinx可以设置多个索引。虽然sphinx生成索引很快，但是对于大量数据，每次数据变更多重新建索引，还是会有很大的额外开销。目前主要的解决方案是通过更新增量索引实现sphinx数据实时更新。索引sphinx中其实有两个索引数据，一个全量索引，一个增量索引。如果数据是后面新增的，会在增量索引中找到。如果数据修改，或删除，则配合sql_query_killlist可以屏蔽旧的索引数据，从而保证对修改的数据以增量索引为主。以下是sphinx使用的一个说明图:

![sphinx集成图片](http://blog.static.aiaiaini.com/sphinx-use-struct.png)


### 实验

#### 普通搜索
mysql中建立三个表，config用于保存sphinx状态数据，changes保存变更记录数据，posts是源数据。post中插入一条数据:

```sql
INSERT INTO `test`.`posts` (`id`, `title`, `sub_title`, `summary`, `status`, `create_at`, `update_at`) VALUES ('1', '百度新华网', '百度新华网', '百度新华网', '1', '0', '1545982266');

```

在命令行中生成全量索引

```shell
 ./indexer.exe --config /d/soft/sphinx-3.1.1/etc/sphinx.conf --all

```
然后启动搜索服务:

```shell
./searchd.exe --config /d/soft/sphinx-3.1.1/etc/sphinx.conf

```
执行php脚本，搜索"新华"两个字，可以发现数据可以被找出来。

#### 新增数据后搜索

执行下面sql语句模拟数据新增操作:

```sql
set @currenttime=(select UNIX_TIMESTAMP(current_timestamp()));
INSERT INTO `test`.`posts` (`title`, `sub_title`, `summary`, `status`, `create_at`, `update_at`) VALUES ('百度新浪网', '百度新浪网', '百度新浪网', '1', @currenttime, @currenttime);
set @lastid=(SELECT max(id) from posts);
INSERT into changes (`post_id`,`update_at`) values(@lastid,@currenttime);
``` 

在命令行中更新增量索引

```shell
./indexer.exe --config /d/soft/sphinx-3.1.1/etc/sphinx.conf detal --rotate

```
执行php脚本，输出内容:``id:3,summary:百度新浪网,title:百度新浪网``,可以找到新增内容

#### 修改旧数据

执行下面sql语句模拟数据更新操作

```sql
set @currenttime=(select UNIX_TIMESTAMP(current_timestamp()));
update posts set summary="百度腾讯网",update_at=@currenttime where id=1;
update changes set update_at=@currenttime where post_id=1;
```
这个时候我们把原先 summary "百度新华网"的数据修改成"百度腾讯网",这个时候在搜索"新华"应该无法搜索到该内容

执行php脚本，搜索"新华"，输出``not found``,搜索"腾讯"内容可以搜索到内容``id:1,summary:百度腾讯网,title:百度新华网``


#### 删除旧数据

重新创建一次全量索引，使得全量索引有两条数据，然后模拟删除一条数据之后搜索。这个时候id=1的数据summary内容为"百度腾讯网"。

执行下面sql语句模拟数据删除操作

```sql
set @currenttime=(select UNIX_TIMESTAMP(current_timestamp()));
DELETE from posts where id=1;
update changes set update_at=@currenttime where post_id=1;
```

执行php脚本，搜索"腾讯"，输出``not found``


### 其他

必须设置sql_query_killlist,和kbatch,sql_query_killlist配置在detal source中，kbatch配置在detal index中。

搜索顺序，必须是'main','detal'，这样才会保证索引以detal为主。

如果没有配置sql_query_killlist的时候，对于已经修改的数据，sphinx返回的查询还是旧的数据。例如前面，初始数据为"百度新华网",修改为"百度腾讯网"之后，搜索新华还是能搜索出来，搜索腾讯确搜索不出来。

sql_query_killlist 只是生成了一个屏蔽表，保证在这个屏蔽表的数据，只会从后面(这里是detal索引)索引查找，并没有删除索引数据，因此查询的顺序也很重要。


[测试代码以及sphinx的配置](http://blog.static.aiaiaini.com/sphinx-demo-config.rar)









