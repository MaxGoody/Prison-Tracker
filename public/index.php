<?php
require __DIR__ . '/../includes/bootstrap.inc.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    exit($_SERVER['REQUEST_METHOD'] . ' is not allowed!');
}

$payload = json_decode(file_get_contents('php://input'), true);
if ($payload === false) {
    http_response_code(400);
    exit('Failed to parse JSON body!');
}

if (
    $payload['group_id'] !== $config['vkontakte']['callback']['group_id'] ||
    $payload['secret'] !== $config['vkontakte']['callback']['secret']
) {
    exit('Invalid credentials!');
}

switch ($payload['type']) {
    case 'confirmation':
        exit($config['vkontakte']['callback']['confirmation_code']);
        break;
    case 'message_new':
        echo 'ok';
        fastcgi_finish_request();

        $object = $payload['object'];
        $text = empty($object['payload']) ? $object['text'] : json_decode($object['payload'], true);
        if (strpos($text, '!') === 0 || strpos($text, '/') === 0) {
            $parts = preg_split('/\s+/', substr($text, 1));
            $command = strtolower(array_shift($parts));
            $arguments = array_values($parts);

            $path = __DIR__ . '/../commands/' . $command . '.php';
            if (is_file($path)) {
                $redis->incr('handled');

                (require $path)(
                    compact('vk', 'prison', 'redis', 'config'),
                    $object,
                    $arguments
                );

                if ($object['peer_id'] < 2000000000 && !$redis->hExists('keyboard', $object['peer_id'])) {
                    $redis->hSet('keyboard', $object['peer_id'], true);
                    $vk->messages->send([
                        'peer_id' => $object['peer_id'],
                        'random_id' => rand(0, pow(2, 31)),
                        'message' => '🚫 Это сообщение было использовано для отображение клавиатуры, не отвечайте на него!',
                        'keyboard' => json_encode([
                            'one_time' => false,
                            'buttons' => [
                                [
                                    [
                                        'action' => [
                                            'type' => 'text',
                                            'label' => 'Короткая Статистика',
                                            'payload' => json_encode('!short')
                                        ],
                                        'color' => 'positive'
                                    ],
                                    [
                                        'action' => [
                                            'type' => 'text',
                                            'label' => 'Полная Статистика',
                                            'payload' => json_encode('!full')
                                        ],
                                        'color' => 'negative'
                                    ]
                                ],
                                [
                                    [
                                        'action' => [
                                            'type' => 'text',
                                            'label' => 'Инкассации',
                                            'payload' => json_encode('!bombers')
                                        ],
                                        'color' => 'primary'
                                    ],
                                    [
                                        'action' => [
                                            'type' => 'text',
                                            'label' => 'Приезды Боссов',
                                            'payload' => json_encode('!bosses')
                                        ],
                                        'color' => 'secondary'
                                    ]
                                ]
                            ]
                        ])
                    ]);
                }
            } elseif ($object['peer_id'] < 2000000000) {
                $vk->messages->send([
                    'peer_id' => $object['peer_id'],
                    'random_id' => rand(0, pow(2, 31)),
                    'message' => '⚠ Команда не найдена!'
                ]);
            }
        }
        break;
    default:
        exit('Unknown event!');
        break;
}
