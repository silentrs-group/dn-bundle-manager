<?php

namespace shop\bundle;

use ide\Logger;
use shop\internal\Http;

class GithubProvider extends BaseBundleProvider
{
    const BASE_HOST = 'https://raw.githubusercontent.com/silentrs-group/dn-bundles/main/';

    /**
     * @var HttpClient
     */
    private $http;


    public function init()
    {

    }

    public function update()
    {
        try {
            $this->list = Http::get(self::BASE_HOST . 'bundle-list-v2.json', "json");
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
        }

        return $this->list;
    }
}