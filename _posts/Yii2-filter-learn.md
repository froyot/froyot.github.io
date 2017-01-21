---
layout: post
title: Yii2 filter 学习
category: PHP
comments: true
description: " Yii2 filter 源码学习 "
---


过滤器是 控制器 动作 执行之前或之后执行的对象。 例如访问控制过滤器可在动作执行之前来控制特殊终端用户是否有权限执行动作， 内容压缩过滤器可在动作执行之后发给终端用户之前压缩响应内容。过滤器可包含 预过滤（过滤逻辑在动作之前） 或 后过滤（过滤逻辑在动作之后）， 也可同时包含两者。

过滤器本质上是一类特殊的行为，所以使用过滤器和使用行为一样。 可以在控制器类中覆盖它的 yii\base\Controller::behaviors() 方法来申明过滤器， 如下所示：

```
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\HttpCache',
                'only' => ['index', 'view'],
                'lastModified' => function ($action, $params) {
                    $q = new \yii\db\Query();
                    return $q->from('user')->max('updated_at');
                },
            ],
        ];
    }

```

Yii中filter继承于\yii\base\ActionFilter,在ActionFilter中可配置的参数:

*   only 需要验证的actionId 列表
*   except 忽略的actionid列表

在ActionFilter中通过isActive对当前actionid在only和except中进行查找，判断是否应用过滤器。


## Yii 的核心控制器

*    yii\\filters\\AccessControl访问过滤器

    访问过滤器用于在action之前，根据配置的rules,对请求方法以及用户是否登陆进行验证。
    因此过滤器验证失败，如果是用户未登录，则调用yii\\web\\User中loginRequired方法引
    导用户到登陆页

```
    public function behaviors()
    {
      return [
          'access' => [
              'class' => \yii\filters\AccessControl::className(),
              'only' => ['create', 'update'],
              'rules' => [
                  [
                      'allow' => false,
                      'verbs' => ['POST'],
                      //'ips'=>[],
                      //'verbs'=>[],
                      //'matchCallback'=>null,
                      //'actions'=>[]
                  ],
                  [
                      'allow' => true,
                      'roles' => ['@'],
                      //'ips'=>[],
                      //'verbs'=>[],
                      //'matchCallback'=>null,
                      //'actions'=>[]
                  ],

              ],
              //'denyCallback'=>function($rule,$action){},
              //'ruleConfig'=>['class' => 'yii\filters\AccessRule']
          ],
      ];
    }

 ```
AccessControl中配置的rule会根据ruleConfig实例化rule对象,默认是AccessRule。
AccessRule会对请求的用户角色，用户ip是,action的请求方法,以及用户的自定义验证进行判断。

```
{
    if ($this->matchAction($action)
        && $this->matchRole($user)
        && $this->matchIP($request->getUserIP())
        && $this->matchVerb($request->getMethod())
        && $this->matchController($action->controller)
        && $this->matchCustom($action)
    ) {
        return $this->allow ? true : false;
    } else {
        return null;
    }
}

```

*   yii\\filters\\RateLimiter访问评论过滤器

    访问平率过滤器用于限制同一个操作执行频率。要使用Yii访问平率过滤器，需要让用户类实现接口yii\\filters\\RateLimitInterface。

```

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $user = $this->user ? : (Yii::$app->getUser() ? Yii::$app->getUser()->getIdentity(false) : null);
        if ($user instanceof RateLimitInterface) {
            Yii::trace('Check rate limit', __METHOD__);
            $this->checkRateLimit(
                $user,
                $this->request ? : Yii::$app->getRequest(),
                $this->response ? : Yii::$app->getResponse(),
                $action
            );
        } elseif ($user) {
            Yii::info('Rate limit skipped: "user" does not implement RateLimitInterface.', __METHOD__);
        } else {
            Yii::info('Rate limit skipped: user not logged in.', __METHOD__);
        }
        return true;
    }

    /**
     * Checks whether the rate limit exceeds.
     * @param RateLimitInterface $user the current user
     * @param Request $request
     * @param Response $response
     * @param \yii\base\Action $action the action to be executed
     * @throws TooManyRequestsHttpException if rate limit exceeds
     */
    public function checkRateLimit($user, $request, $response, $action)
    {
        $current = time();

        list ($limit, $window) = $user->getRateLimit($request, $action);
        list ($allowance, $timestamp) = $user->loadAllowance($request, $action);

        $allowance += (int) (($current - $timestamp) * $limit / $window);
        if ($allowance > $limit) {
            $allowance = $limit;
        }

        if ($allowance < 1) {
            $user->saveAllowance($request, $action, 0, $current);
            $this->addRateLimitHeaders($response, $limit, 0, $window);
            throw new TooManyRequestsHttpException($this->errorMessage);
        } else {
            $user->saveAllowance($request, $action, $allowance - 1, $current);
            $this->addRateLimitHeaders($response, $limit, $allowance - 1, (int) (($limit - $allowance) * $window / $limit));
        }
    }

```

请求限制的判断确实比较精辟,而且判断是时间窗内总的请求次数。假设$current-$timestamp = 0.1s,$limit=10 $window=5。
上一次保存$allowance=0;则当前allowance = 5+0 = 5,合法请求。如果是根据频率限制，0.1s一次的请求则应该属于非法请求。

```
        $allowance += (int) (($current - $timestamp) * $limit / $window);
        if ($allowance > $limit) {
            $allowance = $limit;
        }

        if ($allowance < 1)

```


*   yii\\filters\\VerbFilter,action请求方式控制。并在header中设置Allow 请求头。

*   yii\\filters\\HttpCache,实现httpCache功能。分为前置过滤和后置过滤。在action开始之前对请求提交的lastModified以及etag进行验证，如果验证数据没变更，
直接返回304状态;在action之后，设置etag以及lastModifed头。

```
    /**
     * Validates if the HTTP cache contains valid content.
     * @param integer $lastModified the calculated Last-Modified value in terms of a UNIX timestamp.
     * If null, the Last-Modified header will not be validated.
     * @param string $etag the calculated ETag value. If null, the ETag header will not be validated.
     * @return boolean whether the HTTP cache is still valid.
     */
    protected function validateCache($lastModified, $etag)
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE
            // http://tools.ietf.org/html/rfc7232#section-3.3
            return $etag !== null && in_array($etag, Yii::$app->request->getETags(), true);
        } elseif (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return $lastModified !== null && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified;
        } else {
            return $etag === null && $lastModified === null;
        }
    }

```
*    yii\\filters\\PageCache 对请求action生成页面缓存。通过beginCache判断缓存是
否存在,如果缓存存在，则直接返回缓存内容,否则执行action,在action之后，保存缓存内容。

```
    if ($this->view->beginCache($id, $properties)) {
        $response->on(Response::EVENT_AFTER_SEND, [$this, 'cacheResponse']);
        return true;
    } else {
        $data = $this->cache->get($this->calculateCacheKey());
        if (is_array($data)) {
            $this->restoreResponse($response, $data);
        }
        $response->content = ob_get_clean();
        return false;
    }

```







