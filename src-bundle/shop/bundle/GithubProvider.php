<?php

namespace shop\bundle;

use develnext\bundle\bundlemanager\BundleManageBundle;
use ide\Logger;
use shop\internal\Http;

class GithubProvider extends BaseBundleProvider
{
    const BASE_HOST = 'https://raw.githubusercontent.com/silentrs-group/dn-bundles/main/';

    public function init()
    {

    }

    public function update()
    {
        try {
            $this->list = Http::get(self::BASE_HOST . 'bundle-list-v3.json', "json");
        } catch (\Exception $exception) {
            Logger::error($exception->getMessage());
            try {
                BundleManageBundle::$loggerReporter
                    ->discord("Exception method: " . __METHOD__ . "; line: " . __LINE__ . ";\n```text\n" . $exception->getTraceAsString() . "```")
                    ->send();
            } catch (\Exception $ignore) {}
        }

        return $this->list;
    }
}