<?php
return function (array $container, array $object, array $arguments) {
    extract($container);

    if ($object['peer_id'] >= 2000000000) {
        $key = 'blocked:short' . $object['peer_id'];
        $blocked = $redis->get($key);
        if ($blocked) {
            $vk->messages->send([
                'peer_id' => $object['peer_id'],
                'random_id' => rand(0, pow(2, 31)),
                'message' => '⚠ Команда будет доступна через ' . $redis->ttl($key) . ' сек!'
            ]);
            return;
        }
        $redis->set($key, 'true', 150);
    }

    $id = resolveUser($vk, $object, $arguments);
    if ($id === null) {
        $vk->messages->send([
            'peer_id' => $object['peer_id'],
            'random_id' => rand(0, pow(2, 31)),
            'message' => '⚠ Пользователь не найден!'
        ]);
        return;
    }

    $data = getUserData($redis, $prison, $id);
    if ($data === false) {
        $vk->messages->send([
            'peer_id' => $object['peer_id'],
            'random_id' => rand(0, pow(2, 31)),
            'message' => '⚠ Произошла неизвестная ошибка!'
        ]);
        return;
    } elseif ($data === null) {
        $vk->messages->send([
            'peer_id' => $object['peer_id'],
            'random_id' => rand(0, pow(2, 31)),
            'message' => '⚠ Пользователь не является игроком!'
        ]);
        return;
    }

    $achievements_data = json_decode(file_get_contents(__DIR__ . '/../data/achievements.json'), true);
    $records_data = json_decode(file_get_contents(__DIR__ . '/../data/records.json'), true);

    $achievements = [];
    $achievement_points = 0;
    foreach ($data->playerAchievements->achiev as $achievement) {
        $achievement_id = (Int)$achievement->attributes()->id;
        $achievement_level = (Int)$achievement;
        $achievements[$achievement_id] = $achievement_level;

        for ($index = 0; $index <= $achievement_level; ++$index) {
            $achievement_points += $achievements_data['points'][$achievement_id][$index];
        }
    }

    $message = '⛔ ID: ' . $id . PHP_EOL;
    $message .= '💭 Кликуха: ' . urldecode($data->user->name) . PHP_EOL;
    $message .= '🤘🏻 Авторитет: ' . longNumber((Int)$data->user->rating) . PHP_EOL;
    $message .= '👦 Борода: ' . (Int)$data->user->beard . PHP_EOL;
    $message .= '☀ Достижения: ' . longNumber($achievement_points) . '/' . longNumber($achievements_data['points_sum']) . PHP_EOL;
    $message .= '⚡ Таланты: ' . longNumber(array_sum((Array)$data->user->playerTalents->talent)) . PHP_EOL;
    $message .= '🔪 Урон: ' . shortNumber($records_data['damage'][$achievements[1]]) . '+' . PHP_EOL;
    $message .= '💉 Яды: ' . shortNumber($records_data['poison'][$achievements[2]]) . '+' . PHP_EOL;
    $message .= '🔫 Самопалы: ' . shortNumber($records_data['gun'][$achievements[1051]]) . '+' . PHP_EOL;
    $message .= '💰 Рубли: ' . shortNumber($records_data['rubles'][$achievements[42]]) . '+' . PHP_EOL;
    $message .= PHP_EOL . '⚠ Для подробной статистики используйте !full в диалоге с сообществом.' . PHP_EOL;

    $vk->messages->send([
        'peer_id' => $object['peer_id'],
        'random_id' => rand(0, pow(2, 31)),
        'message' => $message
    ]);
};
