---
layout: post
title: 用sphinx给PHP加个给力的搜索功能
category: php
comments: true
description: 用sphinx给PHP加个给力的搜索功能
keywords: php,sphinx,mysql,模糊查询
---

最近工作上需要实现搜索功能，尝试了几种方案。虽然最终线上部署的还是最low的方案，但是中间的过程还是比较有意思的。业务上根据关键字查找内容。关键字的出处多来源于标题，文章描述等。主要实现方式有一些几种，各个方式各有利弊，需要权衡。

#### like模糊查询标题和描述，使用或条件查询

like查询估计是最常用的方式了，也是最容易实现的方式。业务代码少，逻辑清晰，准确率也高。不用其他额外操作(比如分词)。但是有个非常致命的问题，那就是效率。效率非常低，特别是在数据量大的情况。测试过程中，在224256行数据中，对3749个字进行like查询，执行总时间长达4003秒。相当于每个查询需要花费1.06秒的查询时间。


#### 生成关键字表，使用关键字表进行查询

对数据内容的标题和内容进行分词，把各个分词结果关联该内容。查询的时候根据查询关键字进行匹配。因为不是模糊搜索，所以可以使用数据库的索引，加快搜索速度。但是效果依赖于分词，以及用户输入关键词匹配程度。

例如标题内容"2018年12月7日美国会通过加拿大边境墙预算"，分词内容"2018年12月7日/美国/会/通过/加拿大/边境/墙/预算"。用户输入"美国","加拿大"可以查询到内容。但是如果输入"国会"则无法搜索到内容。如果多个词同时匹配，则需要使用in查询，然后筛选出同时出现的内容。

所以这种生成关键字的方式，虽然查询速度上会比直接使用like查询快，但是业务逻辑会比较复杂。需要在数据插入，更新的同时更新关键词数据。同时查询之前也要对查询内容进行分词操作。查询的准确度依赖于分词结果。

#### 使用sphinx作为搜索引擎

sphinx支持全文搜索,所以在sphinx中查询到关键字对应内容id之后再通过数据库获取内容的全部数据。但是sphinx需要额外的服务(也可以使用sphinxse，不过需要重新编译mysql)，同时索引页会带来内存和储存空间上的开销，同时也会涉及到索引实时更新的问题。在224256行数据中，对3749个字进行查找，查找总时间是6.5秒,速度相当快。主要问题有:

*   数据变更之后需要重建索引。数据增删改都需要记录改动状态(这里我使用最后变更时间，也可以使用一个额外表记录,这样可以处理数据删除的情况)，使用sphinx的sql_query_killlist可以屏蔽旧的索引数据。

*   需要定期重全量索引，保证增量索引重建速度。增量索引的重建速度影响查询的准确率，避免查询已经变更的历史数据。

*   索引重建可以不关闭服务器，但是会影响内存和磁盘开销。224256行数据,重建全量索引时间8.1秒，占用磁盘空间82.6M。 在业务高峰期重建索引容易引起意外


本地实验查询所使用的字是通过sphinx索引创建的字典生成。
生成方式:

```shell
indexer --buildstops dict.txt 100000 --buildfreqs test1 -c /path/to/sphinx.conf
```

以下是在本地实验的一些数据,生成全量索引的结果。下半部分是mysql like查询和sphinx查询的时间对比


```shell

collected 224256 docs, 82.6 MB
sorted 24.4 Mhits, 100.0% done
total 224256 docs, 82.57 Mb
total 8.1 sec, 10.19 Mb/sec, 27685 docs/sec


data count 224256,word count 3749,test times 1
sphinx: etime 6.5123720169067 ,error rate 0
mysql: etime 4003.215970993 ,error rate 0

```