<?php

namespace shop\bundle;

use bundle\http\HttpClient;
use ide\Logger;
use php\util\Flow;
use shop\dto\Bundle;

class GithubProvider extends BaseBundleProvider
{
    private $urlBundleList = 'https://raw.githubusercontent.com/silentrs-group/dn-bundles/main/bundle-list.json';

    /**
     * @var HttpClient
     */
    private $http;


    public function init()
    {
        $this->http = new HttpClient();
        $this->http->responseType = "JSON";

        $this->update();
    }

    public function update()
    {
        Logger::info("send http request " . $this->urlBundleList);
        $response = $this->http->get($this->urlBundleList);

        $this->list = Flow::of($response->body())->map(function ($item) {
            return Bundle::of($item);
        })->toArray();
    }


}