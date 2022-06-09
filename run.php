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
$currencies = ['EUR', 'HUF', 'GBP', 'UAH', 'PLN', 'TRY', 'DKK', 'USD', 'BRL', 'CAD', 'MXN', 'IDR', 'AUD', 'NZD', 'EGP', 'MAD', 'ZAR', 'JPY', 'CNY', 'RUB', 'INR', 'ILS', 'CHF', 'HRK'];
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
        $url    = sprintf('https://api.exchangerate.host/latest?base=%s&symbols=%s', $from, join(',', $currencies));
        $client = new Client();
        $opts   = [];
        try {
            $res = $client->request('GET', $url, $opts);
        } catch (GuzzleException $e) {
            $log->error(sprintf('Could not complete request: %s', $e->getMessage()));
            exit(1);
        }

        // catch errors and issues.
        if (200 !== $res->getStatusCode()) {
            $log->error(sprintf('Status code is %d', $res->getStatusCode()));
            $log->error((string) $res->getBody());
            exit(1);
        }

        $body = (string) $res->getBody();
        $json = json_decode($body, true);

        foreach ($json['rates'] as $to => $rate) {
            if ($from !== $to) {
                $log->debug(sprintf('Found a rate for %s to %s: %f', $from, $to, $rate));
                $final[$date][$from][$to] = $rate;
            }
        }
        $headers = $res->getHeaders();
        $log->debug(sprintf('Requests left: %d of %d.', $headers['X-RateLimit-Remaining'][0] ?? 0, $headers['X-RateLimit-Limit'][0] ?? 0));
        sleep(1);
    }
    // save result:
    file_put_contents($destination, json_encode($final, JSON_PRETTY_PRINT));
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
        $log->debug(sprintf('Stored file "%s" with %d rates', $current, count($content)));
        file_put_contents($current, json_encode($content, JSON_PRETTY_PRINT));
    }
}
