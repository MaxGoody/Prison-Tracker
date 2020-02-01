<?php

use MaxGoody\Helium\Client as Prison;
use MaxGoody\Hydrogen\Client as VK;

require __DIR__ . '/../vendor/autoload.php';

# Config
$config = require __DIR__ . '/config.inc.php';

# VKontakte API
$vk = new VK($config['vkontakte']['api']['group_access_token'], $config['vkontakte']['api']['version']);

# Prison
$randomId = array_rand($config['prison']);
$prison = new Prison($randomId, $config['prison'][$randomId]);

# Redis
$redis = new Redis();
$redis->connect('/var/run/redis/redis-server.sock');
$redis->select(1);

# Register functions.
require __DIR__ . '/functions.inc.php';
