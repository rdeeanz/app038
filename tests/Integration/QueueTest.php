<?php

namespace Tests\Integration;

use App\Modules\Sales\Jobs\ProcessOrderJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_dispatch_job_to_queue(): void
    {
        $orderData = [
            'id' => 1,
            'order_number' => 'ORD-001',
            'total' => 100.00,
        ];

        ProcessOrderJob::dispatch($orderData);

        Queue::assertPushed(ProcessOrderJob::class, function ($job) use ($orderData) {
            return $job->orderData['id'] === $orderData['id'];
        });
    }

    public function test_job_is_queued_with_correct_data(): void
    {
        $orderData = [
            'id' => 1,
            'order_number' => 'ORD-001',
        ];

        ProcessOrderJob::dispatch($orderData);

        Queue::assertPushed(ProcessOrderJob::class, function ($job) {
            return isset($job->orderData['id']) && isset($job->orderData['order_number']);
        });
    }
}

