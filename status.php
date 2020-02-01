<?php
require __DIR__ . '/includes/bootstrap.inc.php';

$vk->setAccessToken($config['vkontakte']['api']['access_token']);
$text = '🌍 Online ' . date('H:i') . ' | ✨ Обработано ' . longNumber($redis->get('handled') ?: 0);
$vk->status->set(['text' => $text, 'group_id' => $config['vkontakte']['callback']['group_id']]);
