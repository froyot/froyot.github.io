---
layout: post
title: Yii2 多语言
category: Developer
comments: true
---


任何一个系统在网络上会受到不同国家地区的用户的访问，这个时候，多语言开发就能给用
户很好的体验。虽然在系统创建之初对整个站点进行完全翻译是不太现实的。但是完全可以
对系统操作界面的按钮，链接之类的UI文字内容进行多语言设置。

Yii2中可以对系统进行多语言设置，设置步骤:

*   在系统配置文件中设置系统的语言，原始语言（原始语言一般采用英语）;

```
    //set target language
    'language' => 'zh-CN',
    // set source language to be English
    'sourceLanguage' => 'en-US',

```

*   在UI模板中所有需要显示UI文字的地方使用Yii::t('languageId','language');的方式
进输出

*   在app目录下新建message文件夹以及languageId.php文件，文件中返回一个语言数组，
数组键是原始语言，值是目标语言

到这里，就能个显示指定语言的UI文字了。

Yii::t的使用方式见(链接)[http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html]
