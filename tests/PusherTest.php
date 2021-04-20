<?php

/**
 * Copyright 2021 Jeremy Presutti <Jeremy@Presutti.us>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace FeastFramework\Pusher\PusherTest;

use Feast\Exception\InvalidArgumentException;
use Feast\Exception\ServerFailureException;
use Feast\Interfaces\ConfigInterface;
use Feast\ServiceContainer\ServiceContainer;
use FeastFramework\Pusher\Pusher;
use FeastFramework\Pusher\PusherService;
use FeastFramework\Pusher\Response\BatchEvent;
use FeastFramework\Pusher\Response\Channel;
use FeastFramework\Pusher\Response\Channels;
use FeastFramework\Pusher\Response\Event;
use FeastFramework\Pusher\Response\User;
use FeastFramework\Pusher\Response\Users;
use FeastFramework\Pusher\Tests\MockService;
use PHPUnit\Framework\TestCase;

class PusherTest extends TestCase
{
    public function setUp(): void
    {
        MockService::$fakeResponseCode = 200;
    }

    public function testInvalidConfig(): void
    {
        $this->buildContainer(null);
        $this->expectException(InvalidArgumentException::class);
        $pusherService = new PusherService('pusher');
    }

    public function testEvent(): void
    {
        $this->buildContainer();
        MockService::$responseString = '{
  "channels": {
    "presence-foobar": { "user_count": 42, "subscription_count": 51 },
    "presence-another": { "user_count": 123, "subscription_count": 140 },
    "another": { "subscription_count": 13 }
  }
}';
        $pusherService = new Pusher();
        $result = $pusherService->event('Test', ['test'], 'feast-framework', null, ['subscription_count']);
        $this->assertTrue($result instanceof Event);
        $this->assertEquals(42, $result->channels['presence-foobar']->userCount);
    }

    public function testBrokenEvent(): void
    {
        $this->buildContainer();
        MockService::$responseString = '';
        $pusherService = new Pusher();
        $result = $pusherService->event('Test', ['test'], 'feast-framework', null, ['subscription_count']);
        $this->assertNull($result);
    }

    public function testInvalidResponseEvent(): void
    {
        $this->buildContainer();
        MockService::$fakeResponseCode = 404;
        $pusherService = new Pusher();
        $this->expectException(ServerFailureException::class);
        $result = $pusherService->event('Test', ['test'], 'feast-framework', null, ['subscription_count']);
    }

    public function testEventMultiChannel(): void
    {
        $this->buildContainer();
        MockService::$responseString = '{
  "channels": {
    "presence-foobar": { "user_count": 42, "subscription_count": 51 },
    "presence-another": { "user_count": 123, "subscription_count": 140 },
    "another": { "subscription_count": 13 }
  }
}';
        $pusherService = new Pusher();
        $result = $pusherService->event('Test', ['test'], ['feast-framework'], 'test', ['subscription_count']);
        $this->assertTrue($result instanceof Event);
        $this->assertEquals(42, $result->channels['presence-foobar']->userCount);
    }

    public function testUsersNull(): void
    {
        $this->buildContainer();
        MockService::$responseString = '';
        $pusherService = new Pusher();
        $result = $pusherService->getUsers('feast-framework');
        $this->assertNull($result);
    }

    public function testUsers(): void
    {
        $this->buildContainer();
        MockService::$responseString = '{ "users": [{ "id": "1" }, { "id": "2" }] }';
        $pusherService = new Pusher();
        $result = $pusherService->getUsers('feast-framework');
        $this->assertTrue($result instanceof Users);
        $this->assertCount(2, $result->users);
        $this->assertTrue($result->users[1] instanceof User);
        $this->assertEquals('2', $result->users[1]->id);
    }

    public function testBatchEventsNull(): void
    {
        $this->buildContainer();
        MockService::$responseString = '';
        $pusherService = new Pusher();
        $result = $pusherService->batchEvents([]);
        $this->assertNull($result);
    }

    public function testBatchEvents(): void
    {
        $this->buildContainer();
        MockService::$responseString = '{
  "batch": [
    { "user_count": 42, "subscription_count": 51 },
    {},
    { "subscription_count": 13 }
  ]
}';
        $pusherService = new Pusher();
        $result = $pusherService->batchEvents([]);
        $this->assertTrue($result instanceof BatchEvent);
        $this->assertTrue($result->batch[2] instanceof Channel);
        $this->assertEquals(13, $result->batch[2]->subscriptionCount);
        $this->assertEquals(42, $result->batch[0]->userCount);
    }

    public function testChannelNull(): void
    {
        $this->buildContainer();
        MockService::$responseString = '';
        $pusherService = new Pusher();
        $result = $pusherService->channelInfo('feast-framework', []);
        $this->assertNull($result);
    }

    public function testChannelsNull(): void
    {
        $this->buildContainer();
        MockService::$responseString = '';
        $pusherService = new Pusher();
        $result = $pusherService->channelsInfo();
        $this->assertNull($result);
    }

    public function testChannel(): void
    {
        $this->buildContainer();
        MockService::$responseString = '{ "occupied": true, "user_count": 42, "subscription_count": 713 }';
        $pusherService = new Pusher();
        $result = $pusherService->channelInfo('feast-framework',['user_count','subscription_count']);
        $this->assertTrue($result instanceof Channel);
        $this->assertTrue($result->occupied);
        $this->assertEquals(42,$result->userCount);
        $this->assertEquals(713,$result->subscriptionCount);
    }
    public function testChannels(): void
    {
        $this->buildContainer();
        MockService::$responseString = '{
  "channels": {
    "presence-foobar": { "user_count": 42 },
    "presence-another": { "user_count": 123 }
  }
}';
        $pusherService = new Pusher();
        $result = $pusherService->channelsInfo('presence',['user_count']);
        $this->assertTrue($result instanceof Channels);
        $this->assertCount(2,$result->channels);
        $this->assertEquals(42,$result->channels['presence-foobar']->userCount);
        $this->assertEquals(123,$result->channels['presence-another']->userCount);
    }

    protected function buildContainer(
        mixed $configItem = [
            'cluster' => 'us-east',
            'key' => 'test',
            'appid' => '1',
            'secret' => 'no-secret-for-you'
        ]
    ): void {
        if (is_array($configItem)) {
            $configItem = (object)$configItem;
        }
        $config = $this->createStub(ConfigInterface::class);
        $config->method('getSetting')->willReturnOnConsecutiveCalls(

            $configItem,
            \FeastFramework\Pusher\Tests\MockService::class
        );
        /** @var ServiceContainer $container */
        $container = di(null, \Feast\Enums\ServiceContainer::CLEAR_CONTAINER);
        $container->add(ConfigInterface::class, $config);
    }

}