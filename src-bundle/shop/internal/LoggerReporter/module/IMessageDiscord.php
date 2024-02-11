<?php


namespace shop\internal\LoggerReporter\module;


use shop\internal\LoggerReporter\interfaces\IMessageObject;

interface IMessageDiscord extends IMessageObject
{
    public function send();
}