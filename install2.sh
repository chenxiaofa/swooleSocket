
#redis
pecl install redis
echo "[swoole]" >> /usr/local/php/etc/php.ini
echo "extension=\"swoole.so\"" >> /usr/local/php/etc/php.ini
#echo "[redis]" >> /usr/local/php/etc/php.ini
#echo "extension=\"redis.so\"" >> /usr/local/php/etc/php.ini

# hiredis
mkdir -p /home/hiredisPack && cd /home/hiredisPack && git clone https://github.com/redis/hiredis.git && cd hiredis && make && make install && ldconfig


# swooole
mkdir -p /home/swoolePack && cd /home/swoolePack && wget https://github.com/swoole/swoole-src/archive/v2.2.0.tar.gz && tar -zxvf v2.2.0.tar.gz && cd swoole-src-2.2.0 && phpize && ./configure --enable-async-redis --with-php-config=/usr/local/php/bin/php-config && make && make install

# zhy
mkdir -p /home/swoole/zhy
cd /home/swoole/zhy && git clone -b zhy https://github.com/645614085/swooleSocket.git

cd /home/swoole/zhy/swooleSocket/ && php Tcp.php

# screen

mkdir -p /home/swoole/screen

cd /home/swoole/screen && git clone -b screen https://github.com/645614085/swooleSocket.git

cd /home/swoole/screen/swooleSocket/ && php Tcp.php
