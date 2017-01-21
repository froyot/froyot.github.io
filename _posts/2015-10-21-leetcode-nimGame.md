---
layout: post
title: nimGame
category: 题趣
comments: true
description: "Nim Game,remove the stones."
---


You are playing the following Nim Game with your friend: There is a heap of
stones on the table, each time one of you take turns to remove 1 to 3 stones.
The one who removes the last stone will be the winner. You will take the first
turn to remove the stones.

Both of you are very clever and have optimal strategies for the game. Write a
function to determine whether you can win the game given the number of stones
in the heap.

For example, if there are 4 stones in the heap, then you will never win the
game: no matter 1, 2, or 3 stones you remove, the last stone will always be
removed by your friend.

以上是leetcode中的一道编程题目。实在惭愧，弄了半天没明白，最后发现题目关键是4
个的时候第一个人必定无法赢。一下是解题:

```php

<?php
/**
 * nimGame,给定石头数目，每次只能从中拿1到3个石头。
 * 思路:
 * 每次开始拿的时候，如果石头的数目是4个，则开始拿的人则输。
 * 因此判断第一个人是一定能赢就是判断石头数目是否是4的整数倍。
 * 保证每次必定赢的策略是留给下个人4的整数个。比如他拿k个，自己就拿4-k个
 * @param  int $number strone of number
 * @return boolean          if the firster can win
 */
function nimGame( $number )
{
    if( $number%4 != 0 )
    {
        return 1;
    }
    else
        return 2;
}
?>

```
