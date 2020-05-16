<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cache\MemCached;
use Kirby\Cms\Page;

class MemcachedPage extends Page
{
    private static $singleton;
    private static function getSingleton(): ?MemCached
    {
        if (class_exists('Memcached') === false) {
            if (\option('bnomei.page-memcached.enforce')) {
                throw new \Exception('Memcached Class is not available');
            }
            return null;
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

    public function isContentMemcached(string $languageCode = null): bool
    {
        return $this->readContentCache($languageCode) !== null;
    }

    public function memcachedKey(string $languageCode = null): string
    {
        $key = $this->cacheId('memcached');
        if (!$languageCode) {
            $languageCode = kirby()->languages()->count() ? kirby()->language()->code() : null;
            if ($languageCode) {
                $key = $languageCode . '.' . $key;
            }
        }

        return md5(kirby()->roots()->index() . $key);
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

    /**
     * @internal
     */
    public function readContentCache(string $languageCode = null): ?array
    {
        $cache = static::getSingleton();
        if (! $cache) {
            return null;
        }
        return $cache->get(
            $this->memcachedKey($languageCode),
            null
        );
    }

    public function writeContent(array $data, string $languageCode = null): bool
    {
        // write to file and memcached
        return parent::writeContent($data, $languageCode) &&
            $this->writeContentCache($data, $languageCode);
    }

    /**
     * @internal
     */
    public function writeContentCache(array $data, string $languageCode = null): bool
    {
        $cache = static::getSingleton();
        if (! $cache) {
            return true;
        }
        return $cache->set(
            $this->memcachedKey($languageCode),
            $data,
            \option('bnomei.page-memcached.expire', 0)
        );
    }

    public function delete(bool $force = false): bool
    {
        $cache = static::getSingleton();
        if ($cache) {
            foreach(kirby()->languages() as $language) {
                $cache->remove(
                    $this->memcachedKey($language->code())
                );
            }
            $cache->remove(
                $this->memcachedKey()
            );
        }

        return parent::delete($force);
    }
}
