<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cache\MemCached;
use Kirby\Cms\Page;

class MemcachedPage extends Page
{
    private static $singleton;
    private static function getSingleton(): MemCached
    {
        if (class_exists('Memcached') === false) {
            throw new \Exception('The Memcached extension is not available.');
        }
        if (! self::$singleton) {
            $config = [
                'host' => \option('bnomei.page-memcached.host'),
                'port' => \option('bnomei.page-memcached.port'),
                'prefix' => \option('bnomei.page-memcached.prefix'),
            ];
            foreach (array_keys($config) as $key) {
                if (!is_string($config[$key]) && is_callable($config[$key])) {
                    $config[$key] = $config[$key]();
                }
            }
            self::$singleton = new MemCached($config);
        }
        if (\option('debug')) {
            self::$singleton->flush();
        }

        return self::$singleton;
    }

    private function memcachedKey(): string
    {
        return $this->cacheId('memcached');
    }

    public function readContent(string $languageCode = null): array
    {
        // read from memcached if exists
        $data = \option('debug') ? null : $this->readContentCache($languageCode);

        // read from file and update memcached
        if (! $data) {
            $data = parent::readContent($languageCode);
            $this->writeContentCache($data, $languageCode);
        }

        return $data;
    }

    public function readContentCache(string $languageCode = null): ?array
    {
        return static::getSingleton()->get(
            $this->memcachedKey(),
            null
        );
    }

    public function writeContent(array $data, string $languageCode = null): bool
    {
        // write to file and memcached
        return parent::writeContent($data, $languageCode) &&
            $this->writeContentCache($data, $languageCode);
    }

    public function writeContentCache(array $data, string $languageCode = null): bool
    {
        return static::getSingleton()->set(
            $this->memcachedKey(),
            $data,
            \option('bnomei.page-memcached.expire', 0)
        );
    }

    public function delete(bool $force = false): bool
    {
        static::getSingleton()->remove(
            $this->memcachedKey()
        );

        return parent::delete($force);
    }
}
