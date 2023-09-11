<?php
$projectRoot = __DIR__; // Путь к корневой директории проекта
require($projectRoot . '/vendor/autoload.php');
use app\enums\UrlsEnum;
use app\services\UrlContent;

// Функция для получения случайной задержки
function randomPause($min, $max)
{
    return rand($min, $max);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start'])) {
    $urls = UrlsEnum::getUrls();
    shuffle($urls);

    // Очистим таблицу перед началом
    echo '<table id="resultTable">';
    echo '<tr><th>URL</th><th>Время</th><th>Длина контента</th></tr>';
    echo '</table>';

    // Запускаем скрипт для отправки URL-ов в очередь
    exec('php send_urls_to_queue.php ');

    // Получаем результаты из очереди process_urls_from_queue.php
    $results = [];
    $index = 0;
    while (count($results) < count($urls)) {
        // Ожидаем получения результатов
        sleep(1);

        // Запрашиваем результаты из очереди
        exec('php process_urls_from_queue.php', $output);

        foreach ($output as $line) {
            $result = json_decode($line, true);
            if ($result !== null && isset($result['index'])) {
                $results[$result['index']] = $result;
                $index = max($index, $result['index']);
            }
        }
    }

    // Выводим результаты в таблицу
    for ($i = 0; $i <= $index; $i++) {
        $url = $urls[$i];
        $executionTime = isset($results[$i]['executionTime']) ? $results[$i]['executionTime'] . ' сек.' : '-';
        $contentLength = isset($results[$i]['contentLength']) ? $results[$i]['contentLength'] . ' байт' : '-';
        echo "<script>$('#resultTable tr:nth-child(" . ($i + 2) . ")').html('<td>$url</td><td>$executionTime</td><td>$contentLength</td>');</script>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    echo '<table>';
    echo '<tr><th>URL</th><th>Время (MariaDB)</th><th>Длина контента (MariaDB)</th><th>Время (ClickHouse)</th><th>Длина контента (ClickHouse)</th></tr>';

    $pdo = new PDO('mysql:host=mariadb;dbname=web', 'test', 'test');

    /*$clickhouse = new ClickHouseDB\Client([
        'host' => 'clickhouse',
        'port' => 8123,
        'username' => 'default',
        'password' => '',
    ]);*/

    foreach (UrlsEnum::getUrls() as $url) {
        // Запрос к MariaDB

        $stmt = $pdo->prepare('SELECT created_at, content_length FROM urls WHERE url = :url');
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt->execute();
        $mariadbResult = $stmt->fetch(PDO::FETCH_ASSOC);

        // Запрос к ClickHouse
        // Предположим, что вы используете библиотеку smi2/phpclickhouse

//        $clickhouseResult = $clickhouse->select('SELECT created_at, content_length FROM urls WHERE url = :url', [':url' => $url])->rows();

        // Вывод результатов в таблицу
        $mariadbExecutionTime = isset($mariadbResult['created_at']) ? $mariadbResult['created_at'] . ' сек.' : '-';
        $mariadbContentLength = isset($mariadbResult['content_length']) ? $mariadbResult['content_length'] . ' байт' : '-';
//        $clickhouseExecutionTime = isset($clickhouseResult[0]['created_at']) ? $clickhouseResult[0]['created_at'] . ' сек.' : '-';
//        $clickhouseContentLength = isset($clickhouseResult[0]['content_length']) ? $clickhouseResult[0]['content_length'] . ' байт' : '-';
//        echo "<tr><td>$url</td><td>$mariadbExecutionTime</td><td>$mariadbContentLength</td><td>$clickhouseExecutionTime</td><td>$clickhouseContentLength</td></tr>";
        echo "<tr><td>$url</td><td>$mariadbExecutionTime</td><td>$mariadbContentLength</td></tr>";
    }

    echo '</table>';


    $query = "SELECT
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS minute,
                COUNT(*) AS row_count,
                AVG(content_length) AS avg_length,
                MIN(created_at) AS min_created_at,
                MAX(created_at) AS max_created_at
              FROM urls
              GROUP BY minute
              ORDER BY minute";

    $stmt2 = $pdo->prepare($query);
    $stmt2->execute();

    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo "Minute: {$row['minute']}\n";
        echo "Row Count: {$row['row_count']}\n";
        echo "Average Length: {$row['avg_length']}\n";
        echo "Min Created At: {$row['min_created_at']}\n";
        echo "Max Created At: {$row['max_created_at']}\n";
        echo "==============================\n";
        echo "<br>";
    }

} else {
    echo '<table>';
    echo '<tr><th>URL</th><th>Время</th><th>Длина контента</th></tr>';

    foreach (UrlsEnum::getUrls() as $url) {
        echo "<tr><td>$url</td><td>-</td><td>-</td></tr>";
    }

    echo '</table>';
}
?>
<form id="startForm" method="POST"><input type="submit" name="start" value="Начать"></form>
<form id="statusForm" method="POST"><input type="submit" name="status" value="Состояние"></form>;


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#startForm').submit(function(event) {
            event.preventDefault();
            $.ajax({
                type: 'POST',
                url: 'index.php',
                data: {
                    start: true
                },
                success: function(response) {
                    $('#startForm').hide();
                    $('#resultTable').html(response);
                }
            });
        });
    });
</script>
