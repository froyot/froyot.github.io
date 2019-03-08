---
layout: post
title: web 开发会用到的linux命令
category: php
comments: true
description: web 开发会用到的linux命令
keywords: web,linux,命令
---

作为web开发，难免和服务器打交道。这里整理一下工作上会用到的命令。

1、获取服务器类型
很多情况下，由于历史遗留因素，给到你的服务器信息都只是一个访问ip,用户名，密码，其他什么都没了。所以登录到服务器上，一开始先要确定的是这台服务器是什么系统,什么版本。

```
cat /etc/issue

```

### 查看日志

``cat -n 10 access.log`` 查看文件并显示行号

``tail -n 10 access.log`` 查最后集10行日志

``head -n 10 access.log`` 查看文件前10行

``tail -n +10 access.log`` 查看第10行之后的日志

``tail -n +10 access.log|head -n 5 ``查看第10行后面5行日志

``tail -f access.log`` 实时输出日志内容

``cat -n 10 access.log|tail -n +10 |head -n 5`` 查看第10行后面5行日志并显示原始行号

``grep "who" -n access.log`` 在日志中查找包含who关键字的行,-n显示行号

``grep "who|abcdef" -n access.log`` 在日志中查找包含who或者abcdef关键字的行,-n显示行号

``grep -E "abcdef.*who" access.log`` 在日志中使用正则查找匹配行日志

``sed -n '/08\/Mar\/2019:18:13:00/,/08\/Mar\/2019:18:13:25/p' access.log`` 查看2019:18:13:00~2019:18:13:25时间段的日志(两个时间段必须是日志中出现的)


### 文件太大，传输不变，分割合并

``split -l 300 file.txt new_prefix`` 将文件每300行分隔成一个文件，并指定分割后的文件名前缀

``split -b 10m file.txt new_prefix`` 将文件每10M分割成一个文件，并指定分割后的文件名前缀

``cat new_prefix* > file.txt`` 将多个分割文件合并


### 查找文件

``find / -name php.ini`` 在根目录下，按文件名查找文件

``find / -name php*`` 在根目录下，查找以php开始的文件

``find / -size 1500c`` 在根目录下，查找文件大于1500byte的文件

find命令选项:
``
-amin n
查找系统中最后N分钟访问的文件
-atime n
查找系统中最后n*24小时访问的文件
-cmin n
查找系统中最后N分钟被改变状态的文件
-ctime n
查找系统中最后n*24小时被改变状态的文件
-empty
``

locate命令其实是"find -name"的另一种写法，但是要比后者快得多，原因在于它不搜索具体目录，而是搜索一个数据库（/var/lib/locatedb），这个数据库中含有本地所有文件信息。Linux系统自动创建这个数据库，并且每天自动更新一次

whereis命令只能用于程序名的搜索，而且只搜索二进制文件（参数-b）、man说明文件（参数-m）和源代码文件（参数-s）。如果省略参数，则返回所有信息。


### 查看磁盘使用率，目录大小，大文件大目录

``df -h`` 查看所有磁盘挂载点使用情况

``df -h /dev`` 查看dev磁盘使用情况

``du --max-depth=1 -h /dev`` 查看dev下一级文件夹以及dev本身的大小

``du -hm --max-depth=1 /dev| sort -nr | head -12 查看dev目录下一级目录大小，并转换成M大小单位，逆序，取前12个


### 查看网络流量








