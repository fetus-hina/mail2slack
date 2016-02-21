<?php
namespace jp3cki\mail2slack;

use Frlnc\Slack\Core\Commander as SlackCommander;
use Frlnc\Slack\Http\CurlInteractor as SlackCurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;
use PhpMimeMailParser\Parser as MailParser;

class Application
{
    public $config;

    public function __construct()
    {
        $this->config = require(__DIR__ . '/../config/slack.php');
    }

    public function run(): int
    {
        $parser = $this->loadMail('php://stdin');
        $filter = $this->config['filter'] ?? null;
        if (is_callable($filter)) {
            if (!$filter($parser)) {
                return 1;
            }
        }
        $text = $this->format($parser);
        if ($text === '') {
            return 1;
        }
        return $this->postSlack($text) ? 0 : 1;
    }

    private function loadMail(string $filename): MailParser
    {
        $parser = new MailParser();
        $parser->setText(\file_get_contents($filename, false, null));
        return $parser;
    }

    private function format(MailParser $parser): string
    {
        $from = \trim($parser->getHeader('from'));
        $subject = \trim($parser->getHeader('subject'));
        $attachments = $parser->getAttachments();
        $text = $this->processText((string)$parser->getMessageBody('text'));
        if ($from === '' || $text === '') {
            return '';
        }
        return sprintf(
            "From: %s\nSubject: %s\n%s",
            $from,
            $subject,
            $text
        );
    }

    private function processText(string $text, int $limit = 5): string
    {
        $lines = array_map(
            function (string $line): string {
                return preg_replace('/(\s|　)+/u', ' ', trim($line));
            },
            preg_split('/\x0d\x0a|\x0d|\x0a/', $text)
        );
        $lines = array_filter($lines, function (string $line): bool { return $line !== ''; });
        return '> ' . implode("\n> ", array_slice($lines, 0, $limit));
    }

    private function postSlack(string $text): bool
    {
        $interactor = new SlackCurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        $commander = new SlackCommander($this->config['token'], $interactor);
        $response = $commander->execute(
            'chat.postMessage',
            array_merge(
                $this->config['postMessage'],
                [
                    'text' => $text,
                ]
            )
        );
        return $response->getStatusCode() == 200 &&
            $response->getBody()['ok'] ?? false;
    }
}
