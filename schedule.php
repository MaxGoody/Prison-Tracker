<?php
ini_set('memory_limit', -1);

require __DIR__ . '/includes/bootstrap.inc.php';

$data = $prison->getData();
if ($data) {
    # Achievements
    $achievements = [
        'points' => [],
        'points_sum' => 0
    ];

    foreach ($data['achievements'] as $achievement) {
        $points = [0];
        foreach ($achievement['levels'] as $level) {
            $points[] = $level['points'];
            $achievements['points_sum'] += $level['points'];
        }
        $achievements['points'][$achievement['id']] = $points;
    }

    file_put_contents(__DIR__ . '/data/achievements.json', json_encode($achievements));

    # Records
    $achievements = array_combine(array_column($data['achievements'], 'id'), $data['achievements']);
    $temp = [
        1 => 'damage',
        2 => 'poison',
        42 => 'rubles',
        1051 => 'gun'
    ];
    $records = [];

    foreach ($temp as $key => $value) {
        $points = [0];
        foreach ($achievements[$key]['levels'] as $level) {
            $points[] = $level['reqs'][0]['value'];
        }
        $records[$value] = $points;
    }

    file_put_contents(__DIR__ . '/data/records.json', json_encode($records));

    # Guild Levels
    $guild_levels = [];
    foreach ($data['guilds']['levels'] as $level) {
        $guild_levels[$level['id']] = $level['minExp'];
    }

    krsort($guild_levels);

    file_put_contents(__DIR__ . '/data/guild_levels.json', json_encode($guild_levels));

    # Bosses
    $zones = array_combine(array_column($data['guilds']['travianInfo']['zones'], 'id'), $data['guilds']['travianInfo']['zones']);

    $bosses = [];
    foreach ($data['guilds']['travianInfo']['zoneBosses'] as $boss) {
        $next = null;
        foreach ($boss['launches'] as $launch) {
            if ($launch['startTS'] > time()) {
                $next = $launch['startTS'];
                break;
            }
        }

        $bosses[$boss['name']] = [
            'zone' => $zones[$boss['zoneId']]['name'],
            'next' => $next
        ];
    }
    file_put_contents(__DIR__ . '/data/bosses.json', json_encode($bosses));

    # Bombers
    $info = $prison->getInfo();
    if ($info) {
        $nodes = array_combine(array_column($data['guilds']['travianInfo']['nodes'], 'id'), $data['guilds']['travianInfo']['nodes']);

        $bombers = [];

        foreach ($info->manualSchedules->schedule as $schedule) {
            if ($schedule->type != 'travianBomber') {
                continue;
            }

            $node_id = (Int)$schedule->state;
            $node_name = $nodes[$node_id]['name'];
            $zone = $zones[$nodes[$node_id]['zoneId']]['name'];

            if (isset($bombers[$zone])) {
                $bombers[$zone]['nodes'][] = $node_name;
            } else {
                $bombers[$zone] = [
                    'time' => (Int)$schedule->finishTS,
                    'nodes' => [$node_name]
                ];
            }
        }

        file_put_contents(__DIR__ . '/data/bombers.json', json_encode($bombers));
    }
}
