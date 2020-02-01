<?php
return function (array $container, array $object, array $arguments) {
    extract($container);

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
    $damagers_data = json_decode(file_get_contents(__DIR__ . '/../data/damagers.json'), true);

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

    $combo_medals = [
        'bronze' => 0,
        'silver' => 0,
        'gold' => 0
    ];
    foreach ($data->spell_combo->counts->combo as $combo) {
        if ($combo >= 100) {
            ++$combo_medals['gold'];
        } elseif ($combo >= 50) {
            ++$combo_medals['silver'];
        } elseif ($combo >= 10) {
            ++$combo_medals['bronze'];
        }
    }

    $contributions = [
        63 => 'Пацанский',
        64 => 'Блатной',
        65 => 'Авторитетный',
        66 => 'Воровской'
    ];
    $contribution = null;
    foreach ($data->buffs->buff as $buff) {
        $buff_id = (Int)$buff->attributes()->id;
        $time = (Int)$buff->attributes()->end_ts;
        if (isset($contributions[$buff_id]) && $time > time()) {
            $contribution = '💣 Общак: ' . $contributions[$buff_id] . ' (до ' . date('d.m.Y', $time) . ')';
            break;
        }
    }

    $message = '⛔ ID: ' . $id . PHP_EOL;
    $message .= '💭 Кликуха: ' . urldecode($data->user->name) . PHP_EOL;
    $message .= isset($data->user->taunt) ? '💬 Базар: ' . mb_strimwidth($data->user->taunt, 0, 25, '...') . PHP_EOL : '';
    $message .= '🤘🏻 Авторитет: ' . longNumber((Int)$data->user->rating) . PHP_EOL;
    $message .= '👦 Борода: ' . (Int)$data->user->beard . PHP_EOL;
    $message .= '🔨 Занныканый Шмот: ' . longNumber((Int)$data->craft_coolness) . PHP_EOL;
    $message .= '☀ Достижения: ' . longNumber($achievement_points) . '/' . longNumber($achievements_data['points_sum']) . PHP_EOL;
    $message .= $contribution ? $contribution . PHP_EOL : '';
    $message .= isset($damagers_data[$id]) ? '💥 Заявленный урон: ' . longNumber($damagers_data[$id]) . PHP_EOL : '';
    $message .= '↙ Боевые ↘' . PHP_EOL;
    $message .= '⚡ Таланты: ' . longNumber(array_sum((Array)$data->user->playerTalents->talent)) . PHP_EOL;
    $message .= '🔪 Урон: ' . shortNumber($records_data['damage'][$achievements[1]]) . '+' . PHP_EOL;
    $message .= '💉 Яды: ' . shortNumber($records_data['poison'][$achievements[2]]) . '+' . PHP_EOL;
    $message .= '🔫 Самопалы: ' . shortNumber($records_data['gun'][$achievements[1051]]) . '+' . PHP_EOL;
    $message .= '💰 Рубли: ' . shortNumber($records_data['rubles'][$achievements[42]]) . '+' . PHP_EOL;
    $message .= '↙ Комбо ↘' . PHP_EOL;
    $message .= '🥉Бронза: ' . $combo_medals['bronze'] . PHP_EOL;
    $message .= '🥈Серебро: ' . $combo_medals['silver'] . PHP_EOL;
    $message .= '🥇Золото: ' . $combo_medals['gold'] . PHP_EOL;

    if (isset($data->playerGuild)) {
        $guild_levels_data = json_decode(file_get_contents(__DIR__ . '/../data/guild_levels.json'), true);
        foreach ($guild_levels_data as $guild_level => $level_experience) {
            if ($data->playerGuild->exp >= $level_experience) {
                break;
            }
        }

        $message .= '↙ Бригада ↘' . PHP_EOL;
        $message .= '🔆 ID: ' . $data->playerGuild->guildId . PHP_EOL;
        $message .= '🔱 Название: @id' . $data->playerGuild->founder . ' (' . $data->playerGuild->name . ')' . PHP_EOL;
        $message .= '⚡ Уровень: ' . $guild_level . PHP_EOL;
        $message .= '💼 Звание: ' . $data->playerGuild->rank . ' (' . $data->playerGuild->rank->attributes()->id . ')' . PHP_EOL;
        $message .= '⏰ Создана: ' . date('d.m.Y в H:i:s', (Int)$data->playerGuild->creation) . PHP_EOL;
    }

    $vk->messages->send([
        'peer_id' => $object['peer_id'],
        'random_id' => rand(0, pow(2, 31)),
        'message' => $message
    ]);
}; 
