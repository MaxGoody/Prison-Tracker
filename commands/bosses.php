<?php
return function (array $container, array $object) {
    extract($container);

    if ($object['peer_id'] >= 2000000000) {
        $key = 'blocked:bosses:' . $object['peer_id'];
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

    $bosses = json_decode(file_get_contents(__DIR__ . '/../data/bosses.json'), true);

    uasort(
        $bosses,
        function ($first, $second) {
            return $first['next'] <=> $second['next'];
        }
    );

    $message = '';
    foreach ($bosses as $name => $boss) {
        $message .= '🔥 ' . $name . ' (' . $boss['zone'] . ')' . ($boss['next'] === null ? '' : ' - ' . date('d.m в H:i:s', $boss['next'])) . PHP_EOL;
    }

    $vk->messages->send([
        'peer_id' => $object['peer_id'],
        'random_id' => rand(0, pow(2, 31)),
        'message' => $message
    ]);
};
