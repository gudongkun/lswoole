<?php

/**
 * todo: 1 worker数量配置; 2 请求对象转化; 3 返回对象转化 4 命令行：start -d | restart | status | stop | reload 实现
 */
namespace gudongkun\lswoole;


use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Application;
use Star\LumenSwoole\Inotify;

class LSwooleServer
{
    protected $config;
    protected $server;
    protected $app;

    /**
     * 初始化配置文件和swoole_http_server对象
     * LSwooleServer constructor.
     * @param $lswooleConfig
     */
    public function __construct($lswooleConfig)
    {
        $this->config = $lswooleConfig;
        $this->server = new \swoole_http_server($this->config['host'], $this->config['port']);
    }

    /**
     * lswoole入口函数
     */
    public function run()
    {
        /**
         * workerStart 进程开启时生成app实例；
         * request     接收到请求时：
         * (1) 把swoole请求对象转化成lunem对象
         * (2) 调用 $app->handle($illuminateRequest);处理请求。
         * (3) 把lumen 返回对象，转化成swoole 返回对象，并调用swoole返回
         */
        unset($this->config['host'], $this->config['port']);
        $this->server->set($this->config);

        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('managerStart', [$this, 'onManagerStart']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->start();
    }

    public function onStart(){
        swoole_set_process_name('lswoole swoole master');
    }

    /**
     *
     */
    public function onManagerStart()
    {
        swoole_set_process_name('lswoole swoole manager');
    }

    /**
     * 开启swoole进程时，初始化$app对象
     * @param \swoole_http_server $server
     * @param $worker_id
     */
    public function onWorkerStart(\swoole_http_server $server, $worker_id)
    {
        //每一个swoole进程生成一个lumen对象
        $this->app = Application::getInstance();
        if ($worker_id >= $server->setting['worker_num']) {
            swoole_set_process_name("lswoole task worker");
        } else {
            swoole_set_process_name("lswoole event worker");
        }
        $this->app = Application::getInstance();
    }

    /**
     * 处理每次请求的回调
     * @param \swoole_http_request $swooleRequest
     * @param \swoole_http_response $response
     */
    public function onRequest(\swoole_http_request $swooleRequest, \swoole_http_response $response)
    {
        if($this->config['enableGlobals']){
            Request::makeGlobal($swooleRequest);
        }

        //1 swoole 请求转化lumen请求

        $oldRequest = $this->app->make('request');
        $lumenRequest = Request::toLumen($swooleRequest, $oldRequest);

       // $lumenRequest->setSession($this->app->make('session.store'));
        $app = clone $this->app;
        $app->singleton('request', function ($app) use($lumenRequest){
            return $lumenRequest;
        });
        //2 把swoole_http_request和swoole_http_response注册如框架,方便调用
        $app->singleton('swoole_http_request', function ($app) use($swooleRequest){
            return $swooleRequest;
        });
        $app->singleton('swoole_http_response', function ($app) use($response){
            return $response;
        });
        //3 调用框架处理请求
        $lumenResponse =  $app->handle($lumenRequest);
        //3 处理结果通过swoole方式发送给客户端
        Response::toSwoole($response, $lumenResponse);
    }

}