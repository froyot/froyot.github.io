---
layout: post
title: 大黄蜂好看吗?用python分析电影观看数据
category: php
comments: true
description: python分析电影观看数据
keywords: python,大黄蜂,猫眼,海王
---


大黄蜂,2019-01-04 在大陆上映。观众们很期待。但是期待归期待，是否真的值得去电影院观看还是值得商榷的。本片导演 特拉维斯·奈特 主演:海莉·斯坦菲尔德,约翰·塞纳,小豪尔赫·兰登伯格 目前在猫眼电影中评分:9.20，评价人数:129402人。看数据还是值得瞧一瞧。

猫眼电影目前m端有些数据还是直接返回json数据，所以抓取还是很方便。之前看网络上有一个分析海王电影的文章，但是一直没有找到代码，所以自己写了一个。不仅仅支持一个电影，可以对猫眼电影里的所有电影进行分析，前提是能爬下来数据。实验过程中，都是爬取10个电影就无法拿到数据了。

猫眼电影电影列表数据url：``http://m.maoyan.com/ajax/movieOnInfoList?token=``没有任何参数，接口会返回当前猫眼可见的电影id列表，后面爬取电影详情需要。

详情url:``http://m.maoyan.com/ajax/detailmovie?movieId=%s``,参数就是前面获取的movieid。基本上电影数据都在这里面，但是很遗憾，没有票房数据。

评论详情，用的是旧的url ``http://m.maoyan.com/mmdb/comments/movie/%d.json?offset=%d&startTime=%s``offset是跳过数目，startTime是最晚的评论时间。目前猫眼已经不使用这个了。旧的数据采用的评分是5分制，新的url采用的是十分制。由于新的url并没有返回用户地理信息，所以还是使用旧的url。




![score](http://blog.static.aiaiaini.com/maoyan_movie_score.png)

![score time](http://blog.static.aiaiaini.com/maoyan_movie_score_time.png)

![gender](http://blog.static.aiaiaini.com/maoyan_movie_gender.png)

![geo1](http://blog.static.aiaiaini.com/maoyan_movie_geo.jpg)

![geo2](http://blog.static.aiaiaini.com/maoyan_movie_geo2.jpg)

![word cloud](http://blog.static.aiaiaini.com/maoyan_movie_wordcloud.png)

![100 word comments](http://blog.static.aiaiaini.com/maoyan_movie_comment_grateset_word.png)



