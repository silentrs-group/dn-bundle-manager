<?php

namespace shop\internal;

use ide\Logger;
use php\lib\str;
use php\net\SocketException;
use php\net\URLConnection;

class Http
{
    public static function get($url, $responseType = "")
    {
        Logger::info("Send http request to: " . $url);

        $connection = URLConnection::create($url);

        $connection->setRequestProperty("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0");
        $connection->setRequestProperty("Content-Type", "*/*; charset=UTF-8");
        $connection->setRequestProperty("Referer", "https://internal.loc/req");

        try {
            if ($connection->responseCode != 200) {
                Logger::error(sprintf("Message: %s; code: %s; url: %s;", $connection->responseMessage, $connection->responseCode, $url));
                return null;
            }

            switch (str::lower($responseType)) {
                case 'json':
                    return json_decode($connection->getInputStream()->readFully(), true);

                case 'stream':
                    return $connection->getInputStream();

                default:
                    return $connection->getInputStream()->readFully();
            }

        } catch (\Exception $exception) {
            Logger::error($exception->getMessage() . '::' . $exception->getLine());
            throw new SocketException($exception->getMessage());
        }

        return null;
    }
}