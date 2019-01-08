---
layout: post
title: 从Laravel,Yii,Thinkphp中学习php 操作数据库的事务嵌套
category: PHP
comments: true
description: 对比Laravel,Yii,Thinkphp，分析php数据库事务嵌套
keywords: 事务嵌套,Laravel,Yii,Thinkphp
---


最近维护历史代码，使用的是phalapi 最初版本开发，数据库操作使用的是notorm。notorm本身不支持事务嵌套，但是在开发过程中，多个操作进行拆分，根据不同业务不同进行调用，必然会设计到多个事务嵌套在一起的问题。举个栗子:

1) 公共模块A,更新用户的账户余额，添加流水记录操作。
2) 模块B,根据用户的操作（消费或充值）根据活动配置赠送相应的优惠券。
基础业务A模块就够用了，但是出现一些业务活动的时候，需要在A成功之后调用B模块，只有两个操作成功之后才完整提交事务。要实现这样的功能，无非两种方式:

1) 模块内部不加事务，事务控制统一交给调用方。谁调用，谁负责事务。内部模块只提供内部模块执行结果。
2) 模块内部控制事务，外部调用只需知道内部执行是否成功。

如果设计的合理，还是比较倾向于使用第一种方案。但是不可否认，你无法确定你的调用模块会不会再被其他人调用，最终结果又演变成第二种方案。因此底层还是需要支持事务嵌套。

<!-- more -->

嵌套事务的核心思想就是添加一个计数器，第一次开启事务，最后一次提交或回滚执行数据库操作，其他情况只是更新计数器数值。分别看一看几个现有框架如何设计数据库事务嵌套操作:

1) Laravel
Laravel与事务相关操作封装在 Illuminate\Database\Concerns\ManagesTransactions当中。

```php
    public function beginTransaction()
    {
        $this->createTransaction();

        $this->transactions++;

        $this->fireConnectionEvent('beganTransaction');
    }
    protected function createTransaction()
    {
        if ($this->transactions == 0) {
            try {
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                $this->handleBeginTransactionException($e);
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->createSavepoint();
        }
    }
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }
    public function rollBack($toLevel = null)
    {
        // We allow developers to rollback to a certain transaction level. We will verify
        // that this given transaction level is valid before attempting to rollback to
        // that level. If it's not we will just return out and not attempt anything.
        $toLevel = is_null($toLevel)
                    ? $this->transactions - 1
                    : $toLevel;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        // Next, we will actually perform this rollback within this database and fire the
        // rollback event. We will also set the current transaction level to the given
        // level that was passed into this method so it will be right from here out.
        $this->performRollBack($toLevel);

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');
    }

```

2) Yii2

Yii2的事务操作也是有一个单独文件进行封装，yii\db\Transaction。

```php
    public function begin($isolationLevel = null)
    {
        if ($this->db === null) {
            throw new InvalidConfigException('Transaction::db must be set.');
        }
        $this->db->open();

        if ($this->_level === 0) {
            if ($isolationLevel !== null) {
                $this->db->getSchema()->setTransactionIsolationLevel($isolationLevel);
            }
            Yii::debug('Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : ''), __METHOD__);

            $this->db->trigger(Connection::EVENT_BEGIN_TRANSACTION);
            $this->db->pdo->beginTransaction();
            $this->_level = 1;

            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::debug('Set savepoint ' . $this->_level, __METHOD__);
            $schema->createSavepoint('LEVEL' . $this->_level);
        } else {
            Yii::info('Transaction not started: nested transaction not supported', __METHOD__);
        }
        $this->_level++;
    }
    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->_level--;
        if ($this->_level === 0) {
            Yii::debug('Commit transaction', __METHOD__);
            $this->db->pdo->commit();
            $this->db->trigger(Connection::EVENT_COMMIT_TRANSACTION);
            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::debug('Release savepoint ' . $this->_level, __METHOD__);
            $schema->releaseSavepoint('LEVEL' . $this->_level);
        } else {
            Yii::info('Transaction not committed: nested transaction not supported', __METHOD__);
        }
    }
    public function rollBack()
    {
        if (!$this->getIsActive()) {
            // do nothing if transaction is not active: this could be the transaction is committed
            // but the event handler to "commitTransaction" throw an exception
            return;
        }

        $this->_level--;
        if ($this->_level === 0) {
            Yii::debug('Roll back transaction', __METHOD__);
            $this->db->pdo->rollBack();
            $this->db->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);
            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::debug('Roll back to savepoint ' . $this->_level, __METHOD__);
            $schema->rollBackSavepoint('LEVEL' . $this->_level);
        } else {
            Yii::info('Transaction not rolled back: nested transaction not supported', __METHOD__);
            // throw an exception to fail the outer transaction
            throw new Exception('Roll back failed: nested transaction not supported.');
        }
    }
```

3) Thinkphp5

Thinkphp5的事务操作并没有独立分开，直接在数据库连接类当中think\db\Connection;

```php
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }
        ++$this->transTimes;
        if (1 == $this->transTimes) {
            $this->linkID->beginTransaction();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepoint('trans' . $this->transTimes)
            );
        }
    }
    public function commit()
    {
        $this->initConnect(true);

        if (1 == $this->transTimes) {
            $this->linkID->commit();
        }

        --$this->transTimes;
    }
    public function rollback()
    {
        $this->initConnect(true);

        if (1 == $this->transTimes) {
            $this->linkID->rollBack();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepointRollBack('trans' . $this->transTimes)
            );
        }

        $this->transTimes = max(0, $this->transTimes - 1);
    }

```

三个框架都是通过计数器以及数据库本身的"部分事务"支持嵌套事务的操作。MYSQL 中通过 savepoint 的方式来实现只提交事务的一部分。操作流程大体分一下三步
1) 开启事务，检查计数器是否是第一次开启，如果是则执行pdo开启事务，不是则修改计数器的值，同时根据是否支持部分事务，执行pdo savepoint操作。
2) 事务提交，检查计数器是否是最外层事务，是则执行pdo事务提交操作，否则计数器减1
3) 事务回滚，检查计算器是否是最外层操作，是则执行pdo事务回滚，否则计数器减1，同时根据是否支持部分事务，执行pdo rollbak to savepoint 操作


虽然整体思路一样，但是三个框架根据自身的特定，代码设计抽象程度不一样。从这个相同的功能，也能够很好的体会三个框架不同的设计方式。


