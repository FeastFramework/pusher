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

namespace FeastFramework\Pusher\Tests;

use Exception;
use Feast\HttpRequest\HttpRequest;
use Feast\Interfaces\HttpRequestInterface;

class MockService extends HttpRequest
{

    public static ?string $responseString = '';
    public static int $fakeResponseCode = 200;
    public function makeRequest(): HttpRequestInterface
    {
        return $this;
    }
    
    public function getResponseAsString(): string
    {
        return self::$responseString;
    }
    
    public function getResponseAsJson(): ?\stdClass
    {
        try {
            /** @var \stdClass $result */
            $result = json_decode(utf8_encode($this->getResponseAsString()), flags: JSON_THROW_ON_ERROR);
            return $result;
        } catch (Exception) {
            return null;
        }
    }
    
    public function getResponseCode(): ?int
    {
        return self::$fakeResponseCode;
    }
}