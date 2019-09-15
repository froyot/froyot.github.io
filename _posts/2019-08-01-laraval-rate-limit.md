
---
layout: post
title: Dingo Api 的限流在Laravel的限流基础上做了哪些修改？
category: php
comments: true
description: Dingo Api 的限流在Laravel的限流基础上做了哪些修改
keywords: Dingo,Laravel，api限流
---


今天看文档的时候看到 Laravel的 节流限速 (throttling) 。网络上搜索，又看到了Dingo 的节流限速的文档。因此查看Laravel 与Dingo的源码，对比两者之间的相同点与不同点。

### 相同点

* 两者都是通过中间件处理请求限流

* 处理方式都是记录缓存key,设置过期时间，在没过期的时候自增，直到超出限制，或key过期

Laravel 限流中间件 ``Illuminate\Routing\Middleware\ThrottleRequests``
```php

public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
{
  $key = $this->resolveRequestSignature($request);

  $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);

  if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
    throw $this->buildException($key, $maxAttempts);
  }

  $this->limiter->hit($key, $decayMinutes);

  $response = $next($request);

  return $this->addHeaders(
    $response, $maxAttempts,
    $this->calculateRemainingAttempts($key, $maxAttempts)
  );
}
```
Dingo 限流中间件``Dingo\Api\Http\Middleware\RateLimit``

```php
public function handle($request, Closure $next)
{
  if ($request instanceof InternalRequest) {
    return $next($request);
  }

  $route = $this->router->getCurrentRoute();

  if ($route->hasThrottle()) {
    $this->handler->setThrottle($route->getThrottle());
  }

  $this->handler->rateLimitRequest($request, $route->getRateLimit(), $route->getRateLimitExpiration());

  if ($this->handler->exceededRateLimit()) {
    throw new RateLimitExceededException('You have exceeded your rate limit.', null, $this->getHeaders());
  }

  $response = $next($request);

  if ($this->handler->requestWasRateLimited()) {
    return $this->responseWithHeaders($response);
  }

  return $response;
}

```

### 不同点

* 从上面两个中间件的代码可以看出，Laravel只有再没有超过限制的情况下才会对缓存进行+1操作，而Dingo是先操作再进行判断

* Dingo 限制key以请求路径hash为前缀，默认以用户ip作为key。因此可以实现对用户每个url的限制，限制粒度更细

``Dingo\Api\Http\RateLimit\Handler``代码如下:

```php
$this->keyPrefix = sha1($request->path());
...
public function getRateLimiter()
{
  return call_user_func($this->limiter ?: function ($container, $request) {
    return $request->getClientIp();
  }, $this->container, $this->request);
}

```
* Laravel 中使用用户信息或域名+ip作为限制key,限制粒度只在用户级别

``Illuminate\Routing\Middleware\ThrottleRequests``代码如下:

```php
  protected function resolveRequestSignature($request)
  {
    if ($user = $request->user()) {
      return sha1($user->getAuthIdentifier());
    }
    if ($route = $request->route()) {
      return sha1($route->getDomain().'|'.$request->ip());
    }
  }
```

* Dingo支持修改限制key，Laravel默认没有支持修改方法

* Dingo支持添加多个限制规则，逻辑上使用限制数最小的进行判断。因此假设有两个限制器，且都符合限制条件。一个限制1分钟10次，另一个限制2分钟15次，会使用1分钟1次的进行限制判断。


* Dingo 返回了过期限制到期时间，Laravel默认不返回限制到期时间

``Dingo\Api\Http\RateLimit\Handler``获取限制最少的限制器代码如下:

```php
public function rateLimitRequest(Request $request, $limit = 0, $expires = 0)
{
  ...
  $this->throttle = $this->getMatchingThrottles()->sort(function ($a, $b) {
        return $a->getLimit() < $b->getLimit();
      })->first();
  ...
}
```

``Dingo\Api\Http\RateLimit\Handler``获取设置的返回头信息代码如下:
```php
protected function getHeaders()
{
  return [
    'X-RateLimit-Limit' => $this->handler->getThrottleLimit(),
    'X-RateLimit-Remaining' => $this->handler->getRemainingLimit(),
    'X-RateLimit-Reset' => $this->handler->getRateLimitReset(),
  ];
}
```

``Illuminate\Routing\Middleware\ThrottleRequests``获取设置的返回头信息代码如下:
```php

protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null)
{
  $headers = [
    'X-RateLimit-Limit' => $maxAttempts,
    'X-RateLimit-Remaining' => $remainingAttempts,
  ];

  if (! is_null($retryAfter)) {
    $headers['Retry-After'] = $retryAfter;
    $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
  }

  return $headers;
}
```

### 总结

两者实现原理相同，只是在细节上Dingo的功能更加强大。Dingo 限制粒度系，限制规则上，可扩展性，灵活性都比Laravel强。



