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

use App\Exception\DownloadException;
use App\RateDownloader\ApiLayerDownloader;
use App\RateDownloader\DownloaderInterface;
use App\RateDownloader\ExchangeRateDownloader;
use Carbon\Carbon;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

include 'vendor/autoload.php';

$timezone = 'Europe/Amsterdam';
$logLevel = Level::Debug;

// currency list is a secret, because people keep injecting their own currencies as PR.

$currencies = explode(',', $argv[1]);
$handler = new StreamHandler('php://stdout', $logLevel);
$formatter = new LineFormatter(null, null, false, true);
$log = new Logger('exchange-rates');
$formatter->setDateFormat('Y-m-d H:i:s');
$handler->setFormatter($formatter);
$log->pushHandler($handler);

/**
 * Both RMB and CNY are present.
 *
 * EUR,HUF,GBP,UAH,PLN,TRY,DKK,ISK,NOK,SEK,RON,USD,BRL,CAD,MXN,IDR,AUD,NZD,EGP,MAD,ZAR,JPY,CNY,RMB,RUB,INR,ILS,CHF,HRK,HKD,CHF,NOK,CZK
 */

/*
 * Here we go:
 */
$log->debug('Start of Exchange Rates 1.0');

/*
 * Variables for the run:
 */
$date = date('Y-m-d');
$final = [];
$destination = sprintf('result/%s.json', $date);
$downloaders = [ExchangeRateDownloader::class, ApiLayerDownloader::class];
$tokens = [getenv('EXCHANGE_RATE_KEY'), getenv('API_LAYER_KEY')];
$result = [];

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

        // at this point several services exist that we can poll for data, and we support many of them (or at least two).
        foreach ($downloaders as $index => $downloader) {
            /** @var DownloaderInterface $object */
            $object = new $downloader($log);
            $object->setFrom($from);
            $object->setTo($currencies);
            $object->setToken($tokens[$index]);

            try {
                $object->download();
            } catch (DownloadException $e) {
                $log->error(sprintf('Could not download rates for %s: %s', $from, $e->getMessage()));
                continue;
            }
            /**
             * Expects the following format:
             * YYYY-MM-DD => [
             *   ABC => rate
             *   DEF => rate
             *
             * etc.
             */

            $result[$from] = $object->getResult();
            if (count($result) === 0) {
                $log->error(sprintf('No rates found for %s', $from));
                continue;
            }
            break;
        }
        sleep(4);
    }
}

if (0 === count($result)) {
    $log->error('No rates found at all. Will exit now.');
    exit(1);
}

/*
 * Parse results into JSON file for weekly consumption by clients
 * This is a separate step so the download can be skipped if necessary.
 */
$path = realpath('rates');

$log->debug(sprintf('Will store rates in %s', $path));

foreach(array_keys($result) as $from) {
    if(0 === count($result[$from])) {
        $log->error(sprintf('No rates found for %s, remove entry.', $from));
        unset($result[$from]);
    }
}


/*
 * Duplicate CNY into RMB because it is not downloaded.
 */
if (array_key_exists('CNY', $result) && !array_key_exists('RMB', $result)) {
    $log->debug('Copy RMB rates from CNY.');
    $result['RMB'] = $result['CNY'];
}
if (!array_key_exists('CNY', $result) && array_key_exists('RMB', $result)) {
    $log->debug('Copy CNY rates from RMB.');
    $result['CNY'] = $result['RMB'];
}

foreach ($result as $from => $set) {
    foreach ($set as $date => $rates) {
        $carbon = Carbon::createFromFormat('Y-m-d', $date, $timezone);
        $log->debug(sprintf('[%s] Running for week %d, %d', $from, $carbon->isoWeek, $carbon->year));
        $current = sprintf('%s/%d/%d/%s.json', $path, $carbon->year, $carbon->isoWeek, $from);
        if (!file_exists(dirname($current))) {
            mkdir(dirname($current), 0777, true);
            $log->debug(sprintf('Created directory "%s"', dirname($current)));
        }
        $content = [
            'date' => $date,
            'rates' => [],
        ];
        foreach ($rates as $to => $rate) {
            $content['rates'][$to] = $rate;
        }
        $log->debug(sprintf('Stored file "%s" with %d rates', $current, count($content['rates'])));
        file_put_contents($current, json_encode($content, JSON_PRETTY_PRINT));
    }
}


$log->debug('Done!');