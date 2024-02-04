<?php

namespace shop\bundle;

use bundle\http\HttpClient;
use ide\formats\ProjectFormat;
use ide\Ide;
use ide\project\behaviours\bundle\BundlesProjectControlPane;
use ide\utils\FileUtils;
use php\compress\ZipFile;
use php\io\File;
use php\io\FileStream;
use php\io\MemoryStream;
use php\io\Stream;
use php\lang\System;
use php\lib\fs;
use php\lib\str;
use php\util\Configuration;
use php\util\Regex;
use shop\ui\UIActionButton;
use gui;
use shop\ui\UIBundleItem;
use shop\ui\UIShop;

class BundleOperation
{
    /**
     * @var string
     */
    public $bundlePath;

    public function __construct()
    {
        $this->bundlePath = fs::normalize(System::getProperty("user.home") . '\DevelNextLibrary\bundles');
    }

    public function addBundleFromUrl($url, UIBundleItem $node = null)
    {
        $file = Ide::get()->createTempFile('.dnbundle');

        $node->showProgress();
        //Ide::get()->getMainForm()->showPreloader('Подождите, загрузка пакета ...');

        Ide::async(function () use ($url, $file, $node) {
            if (!$this->download($url, $file)) return;

            $this->addBundle($file, function () use ($file, $node) {
                $this->successBundleInstall($file, $node);
            });

            uiLater(function () use ($node) {
                Ide::get()->getMainForm()->hidePreloader();
                $node->hideProgress();
                Ide::get()->getLibrary()->updateCategory('bundles');
            });
        });
    }

    /**
     * @param $path
     * @param $callback
     * @return bool
     */
    public function addBundle($path, $callback)
    {
        $file = File::of($path);
        if (!$file->exists()) return false;

        $zip = new ZipFile($file);

        if (!$zip->has('.resource')) return false;
        $config = new Configuration();

        $zip->read('.resource', function ($stat, Stream $stream) use ($config) {
            $config->load($stream);
        });

        $name = $config->get("name") . '~' . $config->get("version");

        if ($name == '~') return false;

        fs::makeDir($this->bundlePath . '\\' . $name);

        $zip->readAll(function ($stat, Stream $stream) use ($name) {
            if (str::startsWith($stat["name"], 'bundle/') && !$stat["directory"]) {
                fs::copy($stream, fs::normalize($this->bundlePath . '\\' . $name . '\\' . File::of($stat["name"])->getName()));
            }
        });

        $config->save($this->bundlePath . '\\' . $name . '.resource');
        $callback();
        return true;
    }

    public function update($url, $bundle, UIBundleItem $node)
    {
        $file = Ide::get()->createTempFile('.dnbundle');

        $node->showProgress();
        // Ide::get()->getMainForm()->showPreloader('Подождите, загрузка пакета ...');

        Ide::async(function () use ($url, $file, $node, $bundle) {
            if (!$this->download($url, $file)) return;

            $this->remove($bundle);

            $this->addBundle($file, function () use ($file, $node) {
                $this->successBundleInstall($file, $node);
            });

            uiLater(function () use ($node) {
                Ide::get()->getLibrary()->updateCategory('bundles');
                $node->hideProgress();
            });
        });
    }

    /**
     * @param Configuration $bundle
     * @return bool
     */
    public function remove(Configuration $bundle)
    {
        if ($bundle->get("name") == null) return false;

        $path = fs::normalize($this->bundlePath . '\\' . $bundle->get("name") . '~' . $bundle->get("version"));
        fs::delete($path);

        $state = fs::delete($path . '.resource');

        uiLater(function () {
            Ide::get()->getLibrary()->updateCategory('bundles');
        });

        return $state;
    }

    /**
     * @param $name
     * @return Configuration
     */
    public function get($name)
    {
        $result = fs::scan($this->bundlePath, [
            "namePattern" => Regex::of($name . '~.*\.resource', Regex::CASE_INSENSITIVE),
            "extensions" => ["resource"],
            "callback" => function (File $file) {
                return $this->readConfig($file);
            }
        ], 1);

        if (count($result) == 0) {
            $result[] = new Configuration();
        }

        return $result[0];
    }

    public function refresh()
    {
        try {

            if (!method_exists(Ide::project(), 'getRegisteredFormat')) return;

            $var = Ide::project()->getRegisteredFormat(ProjectFormat::class);
            /** @var $var ProjectFormat */

            foreach ($var->getControlPanes() as $pane) {
                /** @var $pane BundlesProjectControlPane */
                if ($pane instanceof BundlesProjectControlPane) {
                    $pane->getUi();
                    $pane->refresh();
                }
            }
        } catch (\Exception $ignore) {
        }
    }

    /**
     * @return Configuration[]
     */
    public function getAll(): array
    {
        return fs::scan($this->bundlePath, [
            "extensions" => ["resource"],
            "callback" => function (File $file) {
                return $this->readConfig($file);
            }
        ], 1);
    }

    /**
     * @param File $file
     * @return Configuration
     * @throws \php\io\IOException
     */
    private function readConfig(File $file): Configuration
    {
        $configuration = new Configuration();
        $fileStream = new FileStream($file);
        $memory = new MemoryStream();

        $memory->write($fileStream->readFully());
        $fileStream->close();

        $memory->seek(0);

        $configuration->load($memory);

        return $configuration;
    }

    /**
     * @param $url
     * @param File $file
     * @return bool
     */
    private function download($url, File $file, UIBundleItem $node = null): bool
    {
        $memory = new MemoryStream();
        $memory->write(file_get_contents($url));

        if ($memory->length() < 1) {
            uiLater(function () use ($node) {
                UXDialog::show('Ошибка загрузки пакета');
                $node->hideProgress();
            });
            return false;
        }

        $memory->seek(0);

        FileUtils::copyFile($memory, $file);

        return true;
    }

    /**
     * @param File $file
     * @param $node
     * @return void
     */
    function successBundleInstall(File $file, UIBundleItem $node): void
    {
        if (!$file->delete()) {
            $file->deleteOnExit();
        }

        uiLater(function () use ($node) {
            Ide::get()->getLibrary()->updateCategory('bundles');
            $node->hideProgress();

            if ($node == null) return;
            $node->setState(UIActionButton::STATE_UNINSTALL);
        });
    }
}