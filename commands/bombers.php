<?php
return function (array $container, array $object) {
    extract($container);

    if ($object['peer_id'] >= 2000000000) {
        $key = 'blocked:bombers:' . $object['peer_id'];
        $blocked = $redis->get($key);
        if ($blocked) {
            $vk->messages->send([
                'peer_id' => $object['peer_id'],
                'random_id' => rand(0, pow(2, 31)),
                'message' => '⚠ Команда будет доступна через ' . $redis->ttl($key) . ' сек!'
            ]);
            return;
        }
        $redis->set($key, 'true', 3600);
    }

    $bombers = json_decode(file_get_contents(__DIR__ . '/../data/bombers.json'), true);
    $message = '';
    foreach ($bombers as $name => $zone) {
        $message .= '☀ ' . $name . ' ⏳ ' . date('d.m', $zone['time']) . PHP_EOL;
        foreach ($zone['nodes'] as $node) {
            $message .= '- ' . $node . PHP_EOL;
        }
    }

    $vk->messages->send([
        'peer_id' => $object['peer_id'],
        'random_id' => rand(0, pow(2, 31)),
        'message' => $message
    ]);
};
