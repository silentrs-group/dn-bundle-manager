<?php


namespace shop\internal\LoggerReporter\module;

use ide\Logger;
use php\lang\Thread;
use shop\internal\exception\RuntimeException;
use php\io\File;
use shop\internal\Http;

class MessageDiscord extends AbstractMessage implements IMessageDiscord
{
    /**
     * @var array
     */
    private $apiKeys = [];
    private $baseUrl = 'https://discord.com/api/webhooks/';


    public function __construct()
    {

    }

    public function updateApiKey($event, $key)
    {
        $this->apiKeys[$event] = $key;
    }

    /**
     * @throws RuntimeException
     */
    public function send()
    {
        if (!isset($this->apiKeys[$this->level])) {
            throw new RuntimeException("Please set discord api key for event: " . $this->getLevel());
        }

        if (empty($this->message)) return;

        $messageBody = sprintf("[%s] [%s] %s", $this->getLevel(), $this->getDate(), $this->message);

        $th = new Thread(function () use ($messageBody) {
            try {
                Http::post($this->baseUrl . $this->apiKeys[$this->level], [
                    "content" => $messageBody,
                    "username" => "App reporter Bundle Manager"
                ]);
            } catch (\Exception $ignore) {
                // Logger::error($ignore->getMessage());
            }
        });
        $th->setDaemon(true);
        $th->start();
    }

    /**
     * @param File $logFile
     */
    public function attachFile(File $logFile)
    {

    }
}