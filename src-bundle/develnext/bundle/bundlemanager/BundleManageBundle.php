<?php

namespace develnext\bundle\bundlemanager;

use ide\account\api\AccountService;
use ide\account\IdeService;
use ide\bundle\AbstractBundle;
use ide\commands\IdeLogShowCommand;
use ide\commands\MyAccountCommand;
use ide\commands\TestCommand;
use ide\commands\theme\IDETheme;
use ide\library\IdeLibraryBundleResource;
use ide\Ide;
use ide\Logger;
use ide\project\Project;
use ide\settings\ide\IDESettingsGroup;
use ide\systems\Cache;
use ide\systems\IdeSystem;
use php\gui\UXButton;
use php\lib\fs;
use php\time\Timer;
use gui;

class BundleManageBundle extends AbstractBundle
{
    private static $command = null;

    public function onRegister(IdeLibraryBundleResource $resource)
    {
        if (self::$command != null) {
            return;
        }

        fs::makeDir(Ide::get()->getUserHome() . '\bundleManager');

        self::$command = $test = new TestCommand();


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