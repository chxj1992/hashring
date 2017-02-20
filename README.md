hashring
============================

[![Build Status](https://travis-ci.org/chxj1992/hashring.svg?branch=master)](https://travis-ci.org/chxj1992/hashring)

Implements consistent hashing that can be used when
the number of server nodes can increase or decrease (like in memcached).
The hashing ring is built using the same algorithm as libketama.

Inspired by a golang hashring library [serialx/hashring](https://github.com/serialx/hashring).


Using
============================

Install ::

```bash
composer require chxj1992/hashring:~1.0
```

Basic example usage ::

```php
$memcacheServers = ["192.168.0.246:11212",
                    "192.168.0.247:11212",
                    "192.168.0.249:11212"];

$hashRing = new \Chxj1992\HashRing\HashRing($memcacheServers);
$server = $ring->getNode("my_key");
```

Using weights example ::

```php
$weights = ["192.168.0.246:11212" => 1,
            "192.168.0.247:11212" => 2,
            "192.168.0.249:11212" => 1];

$hashRing = new \Chxj1992\HashRing\HashRing($weights);
$server = $hashRing->getNode("my_key");
```

Adding and removing nodes example ::

```php
$memcacheServers = ["192.168.0.246:11212",
                    "192.168.0.247:11212",
                    "192.168.0.249:11212"];

$hashRing = new \Chxj1992\HashRing\HashRing($memcacheServers);
$hashRing = $hashRing->removeNode("192.168.0.246:11212");
$hashRing = $hashRing->addNode("192.168.0.250:11212");
$server = $hashRing->getNode("my_key");
```
