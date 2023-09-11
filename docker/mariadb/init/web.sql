-- DROP DATABASE IF EXISTS web;
CREATE DATABASE IF NOT EXISTS web;
USE web;

DROP TABLE IF EXISTS urls;

CREATE TABLE urls
(
    id             INT AUTO_INCREMENT PRIMARY KEY,
    url            VARCHAR(255) NOT NULL,
    content_length INT          NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);