---
layout: post
title: SSH 防止暴力破解
category: 技术
comments: true
description: "SSH,暴力破解,扫描"
---


## SSH 防止暴力破解
最近看服务器日志，发现secure日志中有很多登录验证记录，估计是被盯上了。ssh暴力扫
描破解。ssh本身是几次密码错误之后就断开，但是还可以继续连接，然后再试密码和端口。
如果ssh扫描频率大，自己就会没办法连接到服务器，因为服务器一直在验证那些实验的密码。

在网上看到一个脚本，用来将多次密码错误尝试的ip拉进小黑屋。从此拒绝改ip的请求。

```
#!/bin/bash
#Denyhosts ssh error ip

cat /var/log/secure|awk '/Failed/{print $(NF-3)}'|sort|uniq -c|awk '{print $2"=" $1;}' >/root/bin/Denyhosts.txt
DEFINE="3"
for i in `cat /root/bin/Denyhosts.txt`
do
IP=`echo $i|awk -F= '{print $1}'`
NUM=`echo $i|awk -F= '{print $2}'`
if [ $NUM -gt $DEFINE ]
then
grep $IP /etc/hosts.deny >/dev/null
if [ $? -gt 0 ];
then
echo "sshd:$IP" >> /etc/hosts.deny
fi
fi
done

```
以上代码在不同的服务器需要适当修改。
在我的虚拟机里面，log里没有secure文件，登陆记录在auth.log里。
另外需要保证/root/bin/Denyhosts.txt文件存在。

存在一个问题，因为即使公司里很多都是使用动态ip的，万一哪天分配的ip跟黑名单里
面的ip一样，那就无力回天了。





