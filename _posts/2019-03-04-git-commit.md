---
layout: post
title: 清理代码提交记录--代码管理的git
category: 工具
comments: true
description: 清理代码提交记录,代码管理的git
keywords: git,代码管理,commit,reset
---

变化总是存在。

如果有一天，你们代码仓库服务器挂了怎么办？

如果有一天，你们需要分离测试与线上代码仓库，那怎么合并代码，手工合并吗？

如果你们好多个版本，不同的版本对应不同的用户，用户希望他们的代码仓库在他们的服务器而不是你的，怎么办？

千万不要相信那些诸如"你先弄过去，以后代码丢给他们就不管了"的鬼话。如果你信了，你真的新建一个仓库，把代码推到新仓库上了。当新的需求 "把最新版本合并到xxx的代码上"，你就懵逼了，这都不是一个源，怎么合并。一行行代码去查吗？

### 多个远程仓库

git可以添加多个远程地址，最最重要的是只要这些远程仓库的代码从相同的commit分出来的，都可以合并。

假设现在有一个本地局域网的仓库，然后我想要添加一个github的远程地址，但是我只希望我release的版本发布到github上。


1)添加远程地址，指定远程地址名称

```sh
git remote add github git://github.com/user/test.git

```
上述命令添加了一个名为github的远程地址。

* 使用``git remote`` 查看远程地址列表.

![git remotelist](http://blog.static.aiaiaini.com/blog/201903/remotelist45b461b27e76836e5137fc1fd57b3d36.jpg)

* 使用``git remote -v`` 显示远程详情
![git remotelist detail](http://blog.static.aiaiaini.com/blog/201903/remotelist_detail36693d3157f4db5454bd7c82f045f24f.jpg)

2) 将本地仓库推到指定远程

``git push  远程名称 远程分支名``




对我个人而言，都是新建一个本地分支与远程分支对应。对于本地而言，不同的远程仓库地址都是不同的分支而已。只是在push的时候小心，不要把代码推到不该推的仓库就可以了。


### 发布的时候只保留一个commit记录

一般代码合并的时候都是使用merge直接合并。但是merge有个问题就是会把详细的提交记录合并过去。对于一些项目发布，在发布版本上其实不需要记录过多的开发细节。只需记录发布日志信息。这个时候就需要merge --squash了。

```
git merge dev --squash
git commit -m 'relase version2'
git push

```
上述命令会将dev分支的变更进行合并，但是不会使用dev的commit信息，而是需要再自己手动发布一个commit。

merge之前分支情况:

![git merge before](http://blog.static.aiaiaini.com/blog/201903/remotelist_detail36693d3157f4db5454bd7c82f045f24f.jpg)

merge之后分支情况:

![git merge ](http://blog.static.aiaiaini.com/blog/201903/remotelist_detai236693d3157f4db5454bd7c82f045f24f.jpg)

merge--squash之后分支情况:

![git merge squash](http://blog.static.aiaiaini.com/blog/201903/remotelist_detai3eb0b9b3ca54ce8913ef4cb67d08d067c.jpg)



### 保持分支干净rebase

如果你有强迫症，每次看到各个分支之间的连接网络就抓狂，不想看到下面的场景:

![git branch net](http://blog.static.aiaiaini.com/blog/201903/branch_manyeecb3083a9667f35dc56d891bac64982.jpg)

那你可能需要使用rebase来合并代码。

关于rebase的介绍可以参考[官方文档](https://git-scm.com/book/zh/v2/Git-%E5%88%86%E6%94%AF-%E5%8F%98%E5%9F%BA)

```sh
git rebase 需要合并的分支名称
```

以下是rebase前后的一个效果展示

![before git rebase](http://blog.static.aiaiaini.com/blog/201903/before_git_rebaseeecb3083a9667f35dc56d891bac64982.jpg)

使用git rebase 之后:

![after git rebase](http://blog.static.aiaiaini.com/blog/201903/after_git_rebaseeecb3083a9667f35dc56d891bac64982.jpg)

可以看到，git rebase 时候合并后的分支非常干净，看到的提交记录就好像整个开发过程在当前分支串行完成的一样。


### rebase修改历史commit

假如在上调试的时候，提交了很多次类似于'fix error'之类的提交信息像下面这样的:

![git many fix msg](http://blog.static.aiaiaini.com/blog/201903/git_many_fixmsgeecb3083a9667f35dc56d891bac64982.jpg)

怎么把这些信息压缩到一个commit里面呢?这里还是使用到rebase

比如上面的提交记录，我需要合并'fix1','fix2','fix3','fix5'到一个commit 'fix error before online'里面

首先找到fix1前一个记录的hash,然后使用``git rebase -i hash``。命令会把hash之后的commit都列出来，开发人员决定保留那些(pick)，删除哪些(squash)。然后按照命令提示重新编写提交信息即可。合并之后的提交记录如下:

![git rebase commits](http://blog.static.aiaiaini.com/blog/201903/git_rebase_commits13c5fb34f112ba0382e7c96334ad8334.jpg)


### 删除本地commit

对于本地已经commit但是还没有push的情况下

1、保留本地修改:

``git reset commit_id `` 丢弃commit，但是保留文件修改，commit_id是本地的commit

2、完全撤销到修改前状态

``git reset --hard commit_id``  commit_id是修改前最后一个commit_id



以上就是工作中会用的比较多的git操作。当然git还有很多操作可以很好的帮助我们管理代码，还得好好学习。




