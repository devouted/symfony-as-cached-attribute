<?php

namespace Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Contracts\Cache\CacheInterface;
use Tests\Integration\TestKernel;

class IntegrationTest extends WebTestCase
{

    private function createLocalClient(): KernelBrowser
    {
        $client = static::createClient();
        $cache = self::getContainer()->get(CacheInterface::class);
        $cache->clear();
        return $client;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = new TestKernel('dev', false);
        $kernel->boot();
        return $kernel;
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    #[Test]
    public function testCachedEndpointMissAndHit(): void
    {
        $client = $this->createLocalClient();
        // Miss
        $client->request('GET', '/cached-endpoint');
        $this->assertResponseIsSuccessful();


        $this->assertSame('expected cached response', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));

        // Hit
        $client->request('GET', '/cached-endpoint');
        $this->assertResponseIsSuccessful();
        $this->assertSame('expected cached response', $client->getResponse()->getContent());
        $this->assertSame('Hit', $client->getResponse()->headers->get('X-Cache'));
    }

    public function testCachedWithDtoMissAndHit(): void
    {
        $client = $this->createLocalClient();

        $client->request('GET', '/cached-combined?id=1');
        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=1, filter=', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-combined?id=1');
        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=1, filter=', $client->getResponse()->getContent());
        $this->assertSame('Hit', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-combined?id=1&filter=recent');
        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=1, filter=recent', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-combined?id=2&filter=recent');
        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=2, filter=recent', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-combined?id=1&filter=recent');
        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=1, filter=recent', $client->getResponse()->getContent());
        $this->assertSame('Hit', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-combined?id=2&filter=recent');
        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=2, filter=recent', $client->getResponse()->getContent());
        $this->assertSame('Hit', $client->getResponse()->headers->get('X-Cache'));
    }


    #[Test]
    public function testCachedWithQueryParam(): void
    {
        $client = $this->createLocalClient();

        $client->request('GET', '/cached-with-query', ['q' => 'hello']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with query q=hello', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-with-query', ['q' => 'hello']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with query q=hello', $client->getResponse()->getContent());
        $this->assertSame('Hit', $client->getResponse()->headers->get('X-Cache'));

        $client->request('GET', '/cached-with-query', ['q' => 'world']);
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));

    }

    #[Test]
    public function testCachedWithParam(): void
    {
        $client = $this->createLocalClient();
        $client->request('GET', '/cached-with-param/42');

        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with param id=42', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));
    }

    #[Test]
    public function testCachedWithMultipleParams(): void
    {
        $client = $this->createLocalClient();
        $client->request('GET', '/cached-multi-param/99/test-slug');

        $this->assertResponseIsSuccessful();
        $this->assertSame('cached with id=99, slug=test-slug', $client->getResponse()->getContent());
        $this->assertSame('Miss', $client->getResponse()->headers->get('X-Cache'));
    }
}
