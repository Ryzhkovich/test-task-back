#!/bin/sh

mariadb -uroot -proot -e "CREATE DATABASE IF NOT EXISTS urls COLLATE utf8_general_ci;"
mariadb -uroot -proot -e "GRANT ALL PRIVILEGES ON \`test%\`.* TO 'test'@'%'";
mariadb -uroot -proot -e "FLUSH PRIVILEGES";