<?php
declare(strict_types=1);

/**
 * run.php
 * Copyright (c) 2022 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

include 'vendor/autoload.php';

$timezone   = 'Europe/Amsterdam';
$logLevel   = Level::Debug;
$currencies = explode(',', $argv[1]);
$accessKey  = $argv[2];
$handler    = new StreamHandler('php://stdout', $logLevel);
$formatter  = new LineFormatter(null, null, false, true);
$log        = new Logger('exchange-rates');
$formatter->setDateFormat('Y-m-d H:i:s');
$handler->setFormatter($formatter);
$log->pushHandler($handler);

/*
 * Here we go:
 */
$log->debug('Start of Exchange Rates 1.0');

/*
 * Variables for the run:
 */
$date        = date('Y-m-d');
$final       = [];
$destination = sprintf('result/%s.json', $date);

if (file_exists($destination)) {
    $log->error(sprintf('Destination "%s" already exists, will not run again.', $destination));
}

/*
 * Here we go for real
 */
if (!file_exists($destination)) {
    $log->debug(sprintf('Will search for these currencies: %s', join(', ', $currencies)));

    foreach ($currencies as $from) {
        $log->debug(sprintf('Will now query rates of currency "%s"', $from));
        $url  = sprintf('http://api.apilayer.com/exchangerates_data/latest?base=%s&symbols=%s', $from, join(',',$currencies));
        $json = download($log, $url, $accessKey);

        if(!array_key_exists('quotes', $json)) {
            $log->error('No quotes found in JSON response.');
            $log->error(json_encode($json, JSON_PRETTY_PRINT));
            exit(1);
        }

        foreach ($json['quotes'] as $to => $rate) {
            $to = substr($to,3);
            if ($from !== $to) {
                $log->debug(sprintf('Found a rate for %s to %s: %f', $from, $to, $rate));
                $final[$date][$from][$to] = $rate;
            }
        }
        sleep(1);
    }
    // save result:
    $json = json_encode($final, JSON_PRETTY_PRINT);
    file_put_contents($destination, $json);
    echo sprintf('Store in %s:', $destination).PHP_EOL;
    echo $json.PHP_EOL;
    echo PHP_EOL;
}
/*
 * Parse results into JSON file for weekly consumption by clients
 * This is a separate step so the download can be skipped if necessary.
 */
$array = json_decode(file_get_contents($destination), true);
$path  = realpath('rates');

$log->debug(sprintf('Will store rates in %s', $path));

foreach ($array as $date => $set) {
    $carbon = Carbon::createFromFormat('Y-m-d', $date, $timezone);
    $log->debug(sprintf('Running for week %d, %d', $carbon->isoWeek, $carbon->year));
    foreach ($set as $from => $rates) {
        $current = sprintf('%s/%d/%d/%s.json', $path, $carbon->year, $carbon->isoWeek, $from);
        if (!file_exists(dirname($current))) {
            mkdir(dirname($current), 0777, true);
            $log->debug(sprintf('Created directory "%s"', dirname($current)));
        }
        $content = [
            'date'  => $date,
            'rates' => [],
        ];
        foreach ($rates as $to => $rate) {
            $content['rates'][$to] = $rate;
        }
        $log->debug(sprintf('Stored file "%s" with %d rates', $current, count($content['rates'])));
        file_put_contents($current, json_encode($content, JSON_PRETTY_PRINT));
    }
}

function download(Logger $log, string $url, string $accessKey): array
{
    $success = false;
    $count   = 0;
    $json    = [];
    do {
        $count++;
        $log->debug(sprintf('Attempt %d to download %s', $count, $url));
        $client = new Client();
        try {
            $opts = [
                'headers' => [
                    'apikey' => $accessKey,
                ],
            ];
            $res = $client->request('GET', $url, $opts);
        } catch (GuzzleException $e) {
            $log->error(sprintf('Could not complete request: %s', $e->getMessage()));
            $success = false;
            continue;
        }
        // catch errors and issues.
        if (200 !== $res->getStatusCode()) {
            $log->error(sprintf('Status code is %d', $res->getStatusCode()));
            $log->error((string)$res->getBody());
            $success = false;
            continue;
        }
        if (200 === $res->getStatusCode()) {
            $body    = (string)$res->getBody();
            $json    = json_decode($body, true);
            $headers = $res->getHeaders();
            $remaining = (int) ($headers['X-RateLimit-Remaining'][0] ?? 0);
            $log->debug(sprintf('Requests left: %d of %d.', $remaining, $headers['X-RateLimit-Limit'][0] ?? 0));
            //if(0 === $remaining) {
            //    echo 'No API things remain!';
            //    exit(1);
             //}
            $success = true;
        }
    }
    while (false === $success && $count < 5);

    if (false === $success) {
        $log->error('Could not download URL after five tries. Will exit now.');
        exit;
    }

    return $json;

}
