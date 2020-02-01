<?php

use MaxGoody\Helium\Client as Prison;
use MaxGoody\Helium\Exceptions\ResponseException;
use MaxGoody\Hydrogen\Client as VK;

function resolveUser(VK $vk, array $object, array $arguments)
{
    if (empty($arguments)) {
        if (count($object['fwd_messages']) > 0 && $object['fwd_messages'][0]['from_id'] > 0) {
            return $object['fwd_messages'][0]['from_id'];
        }

        return $object['from_id'];
    }

    $target = $arguments[0];
    if (filter_var($target, FILTER_VALIDATE_INT) && $target > 0) {
        return (Int)$target;
    }

    if (
        preg_match('/^\[(.*?)\|.*?\]$/', $target, $matches) ||
        preg_match('/^(?:https?:\/\/)?(?:m\.)?vk\.com\/(.+)$/', $target, $matches)
    ) {
        $screen_name = $matches[1];
    } else {
        $screen_name = $target;
    }

    $response = $vk->utils->resolveScreenName(compact('screen_name'));
    if (empty($response) || $response['type'] != 'user') {
        return null;
    }

    return (Int)$response['object_id'];
}

function getUserData(Redis $redis, Prison $prison, int $id)
{
    $key = 'cache:' . $id;
    if ($redis->exists($key)) {
        $data = $redis->get('cache:' . $id);
        return $data === 'null' ? null : simplexml_load_string($data);
    }

    try {
        $data = $prison->getFriendModels(['friend_uid' => $id, 'with_guild' => true]);
    } catch (ResponseException $exception) {
        if ($exception->getCode() === 4) {
            $redis->set('cache:' . $id, 'null', 86400);
            return null;
        }

        return false;
    }

    $redis->set('cache:' . $id, $data->asXML(), 3600);

    return $data;
}

function longNumber(int $number)
{
    return number_format($number, 0, '', ' ');
}

function shortNumber(int $number)
{
    $endiands = [
        'T' => 1000000000000,
        'B' => 1000000000,
        'M' => 1000000,
        'K' => 1000
    ];

    foreach ($endiands as $key => $value) {
        if ($number >= $value) {
            return round($number / $value, 2, PHP_ROUND_HALF_DOWN) . $key;
        }
    }

    return $value;
}
