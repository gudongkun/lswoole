<?php


namespace gudongkun\lswoole;


class Request
{
    /**
     * 把swoole请求转化成lumen请求。
     * @param \swoole_http_request $request
     * @return \Illuminate\Http\Request
     */
    public static function toLumen(\swoole_http_request $request,  \Illuminate\Http\Request $http_request){
        //1.修改全局变量
        //server信息
        if (isset($request->server)) {
            foreach ($request->server as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        //header头信息
        if (isset($request->header)) {
            foreach ($request->header as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        //get请求
        if (isset($request->get)) {
            foreach ($request->get as $k => $v) {
                $_GET[$k] = $v;
            }
        }

        //post请求
        if (isset($request->post)) {
            foreach ($request->post as $k => $v) {
                $_POST[$k] = $v;
            }
        }
        //文件请求
        if (isset($request->files)) {
            foreach ($request->files as $k => $v) {
                $_FILES[$k] = $v;
            }
        }
        //cookies请求
        if (isset($request->cookie)) {
            foreach ($request->cookie as $k => $v) {
                $_COOKIE[$k] = $v;
            }
        }



        $get     = isset($request->get) ? $request->get : array();
        $post    = isset($request->post) ? $request->post : array();
        $cookie  = isset($request->cookie) ? $request->cookie : array();
        $server  = isset($request->server) ? $request->server : array();
        $header  = isset($request->header) ? $request->header : array();
        $files   = isset($request->files) ? $request->files : array();
        $fastcgi = array();

        $new_server = array();
        foreach ($server as $key => $value) {
            $new_server[strtoupper($key)] = $value;
        }
        foreach ($header as $key => $value) {
            $new_server['HTTP_' . strtoupper($key)] = $value;
        }

        $content = $request->rawContent() ?: null;
        $http_request =$http_request->duplicate($get, $post, $fastcgi, $cookie, $files, $new_server, $content);
        return $http_request;
    }
    //
    public static function  makeGlobal(\swoole_http_request $request){
        //1.修改全局变量
        //server信息
        global $_SERVER;
        if (isset($request->server)) {
            foreach ($request->server as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        //header头信息
        if (isset($request->header)) {
            foreach ($request->header as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        //get请求
        global $_GET;
        if (isset($request->get)) {
            foreach ($request->get as $k => $v) {
                $_GET[$k] = $v;
            }
        }

        //post请求
        global $_POST;
        if (isset($request->post)) {
            foreach ($request->post as $k => $v) {
                $_POST[$k] = $v;
            }
        }
        //文件请求
        global $_FILES;
        if (isset($request->files)) {
            foreach ($request->files as $k => $v) {
                $_FILES[$k] = $v;
            }
        }
        //cookies请求
        global $_COOKIE;
        if (isset($request->cookie)) {
            foreach ($request->cookie as $k => $v) {
                $_COOKIE[$k] = $v;
            }
        }
    }
}