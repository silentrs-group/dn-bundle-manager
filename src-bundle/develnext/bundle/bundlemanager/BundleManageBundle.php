<?php

namespace develnext\bundle\bundlemanager;

use ide\account\api\AccountService;
use ide\bundle\AbstractJarBundle;
use ide\commands\MyAccountCommand;
use ide\commands\BundleManagerCommand;
use ide\library\IdeLibraryBundleResource;
use ide\Ide;
use php\gui\UXButton;
use php\lib\fs;
use php\time\Timer;
use gui;
use shop\internal\LoggerReporter\LoggerReporter;

class BundleManageBundle extends AbstractJarBundle
{
    private static $command = null;

    /**
     * @var LoggerReporter
     */
    public static $loggerReporter;

    public function onRegister(IdeLibraryBundleResource $resource)
    {
        if (self::$command != null) {
            return;
        }

        fs::makeDir(Ide::get()->getUserHome() . BundleManagerCommand::CACHE_DIR);

        self::$command = $test = new BundleManagerCommand();
        self::$loggerReporter = new LoggerReporter();

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
}