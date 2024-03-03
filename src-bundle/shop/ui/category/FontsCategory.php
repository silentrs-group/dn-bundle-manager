<?php

namespace shop\ui\category;

use ide\Ide;
use php\io\FileStream;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;
use shop\internal\Http;
use shop\ui\UIBundleItem;
use shop\ui\UIShop;

class FontsCategory extends AbstractCategory
{
    protected $id = 'fonts';

    /**
     * @var Thread
     */
    private $thread;

    public function getName()
    {
        return "Шрифты";
    }

    public function install($url, UIBundleItem $node = null)
    {
        $node->showProgress();
        $this->thread = new Thread(function () use ($url, $node) {
            try {
                $response = Http::get($url);
                $filename = Ide::get()->createTempFile();
                $file = new FileStream($filename, "r+");
                $file->write($response);
                $file->close();

                $this->unpack($filename, fs::parent(Ide::project()->getMainProjectFile()) . '/src/.data/fonts/' . str::replace($node->getName(), ' ', '_'));
            } catch (\Exception $ignore) {
            }

            uiLater(function () use ($node) {
                $node->hideProgress();
            });
        });

        $this->thread->setDaemon(true);
        $this->thread->start();
    }


}