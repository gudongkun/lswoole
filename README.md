# lswoole

### 一、说明

lswoole 是用swoole来加速lumen的http服务器；

基于php7 lumen 7 和 swoole 4.0+；

由于代码只被加载解析一次，基本免除了lumen框架，运行效率低的问题；

由于本人水平有限，难免出现纰漏，欢迎大家指正和提出宝贵意见。

邮箱：952142073@qq.com

作者：gudk

### 二、使用

（1）安装软件包：

```
# composer require gudongkun/lswoole
```

（2）修改{项目目录}/bootstrap/app.php文件添加：

```php
$app->register(\gudongkun\lswoole\LSwooleServiceProvider::class);
$app->configure('swoole');
```

（3）添加配置文件：{项目目录}/config/swoole.php

```php
<?php
return [
    'host' => '0.0.0.0',
    'port' => 8080,
    'worker_num' => 4,
    'max_request' => 5000,
    'pid_file' => storage_path('logs/swoole.pid'),
    'enableGlobals'=>false
];
```

(4)执行命令开启服务

```
//交互式开启服务
# php artisan lswoole start
//守护进程方式开启服务器
# php artisan lswoole start --daemon
//关闭服务
# php artisan lswoole stop
//重启服务
# php artisan lswoole restart
//热加载lumen代码
# php artisan lswoole reload
```

（5）路由示例

```
$app->router->get('/get', function (Illuminate\Http\Request $request) use ($app) {
	 //依赖注入方式取得 ，lumen Request对象 
	 $request->all()
	 //取得swoole requset对象
	 $swooleRequest = $this->app->make('swoole_http_request')
	 //取得swoole response对象
	 $swooleResponse = $this->app->make('swoole_http_response')
	 //lumen默认并没有开启session，使用session需要开启，参考第三章。
     $data = $request->session()->all();
     //lumen方式返回结果
     return $data;
});
```



### 三、session

swoole的session功能需要自己实现，lumen 实现了自己的session功能，但是默认没有开启。可以使用以下方式开启。

注意：swoole 并不支持$_SESSION超全局变量，本身也不建议我们创建超全局变量，lswoole中开启session后需要用，lumen方式使用session

（1）修改{项目目录}/bootstrap/app.php文件添加：

```php
 	//开启session
    $app->register(Illuminate\Session\SessionServiceProvider::class);
    $app->middleware([Illuminate\Session\Middleware\StartSession::class]);
    $app->alias('session', 'Illuminate\Session\SessionManager');
    $app->configure('session');
```

（2）添加配置文件：{项目目录}/config/session.php

```php
<?php
return [
    'driver' => env('SESSION_DRIVER', 'file'),//默认使用file驱动，你可以在.env中配置
    'lifetime' => 120,//缓存失效时间
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('sessions'),//file缓存保存路径
    'connection' => null,
    'table' => 'sessions',
    'lottery' => [2, 100],
    'cookie' => 'laravel_session',
    'path' => '/',
    'domain' => null,
    'secure' => false,
];
```

（4）使用

```php
$app->router->get('/get', function (Illuminate\Http\Request $request) use ($app) {
     //获取session实例
     $session = $request->session();
     //使用
     $data = $session->all();
     return $data;
});
```




### 四、优势

兼容lumen :结构简单对lumen的侵入小,原来nginx+fpm方式开发的lumen代码基本无需修改。

效率高：完全遵从 swoole4.0的最新开发标准，代码只被实例化一次，其他运行期间都是通过，copy原型的方式

容易扩展：本代码结构简单，只有五个类，主逻辑的处理更是只有LSwooleServer一个类。代码逻辑非常清晰简单。方便大家扩展自己的功能。

swoole进入4.0以后，添加了很多功能，很多逻辑的处理也已经变成对开发者透明，本项目充分利用了这些特性，避免了充分造轮子的过程。

侵入性小：lumen进入7.0以后，有很多性能和结构上的优化和改变，使得一些老的代码出现了兼容性问题，或者为了比较好的兼容性。采用了大量复杂结构如事件等，导致代码复杂，运行效率低下。

本项目根据最新的 lumen7.0+和swoole4.0+,用最简单最直接方式引入swoole（其中包含的是作者大量肝代码，如果觉得本项目有用请不吝点赞）





### 五、测试

项目中有完整的单元测试。执行方式如下

```
 # phpunit tests/TestLSwooleServiceProvider.php
```



### 六、原理

```
1 onWorkerStart 方法中，创建lumen 的$app 对象
3 onRequest 方法中调用 handle方法处理请求
 (1) 把swoole请求对象转化成lunem对象
 (2) 调用 $app->handle($illuminateRequest);处理请求。
 (3) 把lumen 返回对象，转化成swoole 返回对象，并调用swoole返回

```

