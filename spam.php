<?php
require __DIR__ . '/includes/bootstrap.inc.php';

$offset = 0;
$conversations = [];
do {
    $response = $vk->messages->getConversations([
        'count' => 200,
        'offset' => $offset,
        'extended' => false
    ]);
    $conversations = array_merge($conversations, $response['items']);
    $offset += 200;
    usleep(333333);
} while (count($conversations) !== $response['count']);

$users = [];
foreach ($conversations as $conversation) {
    $users[] = $conversation['conversation']['peer']['id'];
}

$message = '⚡ Работа вновь восстановлена, если вам не пришел ответ на команду, то повторите её.';
foreach (array_chunk($users, 2500) as $bigChunk) {
    $execute = "var message = '{$message}';" . PHP_EOL;
    foreach (array_chunk($bigChunk, 100) as $smallChunk) {
        $execute .= 'API.messages.send({"message": message, "user_ids": "' . implode(',', $smallChunk) . '", "random_id": "' . rand() . '"});' . PHP_EOL;
    }
    $execute .= 'return true;';

    $response = $vk->execute(['code' => $execute]);
    echo json_encode($response) . PHP_EOL;
}
