<?php
declare(strict_types=1);

namespace App\RateDownloader;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;

class ExchangeRateDownloader implements DownloaderInterface
{
    private Logger $logger;
    private string $from;
    private array  $to;

    private array  $result;
    private string $token;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->logger->debug('ExchangeRateDownloader instantiated');
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
        $url     = sprintf('https://v6.exchangerate-api.com/v6/%s/latest/%s', $this->token, $this->from);
        $success = false;
        $count   = 0;
        $json    = [];
        do {
            $count++;
            $this->logger->debug(sprintf('Attempt %d to download %s', $count, $url));
            $client = new Client();
            try {
                $opts = [];
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
                $body    = (string) $res->getBody();
                $json    = json_decode($body, true);
                $success = true;
            }
        }
        while (false === $success && $count < 5);

        if (!array_key_exists('conversion_rates', $json)) {
            $this->logger->error('No conversion rates found in response');
            $this->logger->error(var_export($json, true));
            $this->result = [];
            return;
        }
        $date = new Carbon($json['time_last_update_utc']);
        foreach ($this->to as $key) {
            if($key === $this->from) {
                continue;
            }
            if (array_key_exists($key, $json['conversion_rates'])) {
                $this->logger->debug(sprintf('Found rate for %s (%s)', $key, $json['conversion_rates'][$key]));
                $this->result[$date->format('Y-m-d')][$key] = $json['conversion_rates'][$key];
            }
        }
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }
}
