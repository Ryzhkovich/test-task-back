<?php
require_once __DIR__ . '/vendor/autoload.php';
//require_once __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use app\services\UrlContent;
use PDO;
use ClickHouseDB\Client;
//global $pdo;

function logMessage($message, $level = 'INFO', $logFile = '1.log')
{
    $logEntry = date('[Y-m-d H:i:s]') . " $level: $message\n";
    file_put_contents(__DIR__ . '/' . $logFile, $logEntry, FILE_APPEND);
}

try {
    // Создайте соединение с RabbitMQ (используя имя контейнера "rabbitmq")
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    // Определите очередь url_queue
    $channel->queue_declare('url_queue', false, true, false, false);

    // Определите очередь result
    $channel->queue_declare('result', false, true, false, false);

    // Создайте подключение к базе данных (замените параметры на свои)
//    $pdo = new PDO('mysql:host=localhost;dbname=web', 'test', 'test');
    $options = [
        PDO::ATTR_EMULATE_PREPARES   => false, // Disable emulation mode for "real" prepared statements
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Disable errors in the form of exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Make the default fetch be an associative array
    ];
    $pdo = new PDO('mysql:host=mariadb;dbname=web;charset=utf8mb4', 'test', 'test', $options);


//    $clickHouseClient = new Client(['host' => 'clickhouse', 'port' => 8123, 'username' => 'default', 'password' => '']);
//    $clickHouseTable = 'urls';
//    $pdo = new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=web', 'test', 'test');

//    $callback = function ($message) use ($channel, $pdo, $clickHouseClient, $clickHouseTable) {
    $callback = function ($message) use ($channel, $pdo) {
        $startTime = microtime(true); // Время начала выполнения callback-а
        $url = $message->body;

        // Добавьте '?result=true' к URL
        $processedUrl = $url . '?result=true';

        // Выполните HTTP-запрос для получения длины контента
        $contentLength = UrlContent::getContentLength($processedUrl);

        // Вычислите время выполнения callback-а
        $executionTime = microtime(true) - $startTime;

        // Публикуйте данные о выполнении в очереди result
        $resultData = [
            'url' => $processedUrl,
            'executionTime' => $executionTime,
            'contentLength' => $contentLength,
        ];
        $resultMessage = new AMQPMessage(json_encode($resultData));
        $channel->basic_publish($resultMessage, '', 'result');

        echo "Processed URL: $processedUrl\n";
        logMessage("Processed URL: $processedUrl" . "INSERT INTO urls (url, content_length) VALUES (`{$processedUrl}`, `{$contentLength}`)", 'INFO', '2.log'); // Залогируйте второй файл

        // Добавьте запись в базу данных
        $stmt = $pdo->prepare('INSERT INTO urls (url, content_length) VALUES (:url, :contentLength)');
        $stmt->bindParam(':url', $processedUrl, PDO::PARAM_STR);
        $stmt->bindParam(':contentLength', $contentLength, PDO::PARAM_INT);
        $stmt->execute();

        // Запишите данные в ClickHouse
        /*$insertQuery = $clickHouseClient->insert(
            $clickHouseTable,
            [$processedUrl, $contentLength],
            ['url', 'content_length']
        );*/


//        logMessage("Inserted into DB: URL=$processedUrl, Content Length=$contentLength, clickhouse=" . implode(', ', $insertQuery->info()), 'INFO', '3.log'); // Залогируйте в третий файл
        logMessage("Inserted into DB: URL=$processedUrl, Content Length=$contentLength", 'INFO', '3.log'); // Залогируйте в третий файл

        // Подтвердите получение сообщения из очереди url_queue
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    };

    // Укажите, что обработчик должен подтверждать получение сообщений
    $channel->basic_consume('url_queue', '', false, false, false, false, $callback);

    while (count($channel->callbacks)) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    logMessage("Error: " . $e->getMessage(), 'ERROR');
}
