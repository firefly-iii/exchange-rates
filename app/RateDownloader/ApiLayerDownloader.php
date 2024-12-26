<?php
declare(strict_types=1);

namespace App\RateDownloader;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;

class ApiLayerDownloader implements DownloaderInterface
{
    private Logger $logger;
    private string $from;
    private array  $to;

    private string $token;
    private array  $result;

    public function __construct(Logger $logger)
    {
        $this->result = [];
        $this->logger = $logger;
        $this->logger->debug('ApiLayerDownloader instantiated');
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    public function setTo(array $to): void
    {
        $this->to = $to;
    }

    public function download(): void
    {
        $url     = sprintf('http://api.apilayer.com/exchangerates_data/latest?base=%s&symbols=%s', $this->from, join(',', $this->to));
        $success = false;
        $count   = 0;
        $json    = [];
        do {
            $count++;
            $this->logger->debug(sprintf('Attempt %d to download %s', $count, $url));
            $client = new Client();
            try {
                $opts = [
                    'headers' => [
                        'apikey' => $this->token,
                    ],
                ];
                $res  = $client->request('GET', $url, $opts);
            } catch (GuzzleException $e) {
                $this->logger->error(sprintf('Could not complete request: %s', $e->getMessage()));
                if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                    $this->logger->error((string) $e->getResponse()->getBody());
                }
                $success = false;
                continue;
            }
            // catch errors and issues.
            if (200 !== $res->getStatusCode()) {
                $this->logger->error(sprintf('Status code is %d', $res->getStatusCode()));
                $this->logger->error((string) $res->getBody());
                $success = false;
                continue;
            }
            if (200 === $res->getStatusCode()) {
                $body      = (string) $res->getBody();
                $json      = json_decode($body, true);
                $headers   = $res->getHeaders();
                $remaining = (int) ($headers['X-RateLimit-Remaining'][0] ?? 0);
                $this->logger->debug(sprintf('Requests left: %d of %d.', $remaining, $headers['X-RateLimit-Limit'][0] ?? 0));
                //if(0 === $remaining) {
                //    echo 'No API things remain!';
                //    exit(1);
                //}
                $success = true;
            }
        }
        while (false === $success && $count < 5);

        if (false === $success) {
            $this->logger->error('Could not download URL after five tries. Will exit now.');
            return;
        }
        // TODO parse me.
        $this->result = $json;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getResult(): array
    {
        return $this->result;
    }
}
