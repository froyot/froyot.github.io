---
layout: post
title: 高性能MySQL（第3版）阅读笔记
category: sql
comments: true
description: 高性能MySQL（第3版）阅读笔记
keywords: mysql,sql,阅读笔记
---


*  char(5) 和varchar(200) 存储'hello'的空间开销相同，使用短列有什么优势?

*  mysql会分配固定大小内存块保存内部值，尤其使用内存表临时表进行排序，操作时。因此最好只分配需要的存储空间。

*  数据类型越短越好，尽量避免NULL（NULL索引，统计，比较更复杂,可为NULL的列需要的存储空间更多)

*  整数(tinyint 8位，smallint 16位，mediumint 24位，int 32位，bigint 64位储储空间)mysql 可为整数指定列宽，但是列宽只是为图像化界面显示字符个数

*  decimal 可指定小数点前后允许的最大位数，消耗存储空间，mysql 将数字打包在二进制字符串中，每4个字节存储9个数字，小数点战一个字节

*  float 在存储相同范围的数据，占用存储空间比decimal小，float 使用4个字节存储，double占用8个字节

*  varchar 存储变成字符串，需要1位或2位保存长度。长度小于255使用1位。由于变长，更新操作更费时间（更新使得行数据长度变化，myisam 将数据猜成不同存储片段，innodb则需要分裂页，将数据放进页内）。mysql5 在存储varchar 的时候，空格会保留？？

*  以下情况适合使用varchar[高性能MySQL（第3版）p115]：
字符串最大长度比平均长度大很多，列的更新少(不会产生碎片)；使用类似UTF8字符集，每个字符使用不同字节数存储

*  char ,mysql会根据定义的长度分配固定空间，当存储cahr类型数据，mysql会去除末尾空格。char适合存储固定长度，或长度相近的数据。对于经常变更的数据，char比varchar好，因为不会产生碎片

*  binary,varbinary 固定长度二进制，变长二进制字符串，采用"\0"结束

*  blob,text blob以二进制方式存储，没有排序规则；text存储字符串，有字符集排序规则。mysql 对blob,text排序是对列前面max_sort_length排序，而不是整个字符串排序。

*  enum 类型。枚举类型将列表值压缩到一个或两个字节中，内部保存的是整数，并在.frm保存字符串，整数映射关系。枚举类型排序是按内部整数排序，而非字符排序。枚举列，字符串列表是固定的，每次添加修改都需要使用alter table。将char,varchar 和枚举类型关联时，会比直接cahr,varchar关联慢。

*  datetime 保存范围大从1001~9999年，最小精度秒，与时区无关占用8个字节。

*  timestramp 保存从到1970年1月1日时间差。只占用4个字节，表示范围2038年。依赖时区。

*  bit 位，最大64位，mysql把bit当做字符串。bit(1) 是二进制0，和1 而非字符"0","1".

*  主键列:尽量使用整数列(占用空间小)，保证有序。（防止页分裂，磁盘随机读取，MD5,sha,uuid产生的字符串会分布在很大的空间内，导致insert,select 慢）

*  in 查询，mysql会先把in列表数据进行排序，然后使用二分查找方式确定列表的值是否满足条件，因此不等同于多个or条件。对于in中有大量值时，in查询比or快

*  mysql 文件排序需要的空间比想象的要大得多，因为mysql会给排序记录分配足够长度的固定长度的空间，排序消耗的临时空间比磁盘上原有空间大得多。关联查询排序中，mysql有两种处理方式:
order by 子句所有列来自第一个表，mysql在关联第一个表的时候就进行文件排序，explain 中可以看到extra 显示using filesort.
其他情况，会将结果集放到一个临时表，再对临时表进行排序 extra 中显示using temporary;using filesort;mysql 5.6之后，如果使用了limit,mysql不再对所有数据进行排序。而是根据实际情况抛弃不满足条件的结果然后再排序。