<?php
declare(strict_types=1);
namespace App\RateDownloader;
use App\Exception\DownloadException;

interface DownloaderInterface
{
    public function setFrom(string $from): void;

    public function setTo(array $to): void;

    /**
     * @return array
     * @throws DownloadException
     */
    public function download(): void;

    public function setToken(string $token): void;

    public function getResult(): array;
}
