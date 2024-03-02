<?php

namespace shop\bundle;

use http\Exception\RuntimeException;
use ide\commands\BundleManagerCommand;
use ide\Ide;
use ide\Logger;
use php\lib\str;
use php\util\Flow;
use shop\dto\Bundle;
use shop\internal\Cache;

class CacheProvider extends BaseBundleProvider
{
    const UPDATE_TIMEOUT = 30 * 60 * 1000;
    /**
     * @var LocalProvider
     */
    private $local;

    /**
     * @var GithubProvider
     */
    private $github;

    private $cacheFile = 'bundle-list.cache';

    public function setLocal($value)
    {
        $this->local = $value;
    }

    public function setGithub($value)
    {
        $this->github = $value;
    }

    public function init()
    {
        // Logger::info("Cache loaded");
    }

    public function update()
    {
        $cache = Ide::get()->getUserHome() . BundleManagerCommand::CACHE_DIR . '\\' . $this->cacheFile;

        if (($githubList = Cache::get($cache, false)) === false) {
            $this->github->update();
            Cache::make($cache, json_encode($this->github->getList()), false, self::UPDATE_TIMEOUT);
            $githubList = Cache::get($cache, false);
        }

        $githubList = json_decode($githubList, true);

        if (!is_array($githubList)) {
            throw new RuntimeException("Response data is broken");
        }

        $githubList["bundle"] = Flow::of($githubList["bundle"])->map([$this, 'sort'])->toArray();
        $githubList["fonts"] = Flow::of($githubList["fonts"])->map([$this, 'sort'])->toArray();
        $githubList["icons"] = Flow::of($githubList["icons"])->map([$this, 'sort'])->toArray();

        $this->list = $githubList;

        // merge and sort with local bundles, todo: i think need remove merge and keep only sorting
        $this->list["bundle"] = Flow::of($this->mergeList($this->local->getList(), $githubList["bundle"]))->sort(function ($a, $b) {
            return str::compare(str::lower($a->name), str::lower($b->name));
        });
    }

    /**
     * @param $a
     * @param $b
     * @return Bundle[]
     */
    private function mergeList($a, $b): array
    {
        $tempList = $a;

        foreach ($tempList as $key => $item) {
            if ($item === false) {
                unset($tempList[$key]);
            }
        }

        sort($tempList);

        foreach ($b as $obj) {
            foreach ($tempList as $key => $_obj) {
                if ($obj->name == $_obj->name) {
                    $tempList[$key] = $obj;
                    continue 2;
                }
            }

            $tempList[] = $obj;
        }

        return Flow::of($tempList)->sort(function ($a, $b) {
            return str::compare(str::lower($a->name), str::lower($b->name));
        });
    }

    /**
     * @param $item
     * @return Bundle
     */
    public function sort($item): Bundle
    {
        return Bundle::of($item);
    }
}