<?php

namespace Tests\Feature\Auth;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiRateLimiterTest extends TestCase
{
    public function test_api_named_rate_limiter_is_registered(): void
    {
        $limiter = app(RateLimiter::class)->limiter('api');

        $this->assertNotNull($limiter);

        $limits = $limiter(Request::create('/api/v1/me', 'GET'));

        $this->assertIsArray($limits);
        $this->assertCount(2, $limits);
        $this->assertSame(180, $limits[0]->maxAttempts);
        $this->assertSame(600, $limits[1]->maxAttempts);
    }
}
