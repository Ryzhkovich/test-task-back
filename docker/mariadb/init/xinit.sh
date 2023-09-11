#!/bin/sh

mariadb -uroot -proot -e "GRANT ALL PRIVILEGES ON web.* TO 'test'@'%' IDENTIFIED BY 'test';";
mariadb -uroot -proot -e "FLUSH PRIVILEGES";