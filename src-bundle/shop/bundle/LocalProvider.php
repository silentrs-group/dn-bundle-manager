<?php

namespace shop\bundle;

use ide\Ide;
use ide\library\IdeLibraryBundleResource;
use ide\Logger;
use php\compress\ZipFile;
use php\io\MemoryStream;
use php\io\MiscStream;
use php\lib\fs;
use php\util\Configuration;
use php\util\Flow;
use shop\dto\Bundle;

class LocalProvider extends BaseBundleProvider
{

    public function init()
    {
        $this->update();
    }

    public function update()
    {
        $this->list = Flow::of($this->bundleOperation->getAll())->map(function (Configuration $item) {
            if ($item->get("name") == null) return false;
            $stream = new MemoryStream();

            fs::scan($this->bundleOperation->bundlePath . '/' . $item->get("name") . '~' . $item->get("version"), ["namePattern" => "^dn-.*?\\.jar$", "callback" => function ($file) use ($item, &$stream) {
                $zip = new ZipFile($file);

                if ($zip->has('.data/img/' . $item->get("icon"))) {
                    $zip->read('.data/img/' . $item->get("icon"), function ($stat, MiscStream $_stream) use (&$stream, $item) {
                        fs::copy($_stream, $stream);
                    });
                }


            }]);

            if ($stream->length() == 0) {
                $stream = "res://.data/img/default.png";
            }

            return Bundle::of([
                "name" => $item->get("name"),
                "version" => $item->get("version"),
                "author" => $item->get("author"),
                "image" => $stream,
                "description" => $item->get("description"),
                "url" => null
            ]);
        })->toArray();

        /* $this->list = Flow::of(Ide::get()->getLibrary()->getResources("bundles"))->map(function (IdeLibraryBundleResource $item) {
            if ($item->isEmbedded()) return false;
            $stream = new MemoryStream();

            fs::scan($item->getBundle()->getBundleDirectory(), ["namePattern" => "^dn-.*?\\.jar$", "callback" => function ($file) use ($item, &$stream) {
                $zip = new ZipFile($file);

                if ($zip->has('.data/img/' . $item->getIcon())) {
                    $zip->read('.data/img/' . $item->getIcon(), function ($stat, MiscStream $_stream) use (&$stream, $item) {
                        fs::copy($_stream, $stream);
                    });
                }
            }]);

            if ($stream->length() == 0) {
                $stream = "res://.data/img/default.png";
            }

            return Bundle::of([
                "name" => $item->getName(),
                "version" => $item->getVersion(),
                "author" => $item->getAuthor(),
                "image" => $stream,
                "url" => null
            ]);
        })->toArray(); */
    }
}