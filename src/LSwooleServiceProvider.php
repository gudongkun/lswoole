<?php
namespace gudongkun\lswoole;

use Illuminate\Support\ServiceProvider;

/**
 * lswoole的服务提供者，把lswoole命名注册到lumen的容器中。
 * 使用方法：
 * $app->register(\gudongkun\lswoole\LSwooleServiceProvider::class);
 * Class LSwooleServiceProvider
 * @package gudongkun\lswoole
 */
class LSwooleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            Command::class,
        ]);
        $this->app->singleton('lswoole', function ($app) {
            $this->mergeConfigFrom(
                __DIR__ . '/../config/swoole.php', 'swoole'
            );
            $swooleConfig = $app['config']['swoole'];
            return new LSwooleServer($swooleConfig);
        });
    }
}