---
layout: post
title: sublime markdown preview 输出文档添加日期
category: tools
comments: true
description: "sublime markdown preview 输出文档添加日期"
---

## sublime markdown preview 输出文档添加日期
最近输出文档都是使用sublime 编辑markdown文件，然后用markdown preview
的方式输出html文件或者pdf。但是每次编辑完之后总是需要手动添加一个更新
日期，未免有些麻烦，而且有时候修改急，会有遗忘的时候。因此想要让sublime
自动为我的文档添加一个update time。

sublime markdown preview插件都是python语言，修改起来还是比较方便。跟踪代码，
发现1191行有save_utf8(tmp_fullpath, html);这里的html内容就是从网络获取之后的
一个html内容。因此如果想要添加内容就要在内容被写入缓存前添加。

但是，有些文件确实不需要添加更新日期，所以还需要为插件添加一项配置项，在插件
的配置文件中添加add_update_time配置项，在MerkdownPreview.py文件中，采用

```python
if settings.get('add_update_time',True)
```
获取插件配置

因此修改内容为：

```python
if settings.get('add_update_time',True):
    html += "<p>update time "+time.strftime( '%Y-%m-%d %X', time.localtime() )+"</p>"

```
