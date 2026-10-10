<?php

require_once __DIR__.'/AutoPosterTestCase.php';

use Fujiba\AutoPoster\Services\ChatpionPostClient;
use Illuminate\Support\Facades\Schema;

uses(Tests\Unit\AutoPoster\AutoPosterTestCase::class);

beforeEach(function () {
    $this->app->instance(ChatpionPostClient::class, Mockery::mock(ChatpionPostClient::class)); // no network
});

it('skips cleanly when the instagram_status column is missing', function () {
    Schema::shouldReceive('hasTable')->with('autoposter_queue')->andReturnTrue();
    Schema::shouldReceive('hasColumn')->with('autoposter_queue', 'instagram_status')->andReturnFalse();
    $this->artisan('autoposter:instagram')->expectsOutput('skipped: instagram schema absent')->assertExitCode(0);
});

it('skips cleanly when the autoposter_queue table is missing', function () {
    Schema::shouldReceive('hasTable')->with('autoposter_queue')->andReturnFalse();
    Schema::shouldReceive('hasColumn')->never();
    $this->artisan('autoposter:instagram --id=5')->expectsOutput('skipped: instagram schema absent')->assertExitCode(0);
});

it('falls through to the unchanged flow when the schema is present', function () {
    config(['autoposter.instagram.enabled' => false]);
    Schema::shouldReceive('hasTable')->andReturnTrue();
    Schema::shouldReceive('hasColumn')->andReturnTrue();
    $this->artisan('autoposter:instagram')
        ->expectsOutput('Instagram cross-posting disabled (AUTOPOSTER_INSTAGRAM_ENABLED)')
        ->doesntExpectOutput('skipped: instagram schema absent')
        ->assertExitCode(0);
});

it('does not consult the schema for --status', function () {
    config(['autoposter.page_ids' => []]);
    Schema::shouldReceive('hasTable')->never();
    $this->artisan('autoposter:instagram --status')->assertExitCode(0);
});
