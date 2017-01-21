---
layout: post
title: PHP 浏览器关闭 和timeout之后，php程序还会执行吗？
category: PHP
comments: true
description: "PHP 浏览器关闭 和timeout之后，php程序还会执行吗？"
---



在 PHP 内部，系统维护着连接状态，其状态有三种可能的情况：
0 - NORMAL（正常）
1 - ABORTED（异常退出）
2 - TIMEOUT（超时）
当 PHP 脚本正常地运行 NORMAL 状态时，连接为有效。当远程客户端中断连接时，ABORTED
状态的标记将会被打开。当连接时间超过 PHP 的时限时，TIMEOUT 状态的标记将被打开。

```php

<?php
set_time_limit(60);
$interval = 5;
do {

    $fp = fopen('text3.txt', 'a');

    fwrite($fp, "test\n\r");

    fclose($fp);

    sleep($interval); // 函数延迟代码执行若干秒

} while (true);

?>

```

对于上述代码，如果我一打开就立刻关掉该页面，应该会在文件里写多少次test呢？事实证明，
当服务器ignore_user_abort开启的时候，执行12次，就是说，即时用户关掉了
浏览器，PHP脚本也会只执行完，除非遇到timeout的情况。
当ignore_user_abort关闭的时候，会随着用户的终端而终端脚本

#### 注意，面对这种情况，必须确认服务器ignore_user_abort的设置是有效的，xampp似乎无效






