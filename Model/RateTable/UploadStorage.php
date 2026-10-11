<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\RateTable;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Keeps an uploaded spreadsheet between its preview and the confirmation, under var/.
 *
 * Files are named by a random token; abandoned previews are removed after a day.
 */
class UploadStorage
{
    private const DIRECTORY = 'easyshippingrules/rate_table_import';
    private const MAX_AGE = 86400;

    /**
     * Initialize dependencies.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * Store the contents; returns the token that reads them back.
     *
     * @param string $contents
     * @return string
     */
    public function save(string $contents): string
    {
        $this->purge();
        $token = bin2hex(random_bytes(16));
        $this->getDirectory()->writeFile($this->path($token), $contents);

        return $token;
    }

    /**
     * Stored contents, or null when the token is unknown or malformed.
     *
     * @param string $token
     * @return string|null
     */
    public function load(string $token): ?string
    {
        if (!$this->isValid($token) || !$this->getDirectory()->isFile($this->path($token))) {
            return null;
        }

        return $this->getDirectory()->readFile($this->path($token));
    }

    /**
     * Remove the stored contents.
     *
     * @param string $token
     * @return void
     */
    public function delete(string $token): void
    {
        if ($this->isValid($token) && $this->getDirectory()->isFile($this->path($token))) {
            $this->getDirectory()->delete($this->path($token));
        }
    }

    /**
     * Remove previews older than a day.
     *
     * @return void
     */
    private function purge(): void
    {
        $directory = $this->getDirectory();
        if (!$directory->isDirectory(self::DIRECTORY)) {
            return;
        }

        foreach ($directory->read(self::DIRECTORY) as $path) {
            $mtime = (int)($directory->stat($path)['mtime'] ?? 0);
            if ($mtime && $mtime < time() - self::MAX_AGE) {
                $directory->delete($path);
            }
        }
    }

    /**
     * Tokens are 32 hex characters, so a token never escapes the directory.
     *
     * @param string $token
     * @return bool
     */
    private function isValid(string $token): bool
    {
        return (bool)preg_match('/^[a-f0-9]{32}$/', $token);
    }

    /**
     * Path of a token, relative to var/.
     *
     * @param string $token
     * @return string
     */
    private function path(string $token): string
    {
        return self::DIRECTORY . '/' . $token . '.csv';
    }

    /**
     * The var/ directory.
     *
     * @return WriteInterface
     */
    private function getDirectory(): WriteInterface
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }
}
