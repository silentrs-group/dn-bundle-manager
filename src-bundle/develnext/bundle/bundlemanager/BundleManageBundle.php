<?php

namespace develnext\bundle\bundlemanager;

use develnext\bundle\httpclient\HttpClientBundle;
use ide\account\api\AccountService;
use ide\bundle\AbstractBundle;
use ide\bundle\AbstractJarBundle;
use ide\commands\MyAccountCommand;
use ide\commands\BundleManagerCommand;
use ide\library\IdeLibraryBundleResource;
use ide\Ide;
use ide\Logger;
use php\gui\UXButton;
use php\lib\fs;
use php\time\Timer;
use gui;

class BundleManageBundle extends AbstractJarBundle
{
    private static $command = null;

    public function onRegister(IdeLibraryBundleResource $resource)
    {
        if (self::$command != null) {
            return;
        }

        fs::makeDir(Ide::get()->getUserHome() . BundleManagerCommand::CACHE_DIR);

        self::$command = $test = new BundleManagerCommand();

        if (Ide::get()->getMainForm()->getHeadPane()->children->offsetGet(0) instanceof UXButton::class) {
            if (Ide::get()->getMainForm()->getHeadPane()->children->offsetGet(0)->text == $test->makeUiForHead()->text) return;
        }

        if (class_exists(AccountService::class) && class_exists(MyAccountCommand::class)) {
            Ide::get()->unregisterCommand(AccountService::class);
            Ide::get()->unregisterCommand(MyAccountCommand::class);
            Timer::after(10000, function () {
                Ide::service()->shutdown();
            });
        } else {
            return;
        }

        Ide::get()->getMainForm()->getHeadPane()->children->insert(0, $test->makeUiForHead());
    }

    public function getDependencies()
    {
        return [
            HttpClientBundle::class
        ];
    }
}