<?php

uses(\Symfony\Bundle\FrameworkBundle\Test\WebTestCase::class);
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Contracts\Cache\CacheInterface;
use Tests\Integration\TestKernel;


function createLocalClient(): KernelBrowser
{
    /** @var \Symfony\Bundle\FrameworkBundle\Test\WebTestCase $test */
    $test = test();
    $client = $test->createClient();
    $cache = $test->getContainer()->get(CacheInterface::class);
    $cache->clear();
    return $client;
}

function createKernel(array $options = []): KernelInterface
{
    $kernel = new TestKernel('dev', false);
    $kernel->boot();
    return $kernel;
}

afterEach(function () {
    restore_exception_handler();
});

describe('controller action', function () {
    test('cached endpoint miss and hit', function () {
        $client = createLocalClient();

        $client->request('GET', '/cached-endpoint');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('expected cached response');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-endpoint');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('expected cached response');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');
    });

    test('cached with path param', function () {
        $client = createLocalClient();

        $client->request('GET', '/cached-with-param/42');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('cached with param id=42');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-with-param/42');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/cached-with-param/99');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');
    });

    test('cached with multiple path params', function () {
        $client = createLocalClient();

        $client->request('GET', '/cached-multi-param/99/test-slug');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('cached with id=99, slug=test-slug');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-multi-param/99/test-slug');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/cached-multi-param/99/other-slug');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');
    });

    test('cached with query param', function () {
        $client = createLocalClient();

        $client->request('GET', '/cached-with-query', ['q' => 'hello']);
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('cached with query q=hello');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-with-query', ['q' => 'hello']);
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/cached-with-query', ['q' => 'world']);
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');
    });

    test('cached with dto', function () {
        $client = createLocalClient();

        $client->request('GET', '/cached-combined?id=1');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('cached with id=1, filter=');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-combined?id=1');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/cached-combined?id=1&filter=recent');
        expect($client->getResponse()->getContent())->toBe('cached with id=1, filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-combined?id=2&filter=recent');
        expect($client->getResponse()->getContent())->toBe('cached with id=2, filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/cached-combined?id=1&filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/cached-combined?id=2&filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');
    });
});

describe('invokable controller', function () {
    test('cached endpoint miss and hit', function () {
        $client = createLocalClient();

        $client->request('GET', '/inv-endpoint');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('invokable cached response');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-endpoint');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('invokable cached response');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');
    });

    test('cached with path param', function () {
        $client = createLocalClient();

        $client->request('GET', '/inv-with-param/42');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('invokable param id=42');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-with-param/42');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/inv-with-param/99');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');
    });

    test('cached with multiple path params', function () {
        $client = createLocalClient();

        $client->request('GET', '/inv-multi-param/99/test-slug');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('invokable id=99, slug=test-slug');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-multi-param/99/test-slug');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/inv-multi-param/99/other-slug');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');
    });

    test('cached with query param', function () {
        $client = createLocalClient();

        $client->request('GET', '/inv-with-query', ['q' => 'hello']);
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('invokable q=hello');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-with-query', ['q' => 'hello']);
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/inv-with-query', ['q' => 'world']);
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');
    });

    test('cached with dto', function () {
        $client = createLocalClient();

        $client->request('GET', '/inv-combined?id=1');
        $this->assertResponseIsSuccessful();
        expect($client->getResponse()->getContent())->toBe('invokable id=1, filter=');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-combined?id=1');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/inv-combined?id=1&filter=recent');
        expect($client->getResponse()->getContent())->toBe('invokable id=1, filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-combined?id=2&filter=recent');
        expect($client->getResponse()->getContent())->toBe('invokable id=2, filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Miss');

        $client->request('GET', '/inv-combined?id=1&filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');

        $client->request('GET', '/inv-combined?id=2&filter=recent');
        expect($client->getResponse()->headers->get('X-Cache'))->toBe('Hit');
    });
});
