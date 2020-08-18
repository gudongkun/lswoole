<?php
namespace gudongkun\lswoole;

use Illuminate\Console\Command as IlluminateCommand;
 use Exception;
/**
 * Class Command
 * @package Star\LumenSwoole
 * 提供命令lumen命令行和swoole进程支持
 *
 * //todo 重新加载php， 测试 web网站的正确性。
 */
class Command extends IlluminateCommand
{
    public $isDaemon = false;

    protected $signature = 'lswoole {action : 管理swoole-lumen的http服务的基本口令} {--daemon}';

    protected $description = '通过以下口令管理swoole-lumen服务器： start | restart | reload | stop | status';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'start':
                $this->start();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'status':
                $this->status();
                break;
            default:
                $this->error('请输入正确的管理口令： start | restart | stop | reload | status');
        }
    }

    /**
     * 开启主进程
     */
    protected function start()
    {
        //1 生成pid文件
        $pid = $this->checkPidFile();
        if($pid){
            $this->info("启动lswoole失败,已经再运行pid：{$pid}") ;
            return;
        };

        if( $this->option('daemon') or $this->isDaemon){
            if($this->makeDaemon()){
                return;
            }
        }
        $this->initPidFile();
        //2 修改进程名
        swoole_set_process_name("lswoole master");
        //3 进入lswoole主逻辑
        app()->make('lswoole')->run();
        exit;
    }
    /**
     * 如果进程存在返回进程id，不存在返回0.进程文件被其他程序占用，抛出异常
     */
    protected function checkPidFile(){
        $pid_file = config('swoole.pid_file');
        //1 文件不存在，直接返回0
        if(!file_exists($pid_file)) {;
            return 0;
        }
        $pid = intval(file_get_contents($pid_file));
        //2 进程文件存在 pid为0删除pid文件
        if($pid == 0){
            unlink($pid_file);
            if(file_exists($pid_file)) {
                throw new Exception("pid文件：$pid_file,已经失效，但是删除失败");
            }
            return 0;
        }
        //3 通过进程id无法获得进程组id证明进程不存在,默认组id和进程id一致.
        $gid = posix_getpgid($pid);
        if($gid == 0){
            unlink($pid_file);
            if(file_exists($pid_file)) {
                throw new Exception("pid文件：$pid_file,已经失效，但是删除失败");
            }
            return 0;
        }
        //4 进程存在，但是不是，当前程序生成的，报exception
        $info =  file_get_contents("/proc/{$pid}/cmdline");
        if(!preg_match('/lswoole/',$info)){
            throw new Exception("pid文件被，其他程序占用{$info},请手动删除{$pid_file}");
        }
        return $pid;
    }
    /**
     * 生成pid文件,并锁定文件
     * @throws Exception
     */
    protected function initPidFile(){
        //1 创建pid文件
        $pid_file = config('swoole.pid_file');
        $f = fopen($pid_file,"w+");
        // 锁定pid文件
        if (flock($f,LOCK_EX)) {
            file_put_contents($pid_file,posix_getpid());
        } else {
            throw new Exception("无法锁定pid文件");
        }
    }
    /**
     * 守护进程化当前进程，原主进程返回真，守护进程中返回假。
     * @return bool
     */
    public function makeDaemon(){
        ////   守护进程化当前进程 //
        // 重设文件创建掩码
        umask( 0 );
        //把程序变成守护进程
        //新建进程，$pid为子进程id。只有父进程中值不是0
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("脱离当前会话的进程成创建失败，守护进程化失败。");
        }
        else if ($pid) {
            //父进程直接退出
            return true;
        }
        // 子进程称为新的会话头领，会话头领存进程终止，会话就会终止。
        if( !posix_setsid() ){
            die("进程脱离会话失败，进程终止");
        }
        //创建孙子进程，关闭儿子进程从而关闭会话头领，使会话终止。孙子进程脱离会话称为守护进程。
        $pid = pcntl_fork();
        if( $pid  < 0 ){
            exit('守护进程成创建失败，守护进程化失败。');
        } else if( $pid > 0 ) {
            exit;
        }
        // 改变工作目录
        chdir( '/tmp' );
//        fclose(STDIN);
//        fclose(STDOUT);
//        fclose(STDERR);
        return false;
    }

    /**
     * 重启lswoole
     * @throws Exception
     */
    protected function restart()
    {
        $pid = $this->checkPidFile();
        if(!$pid){
            $this->info('lswoole未运行');
        } else {
            posix_kill($pid, SIGKILL);
            $this->info('正在停止lswoole...:');
            while(1){
                $pid = $this->checkPidFile();
                if($pid){
                    echo '.';
                    sleep(1);
                } else {
                    break;
                }
            }
            $this->info('lswoole停止成功');
        }
        sleep(1);
        $this->info('lswoole正在起动');
        $this->isDaemon = true;
        $this->start();
        $this->info('lswoole重启成功');
    }

    /**
     * 平滑重启work进程
     * @throws Exception
     */
    protected function reload()
    {
        $pid = $this->checkPidFile();
        posix_kill($pid, SIGKILL);
        $pid = $this->checkPidFile();
        if(!$pid){
            $this->info('lswoole进程并未开启');
        } else {
            posix_kill($pid, SIGUSR1);
        }

    }
    /**
     * 关闭当前运行中的lswoole
     * @throws Exception
     */
    protected function stop()
    {
        $pid = $this->checkPidFile();
        if(!$pid){
            $this->info('lswoole进程并未开启');
        } else {
            posix_kill($pid, SIGKILL);
        }
    }
    /**
     * lswoole运行状态查询
     * @throws Exception
     */
    protected function status()
    {
        $pid = $this->checkPidFile();
        if ($pid) {
            $this->info('lswoole正在运行 master pid : ' . $pid);
        } else {
            $this->info('lswoole未运行!');
        }
    }
}