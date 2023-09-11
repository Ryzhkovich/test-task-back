<?php
require_once __DIR__ . '/vendor/autoload.php';

use app\enums\UrlsEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function logMessage($message, $level = 'INFO')
{
    $logEntry = date('[Y-m-d H:i:s]') . " $level: $message\n";
    file_put_contents(__DIR__ . '/1.log', $logEntry, FILE_APPEND);
}

try {
    // Создайте соединение с RabbitMQ
//    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    // Определите очередь
    $channel->queue_declare('url_queue', false, true, false, false);

    $urls = UrlsEnum::getUrls();

    foreach ($urls as $url) {
        // Отправьте URL в очередь
        $message = new AMQPMessage($url);
        $channel->basic_publish($message, '', 'url_queue');
        echo "Sent URL: $url\n";
        logMessage("Sent URL: $url"); // Залогируйте отправку
        sleep(rand(5, 20)); // Задержка перед отправкой следующего URL
    }

    $channel->close();
    $connection->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    logMessage("Error: " . $e->getMessage(), 'ERROR'); // Залогируйте ошибку
}
