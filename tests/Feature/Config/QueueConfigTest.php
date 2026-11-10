<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class QueueConfigTest extends TestCase
{
    public function test_database_and_redis_retry_after_defaults_leave_room_for_correspondence_job_timeouts(): void
    {
        $this->assertSame(310, config('queue.connections.database.retry_after'));
        $this->assertSame(310, config('queue.connections.redis.retry_after'));
    }
}
