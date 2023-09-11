CREATE DATABASE IF NOT EXISTS web;

USE web;

CREATE TABLE IF NOT EXISTS urls
(
    id             UUID DEFAULT generateUUIDv4(),
    url            String,
    content_length Int32,
    created_at     DateTime DEFAULT now()
    ) ENGINE = MergeTree()
    ORDER BY (created_at, id);
