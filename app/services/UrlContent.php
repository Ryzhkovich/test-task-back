<?php

namespace app\services;

use Exception;

class UrlContent
{
    /**
     * @throws Exception
     */
    public static function getContentLength($url): int
    {
        // Используем cURL для выполнения HTTP-запроса
        $ch = curl_init($url);

        // Устанавливаем параметры запроса
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // Выполняем запрос и получаем содержимое
        $content = curl_exec($ch);

        // Получаем длину контента
        $length = ($content !== false) ? strlen($content) : 0;

        // Проверяем на ошибки cURL
        if ($content === false) {
            // Получаем текущее время
            $currentTime = date('H:i:s');

            // Ошибка при выполнении запроса
            $error = curl_error($ch);

            // Закрываем соединение cURL
            curl_close($ch);

            // Определяем путь к лог-файлу
            $logPath = __DIR__ . '/../log/' . date('Y-m-d') . '.log';

            // Создаем запись для лога
            $logEntry = "$currentTime - URL: $url, Ошибка: $error\n";

            // Добавляем запись в лог-файл
            file_put_contents($logPath, $logEntry, FILE_APPEND);

            // Выбрасываем исключение с текстом ошибки
            throw new Exception("Ошибка при выполнении запроса: $error");
        }

        // Закрываем соединение cURL
        curl_close($ch);

        return $length;
    }
}

