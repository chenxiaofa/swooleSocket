#! /bin/bash
PATH=$PATH:/bin/sh/:/bin/bash

yum install -y gcc gcc-c++ glibc make autoconf openssl openssl-devel

yum install -y libxslt-devel -y gd gd-devel GeoIP GeoIP-devel pcre pcre-devel

yum install -y wget git

if [! -f "/home/lnmp1.5-full.tar.gz"]

cd /home/ && wget http://202.115.33.13/soft/lnmp/lnmp1.5-full.tar.gz

fi

cd /home/ && tar -zxvf lnmp1.5-full.tar.gz && cd lnmp1.5-full

LNMP_Auto="y" DBSelect="3" DB_Root_Password="lnmp.org" InstallInnodb="y" PHPSelect="7" SelectMalloc="1" ./install.sh lnmp



