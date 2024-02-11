<?php


namespace shop\internal\LoggerReporter\interfaces;


interface IMessageObject
{
    public function setMessage($message);
    public function setLevel($level);

    public function getDate(): string;
}