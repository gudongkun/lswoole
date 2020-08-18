<?php

require_once __DIR__.'/../vendor/autoload.php';

use App\Exceptions\Handler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;


(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));


class TestLSwooleServiceProvider  extends TestCase
{
    public $app;
    public function setUp(){
        $app = new Laravel\Lumen\Application(
            dirname(__DIR__)
        );
        $app->singleton(
            Illuminate\Contracts\Debug\ExceptionHandler::class,
            Laravel\Lumen\Exceptions\Handler::class

        );

        $app->singleton(
            Illuminate\Contracts\Console\Kernel::class,
            Laravel\Lumen\Console\Kernel::class

        );
        //开启session
        $app->register(Illuminate\Session\SessionServiceProvider::class);
        $app->middleware([Illuminate\Session\Middleware\StartSession::class]);
        $app->alias('session', 'Illuminate\Session\SessionManager');
        $app->configure('session');

        $app->register(\gudongkun\lswoole\LSwooleServiceProvider::class);
        $app->configure('swoole');

        $app->router->get('/', function (Illuminate\Http\Request $request) use ($app) {
            $res = $request->all();
            $request->session()->put('my_session', 'test001');
            return $res['action'];
        });
        $app->router->get('/get', function (Illuminate\Http\Request $request) use ($app) {
            $data = $request->session()->all();
            return $data;
        });

        $this->app = $app;

    }
    public function testInitPid()
    {
        $kernel = $this->app->make(
            'Illuminate\Contracts\Console\Kernel'
        );
        $kernel->handle(new ArgvInput(["anything","lswoole","stop"]), new ConsoleOutput);
        sleep(1);
        //1、测试开启服务
        $kernel->handle(new ArgvInput(["anything","lswoole","start","--daemon"]), new ConsoleOutput);
        //  $kernel->handle(new ArgvInput(["anything","lswoole","start"]), new ConsoleOutput);
        sleep(1);
        $res = file_get_contents('http://127.0.0.1:8080/?action=hello_world');
        $this->assertEquals($res,'hello_world');
        $kernel->handle(new ArgvInput(["anything","lswoole","status"]), new ConsoleOutput);
        //2、测试重启服务
        //   因为多进程的矛盾，start --daemon 和 restart 不能在同一个进程中测试，需要单独分开测试。
//        $kernel->handle(new ArgvInput(["anything","lswoole","restart"]), new ConsoleOutput);
//        sleep(2);
        $kernel->handle(new ArgvInput(["anything","lswoole","status"]), new ConsoleOutput);
       // 3.测试关闭服务
        $kernel->handle(new ArgvInput(["anything","lswoole","stop"]), new ConsoleOutput);
        sleep(1);
        $kernel->handle(new ArgvInput(["anything","lswoole","status"]), new ConsoleOutput);
        return;
    }

}

