<?php

namespace shop\ui\category;

use ide\Ide;
use ide\Logger;
use php\io\FileStream;
use php\lang\Thread;
use php\lib\fs;
use php\lib\str;
use shop\internal\Http;
use shop\ui\UIBundleItem;

class IconsCategory extends AbstractCategory
{
    protected $id = 'icons';

    public function getName()
    {
        return "Иконки";
    }

    public function install($url, UIBundleItem $node = null)
    {
        $node->showProgress();

        $this->thread = new Thread(function () use ($url, $node) {
            try {
                $response = Http::get($url);

                $to = fs::parent(Ide::project()->getMainProjectFile());
                $to = fs::normalize($to . '/src/.data/img/' . str::replace($node->getName(), ' ', '_') . '.' . fs::ext($url));
                Logger::error($to);

                $fs = new FileStream($to, 'w');
                $fs->write($response);
                $fs->close();
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