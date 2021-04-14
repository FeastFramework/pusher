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

use Feast\Enums\RequestMethod;
use Feast\Enums\ResponseCode;
use Feast\Exception\InvalidArgumentException;
use Feast\Exception\InvalidOptionException;
use Feast\Exception\ServerFailureException;
use Feast\Interfaces\ConfigInterface;
use Feast\Service;
use Feast\ServiceContainer\NotFoundException;
use stdClass;

class PusherService extends Service
{

    public const PUSHER_URL = 'https://api-{cluster}.pusher.com';

    protected string $cluster;
    protected string $secret;
    protected string $appId;
    protected string $authKey;
    protected string $pusherUrl;
    protected string $authVersion = '1.0';

    /**
     * Pusher constructor.
     *
     * @param string $configNamespace
     * @throws InvalidArgumentException
     * @throws InvalidOptionException
     * @throws \Feast\Exception\NotFoundException
     * @throws NotFoundException
     */
    public function __construct(string $configNamespace = 'pusher')
    {
        /** @var stdClass $config */
        $config = di(ConfigInterface::class)->getSetting($configNamespace);
        if (empty($config->cluser) || empty($config->key) || empty($config->appid) || empty($config->secret)) {
            throw new InvalidArgumentException(
                'Invalid pusher configuration key: ' . $configNamespace . '. Please ensure all keys are set.' . "\n" . 'Required keys: cluster, key, secret, appid'
            );
        }
        $this->cluster = (string)$config->cluster;
        $this->authKey = (string)$config->key;
        $this->appId = (string)$config->appid;
        $this->secret = (string)$config->secret;

        $this->pusherUrl = str_replace('{cluster}', $this->cluster, self::PUSHER_URL);
        parent::__construct();
    }

    /**
     * Get information for users
     *
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#get-users
     *
     * @param string $channel Channel name to fetch information for.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function getUsers(string $channel): ?stdClass
    {
        $this->makeRequest('/apps/' . $this->appId . '/channels/' . $channel . '/users');

        return $this->httpRequest->getResponseAsJson();
    }

    /**
     * Batch multiple events.
     *
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#post-batch-events-trigger-multiple-events-
     *
     * @param array<array> $eventData See Pusher documentation for more info.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function batchEvents(
        array $eventData
    ): ?stdClass {
        $requestData = [
            'batch' => $eventData
        ];
        $this->makeRequest('/apps/' . $this->appId . '/batch_events', RequestMethod::POST, $requestData);

        return $this->httpRequest->getResponseAsJson();
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
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function event(
        string $name,
        array|stdClass $data,
        string|array $channels,
        ?string $socketId = null,
        ?array $info = null
    ): ?stdClass {
        $requestData = [
            'name' => $name,
            'data' => json_encode($data),
        ];
        if (is_string($channels)) {
            $requestData['channel'] = $channels;
        } else {
            $requestData['channels'] = $channels;
        }
        if ($socketId !== null) {
            $requestData['socket_id'] = $socketId;
        }

        if ($info !== null) {
            $requestData['info'] = implode(',', $info);
        }

        $this->makeRequest('/apps/' . $this->appId . '/events', RequestMethod::POST, $requestData);

        return $this->httpRequest->getResponseAsJson();
    }

    /**
     * Fetch information for a single channel.
     *
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#get-channel-fetch-info-for-one-channel-
     *
     * @param string $channel Channel name to fetch information for.
     * @param array $infoType The information type to fetch. Valid options are currently 'user_count' and 'subscription_count'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function getChannelInfo(string $channel, array $infoType): ?stdClass
    {
        $this->makeRequest(
            '/apps/' . $this->appId . '/channels/' . $channel,
            arguments: ['info' => implode(',', $infoType)]
        );

        return $this->httpRequest->getResponseAsJson();
    }

    /**
     * Fetch information for multiple channels.
     *
     * https://pusher.com/docs/channels/library_auth_reference/rest-api#get-channels-fetch-info-for-multiple-channels-
     *
     * @param string|null $prefix Filters returned channels by specified prefix.
     * @param array|null $infoType The information type to fetch. Valid option currently only 'user_count'.
     * @return \stdClass|null
     * @throws \Feast\Exception\ServerFailureException
     */
    public function getChannelsInfo(?string $prefix = null, ?array $infoType = null): ?stdClass
    {
        $arguments = [];
        if ($prefix !== null) {
            $arguments['filter_by_prefix'] = $prefix;
        }
        if ($infoType !== null) {
            $arguments['info'] = implode(',', $infoType);
        }
        $this->makeRequest('/apps/' . $this->appId . '/channels', arguments: $arguments);

        return $this->httpRequest->getResponseAsJson();
    }

    protected function initRequest(
        string $url,
        string $method,
        array $queryStringArguments,
        ?array $arguments = []
    ): void {
        $arguments ??= [];
        switch ($method) {
            case RequestMethod::GET:
                $this->httpRequest->get($this->pusherUrl . $url);
                $this->httpRequest->addArguments(array_merge($queryStringArguments, $arguments));
                break;
            case RequestMethod::POST:
                $this->httpRequest->postJson($this->pusherUrl . $url . '?' . http_build_query($queryStringArguments));
                if (!empty($arguments)) {
                    $this->httpRequest->setArguments($arguments);
                }
                break;
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param array|null $arguments
     * @throws ServerFailureException
     */
    protected function makeRequest(
        string $url,
        string $method = RequestMethod::GET,
        array|null $arguments = null
    ): void {
        $requestTimestamp = time();
        $requestArguments = [
            'auth_key' => $this->authKey,
            'auth_timestamp' => (string)$requestTimestamp,
            'auth_version' => $this->authVersion
        ];
        $bodyMd5 = null;
        if ($arguments !== null) {
            if ($method !== RequestMethod::GET && $method !== RequestMethod::DELETE) {
                $body = json_encode($arguments);
                $bodyMd5 = md5($body);
                $requestArguments['body_md5'] = $bodyMd5;
            }
        }
        $requestArguments['auth_signature'] = $this->generateSignature(
            $url,
            $requestTimestamp,
            $method,
            $bodyMd5,
            $arguments
        );
        $this->initRequest($url, $method, $requestArguments, $arguments);
        if ($arguments !== null && $method !== RequestMethod::GET) {
            $this->httpRequest->setArguments($arguments);
        }
        $this->httpRequest->makeRequest();
        if ($this->httpRequest->getResponseCode() !== ResponseCode::HTTP_CODE_200) {
            throw new ServerFailureException($this->httpRequest->getResponseAsString());
        }
    }

    protected function generateSignature(
        string $url,
        int $timestamp,
        string $method = RequestMethod::GET,
        ?string $bodyMd5 = null,
        ?array $arguments = null
    ): string {
        $data = $method . "\n" . $url . "\n" . 'auth_key=' . $this->authKey . '&auth_timestamp=' . (string)$timestamp . '&auth_version=' . $this->authVersion;
        if ($bodyMd5 !== null) {
            $data .= '&body_md5=' . $bodyMd5;
        }
        if ($method === RequestMethod::GET && $arguments !== null) {
            ksort($arguments);
            /**
             * @var string $key
             * @var string $val
             */
            foreach ($arguments as $key => $val) {
                $data .= '&' . $key . '=' . $val;
            }
        }

        return hash_hmac('sha256', $data, $this->secret);
    }
}