<?php

namespace shop;

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

        $connection->setRequestProperty("User-Agent", "Bundle Manager");
        $connection->setRequestProperty("Content-Type", "application/json; charset=UTF-8");
        $connection->setRequestProperty("Referer", "http://internal.stat/");

        try {
            if ($connection->responseCode != 200) {
                Logger::error(sprintf("Message: %s; code: %s;", $connection->responseMessage, $connection->responseCode));
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