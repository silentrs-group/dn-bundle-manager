<?php

namespace shop\bundle;

use ide\Logger;
use php\util\Flow;
use shop\dto\Bundle;
use shop\Http;

class GithubProvider extends BaseBundleProvider
{
    private $urlBundleList = 'https://raw.githubusercontent.com/silentrs-group/dn-bundles/main/bundle-list.json';

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
            $this->list = Http::get($this->urlBundleList, "json");
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
        }

        return $this->list;
    }
}