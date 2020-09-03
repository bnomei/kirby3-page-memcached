<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Bnomei\MemcachedPage;

final class MemcachedPageTest extends TestCase
{
    public function testConstructs()
    {
        $this->assertInstanceOf(MemcachedPage::class, page('home'));
    }

    public function testModified()
    {
        /** @var MemcachedPage $home */
        $home = page('home');
        $key = $home->memcachedKey();

        $before = \Bnomei\MemcachedPage::singleton()->retrieve($key);
        kirby()->impersonate('kirby');
        $home->update(['title' => 'Home ' . time()]);
        $after = \Bnomei\MemcachedPage::singleton()->retrieve($key);

        $this->assertNotEquals($before, $after);
    }

    public function testUnknown()
    {
        /** @var SQLitePage $home */
        $home = page('home');
        $key = $home->memcachedKey();

        \Bnomei\MemcachedPage::singleton()->remove($key);
        $obj = \Bnomei\MemcachedPage::singleton()->get($key);
        $this->assertNull($obj);
    }

    public function testReadContent()
    {
        /** @var SQLitePage $home */
        $home = page('home');
        $key = $home->memcachedKey();

        \Bnomei\MemcachedPage::singleton()->remove($key);
        $cache = $home->readContentCache();
        $this->assertNull($cache);

        \Bnomei\MemcachedPage::singleton()->remove($key);
        $data = $home->readContent();
        $this->assertNotNull($data);
        $cache = $home->readContentCache();
        $this->assertNotNull($cache);
    }
}
