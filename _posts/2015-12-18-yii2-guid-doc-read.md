---
layout: post
title: Yii2 PHP 手册阅读记录
category: PHP
comments: true
description: "Yii2 PHP 手册阅读记录"
---


## Yii2 PHP 手册阅读记录

*   action的默认参数都是从$_GET中获取

>The action methods for inline actions and the run() methods for standalone
actions can take parameters, called action parameters. Their values are obtained
from requests. For Web applications, the value of each action parameter is
retrieved from $_GET using the parameter name as the key; for console applications,
they correspond to the command line arguments.

*   如果action的默认参数要传一个数组，需要在参数前添加限定符array

>If you want an action parameter to accept array values, you should type-hint it with array

```
public function actionView(array $id, $version = null)
{
    // ...
}
```

*   action的寻找顺序，匹配
