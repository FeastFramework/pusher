![FEAST Framework](https://github.com/FeastFramework/framework/blob/master/logos/feast-transparent-small.png?raw=true)

![PHPUnit](https://github.com/FeastFramework/pusher/workflows/PHPUnit/badge.svg?branch=master)
![Psalm Static analysis](https://github.com/FeastFramework/pusher/workflows/Psalm%20Static%20analysis/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/FeastFramework/pusher/branch/master/graph/badge.svg?token=IWFOXSdyRZ)](https://codecov.io/gh/FeastFramework/pusher)

![PHP Version](https://img.shields.io/packagist/php-v/feast/json)
[![Packagist](https://img.shields.io/packagist/v/feast/pusher)](https://packagist.org/packages/feast/pusher)
![License](https://img.shields.io/github/license/FeastFramework/pusher.svg)
[![Docs](https://img.shields.io/badge/docs-quickstart-green.svg)](https://docs.feast-framework.com)

# FEAST Pusher Plugin

This package is a plug to work with [Pusher](https://pusher.com)
for [FEAST Framework](https://github.com/FeastFramework/framework)

[Installation](#installation)

[Usage](#configuration-and-usage)

## Installation

Recommended installation uses composer. This allows for quick setup.

### Installing with Composer

Run `composer install feast/pusher`.

In `container.php`, add the following at the bottom of the file.

```php
$container->add(\FeastFramework\Pusher\Pusher::class,new \FeastFramework\Pusher\Pusher());
```

### Manual installation

Alternatively, if you do not wish to use composer, this plugin can be manually installed by downloading and placed in a
folder of your choosing if you do not wish to use composer. You will need to add a path mapping in `container.php`
before adding Pusher to the container. Replace `src` in the block below with the path to your installation of the Pusher
plugin.

```php
/** @var \Feast\Autoloader $autoLoader */
$autoLoader->addPathMapping('FeastFramework\\Pusher', ['src']);
$container->add(\FeastFramework\Pusher\Pusher::class,new \FeastFramework\Pusher\Pusher());
```

## Configuration and Usage

### Configuration

The Pusher plugin is configured by adding an array into your `configs/config.php` that contains the necessary keys. See
sample below. Note that if you name your configuration namespace `pusher`, then you do not need to pass it in to the
various methods.

```php
$environment['production'] = ['
    pusher' => [
        'cluster' => 'us2',
        'key' => 'Your-App-Key',
        'secret' => 'Your-Secret-Key',
        'appid' => 'App-ID'
    ]
];
```

### Injection and Instantiation

The Pusher plugin can be automatically injected into both controllers and plugin classes by type-hinting the
argument `\FeastFramework\Pusher\Pusher`. To learn more about Dependency injection in FEAST, see the
docs, [here](https://docs.feast-framework.com/service-container.html#dependency-injection)

Alternatively, you may directly instantiate by calling `new Pusher();`

### Usage

The Pusher object has the following methods. All methods take various parameters as well as an optional configuration
namespace. Methods return objects that represent the data from the pusher API. See the `src/Response` folder for
details.

1. `getUsers` - get user information for a channel
    1. Parameters
        1. `channel`
        2. `pusherConfigNamespace` - defaults to `pusher`
2. `event` - Trigger a single event.
    1. Parameters
        1. `name` - The name of the event to trigger.
        2. `data` - An array or stdClass of data to pass to the event.
        3. `channels` - Either a single channel as a string, or an array of channels to publish to.
        4. `socketId` - Exclude the event from the given socket id if passed in. Defaults to null.
        5. `info` - An array of attributes which should be returned. Currently valid values are user_count and
           subscription_count.
        6. `pusherConfigNamespace` - defaults to `pusher`
3. `batchEvents` - Trigger multiple events.
    1. Parameters
        1. `eventData` - See the
           Pusher [docs](https://pusher.com/docs/channels/library_auth_reference/rest-api#post-batch-events-trigger-multiple-events-)
           for more info.
        2. `pusherConfigNamespace` - defaults to `pusher`
4. `channelInfo` - Fetch information for a single channel.
    1. Parameters
        1. `channel` - Channel name to fetch information for
        2. `infoType` - An array of attributes to fetch. Valid options are `user_count` and `subscription_count`.
        3. `pusherConfigNamespace` - defaults to `pusher`
5. `channelsInfo` - Fetch information for multiple channels
    1. Parameters
        1. `prefix` - Filter returned values by specified prefix. Defaults to null.
        2. `infoType` - An array of attributes to fetch. Valid option currently only `user_count`.
        3. `pusherConfigNamespace` - defaults to `pusher`
