<?php

/**
 * Copyright 2021 Jeremy Presutti <Jeremy@Presutti.us>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace FeastFramework\Pusher;

use Feast\Plugin;
use Feast\ServiceContainer\ServiceContainerItemInterface;
use Feast\View;
use stdClass;

class Pusher extends Plugin implements ServiceContainerItemInterface
{

    /**
     * @var array<\FeastFramework\Pusher\PusherService>
     */
    protected array $pusherConnections = [];

    /**
     * @throws \Feast\ServiceContainer\NotFoundException
     */
    public function __construct()
    {
        parent::__construct(di(View::class));
    }

    /**
     * Get information for users
     * 
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#get-users
     * 
     * @param string $channel Channel name to fetch information for.
     * @param string $pusherConfigNamespace Configuration namespace to load connection info for. Defaults to 'pusher'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function getUsers(string $channel, string $pusherConfigNamespace = 'pusher'): ?stdClass
    {
        $pusher = $this->getPusherService($pusherConfigNamespace);
        return $pusher->getUsers($channel);
    }

    /**
     * Batch multiple events.
     *
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#post-batch-events-trigger-multiple-events-
     *
     * @param array<array> $eventData See Pusher documentation for more info.
     * @param string $pusherConfigNamespace Configuration namespace to load connection info for. Defaults to 'pusher'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function batchEvents(array $eventData, string $pusherConfigNamespace = 'pusher'): ?stdClass
    {
        $pusher = $this->getPusherService($pusherConfigNamespace);
        return $pusher->batchEvents($eventData);
    }

    /**
     * Fetch information for a single channel.
     * 
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#get-channel-fetch-info-for-one-channel-
     * 
     * @param string $channel Channel name to fetch information for.
     * @param array $infoType The information type to fetch. Valid options are currently 'user_count' and 'subscription_count'.
     * @param string $pusherConfigNamespace Configuration namespace to load connection info for. Defaults to 'pusher'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function channelInfo(string $channel, array $infoType, string $pusherConfigNamespace = 'pusher'): ?stdClass
    {
        $pusher = $this->getPusherService($pusherConfigNamespace);
        return $pusher->getChannelInfo($channel,$infoType);
    }

    /**
     * Fetch information for multiple channels.
     * 
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#get-channels-fetch-info-for-multiple-channels-
     * 
     * @param string|null $prefix Filters returned channels by specified prefix.
     * @param array|null $infoType The information type to fetch. Valid option currently only 'user_count'.
     * @param string $pusherConfigNamespace Configuration namespace to load connection info for. Defaults to 'pusher'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function channelsInfo(string $prefix = null, ?array $infoType = null, string $pusherConfigNamespace = 'pusher'): ?stdClass
    {
        $pusher = $this->getPusherService($pusherConfigNamespace);
        return $pusher->getChannelsInfo($prefix,$infoType);
    }

    /**
     * Trigger a single event.
     * 
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#post-event-trigger-an-event-
     * 
     * @param string $name The name of the event to trigger.
     * @param array|\stdClass $data An array or stdClass of data to pass on the event.
     * @param string|array $channels Either a single channel name as a string or an array of channels to publish to.
     * @param string|null $socketId Exclude the event from the given socket id
     * @param array|null $info List of attributes which should be returned for each unique channel triggered to. Currently valid values are user_count and subscription_count.
     * @param string $pusherConfigNamespace Configuration namespace to load connection info for. Defaults to 'pusher'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function event(
        string $name,
        array|stdClass $data,
        string|array $channels,
        ?string $socketId = null,
        ?array $info = null,
        string $pusherConfigNamespace = 'pusher'
    ): ?stdClass {
        $pusher = $this->getPusherService($pusherConfigNamespace);
        return $pusher->event($name, $data, $channels, $socketId, $info);
    }

    protected function getPusherService(string $pusherConfigNamespace): PusherService
    {
        if (!isset($this->pusherConnections[$pusherConfigNamespace])) {
            $this->pusherConnections[$pusherConfigNamespace] = new PusherService($pusherConfigNamespace);
        }
        return $this->pusherConnections[$pusherConfigNamespace];
    }

}
