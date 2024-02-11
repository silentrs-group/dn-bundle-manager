<?php


namespace shop\internal\LoggerReporter\module;


use shop\internal\LoggerReporter\interfaces\IMessageObject;

interface IMessageConsole extends IMessageObject
{
    public function show();
}