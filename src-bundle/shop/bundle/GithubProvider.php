<?php

namespace shop\bundle;

use develnext\bundle\httpclient\HttpClientBundle;
use bundle\http\HttpClient;
use bundle\http\HttpResponse;
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
        Logger::error(class_exists(HttpClientBundle::class));

        $this->update();
    }

    public function update()
    {
        Logger::info("send http request " . $this->urlBundleList);
        $response = json_decode(file_get_contents($this->urlBundleList), true); // $this->http->get($this->urlBundleList);

        $this->list = Flow::of($response)->map(function ($item) {
            return Bundle::of($item);
        })->toArray();
    }


}