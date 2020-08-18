<?php


namespace gudongkun\lswoole;


class Response
{
    /**
     * 把lumen请求转化成swoole的回复，并发送。
     * @param \swoole_http_response $response
     * @param \Symfony\Component\HttpFoundation\Response $http_response
     */
    public static function toSwoole(\swoole_http_response $response, \Symfony\Component\HttpFoundation\Response $http_response)
    {
        // status
        $response->status($http_response->getStatusCode());
        // headers
        foreach ($http_response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }
        // cookies
        foreach ($http_response->headers->getCookies() as $cookie) {
            $response->rawcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        // content
        $content = $http_response->getContent();
        // send content
        $response->end($content);
    }

}