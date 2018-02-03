<?php

namespace Chenmobuys\LaravelSwoole\Traits;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait HttpTrait
{
    protected $accept_gzip = false;

    protected function handleResponse($response, $illuminateResponse, $accept_encoding = '')
    {

        $accept_gzip = $this->accept_gzip && stripos($accept_encoding, 'gzip') !== false;

        // status
        $response->status($illuminateResponse->getStatusCode());
        // headers
        $response->header('Server', config('laravoole.base_config.server'));
        foreach ($illuminateResponse->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }
        // cookies
        foreach ($illuminateResponse->headers->getCookies() as $cookie) {
            $response->rawcookie(
                $cookie->getName(),
                urlencode($cookie->getValue()),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        // content
        if ($illuminateResponse instanceof BinaryFileResponse) {
            $content = function () use ($illuminateResponse) {
                return $illuminateResponse->getFile()->getPathname();
            };
            if ($accept_gzip && isset($response->header['Content-Type'])) {
                $size = $illuminateResponse->getFile()->getSize();
            }
        } else {
            $content = $illuminateResponse->getContent();
            // check gzip
//            if ($accept_gzip && isset($response->header['Content-Type'])) {
//                $mime = $response->header['Content-Type'];
//
//                if (strlen($content) > 1024 && $this->is_mime_gzip($mime)) {
                        //方法不存在
//                    $response->gzip(5);
//                }
//            }
        }
        return $this->endResponse($response, $content);
    }

    protected function is_mime_gzip($mime)
    {
        static $mimes = [
            'text/plain' => true,
            'text/html' => true,
            'text/css' => true,
            'application/javascript' => true,
            'application/json' => true,
            'application/xml' => true,
        ];
        if ($pos = strpos($mime, ';')) {
            $mime = substr($mime, 0, $pos);
        }
        return isset($mimes[strtolower($mime)]);
    }

}
