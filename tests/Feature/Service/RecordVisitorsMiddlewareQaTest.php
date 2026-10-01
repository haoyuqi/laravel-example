<?php

namespace Tests\Feature\Service;

use App\Http\Middleware\RecordVisitors;
use App\Models\BlackList;
use App\Service\BlackListService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RecordVisitorsMiddlewareQaTest extends TestCase
{
    private object $redisManager;

    private object $redisConnection;

    private string $key;

    private string $ip = '203.0.113.240';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.redis.options.prefix' => 'middleware_qa_'.bin2hex(random_bytes(8)).'_']);
        $this->redisManager = app('redis');
        $this->redisConnection = $this->redisManager->connection();
        $this->key = app(BlackListService::class)->cacheKey();
    }

    public function test_middleware_blocks_created_ip_then_allows_after_delete(): void
    {
        $this->redisConnection->hset($this->key, $this->ip, '0');
        BlackList::create(['ip' => $this->ip]);

        try {
            $this->middleware()->handle($this->request(), fn () => new Response('continued', 200));
            $this->fail('Expected the middleware to abort a blacklisted request.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        BlackList::where('ip', $this->ip)->firstOrFail()->delete();
        $response = $this->middleware()->handle($this->request(), fn () => new Response('continued', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('continued', $response->getContent());
    }

    private function middleware(): RecordVisitors
    {
        return app(RecordVisitors::class);
    }

    private function request(): Request
    {
        return Request::create('/middleware-qa', 'GET', server: ['REMOTE_ADDR' => $this->ip]);
    }

    protected function tearDown(): void
    {
        try {
            Redis::clearResolvedInstance('redis');
            $this->app->instance('redis', $this->redisManager);
            $this->redisConnection->del($this->key);
        } finally {
            parent::tearDown();
        }
    }
}
