#!/bin/sh
#CentOS7.9使用pecl工具安装php扩展rdkafka
#使用此安装方法php版本需 >=7.0.0 但CentOS7.9 yum方式安装的php版本默认为5.4 所以建议在CentOS8.0及以后版本尝试此方式
#假定所有支持软件(包括但不限于apache php等)都以yum方式安装为前提
#自行编译安装支持软件(包括但不限于apache php等)不在此脚本支持范围
if [ `whoami` != 'root' ]
then
    echo "编译需要root用户"
    exit 1
fi

#查找依赖软件是否已安装
if [ `rpm -qa | grep epel-release | wc -l` -eq 0 ]
then
	yum -y install epel-release
fi
if [ `rpm -qa | grep php-pear | wc -l` -eq 0 ]
then
	yum -y install php-pear
fi
#if [ `rpm -qa | grep librdkafka | wc -l` -eq 0 ]
#then
#	yum install librdkafka
#fi
if [ `rpm -qa | grep make | wc -l` -eq 0 ]
then
	yum -y install make
fi
if [ `rpm -qa | grep ^gcc | wc -l` -eq 0 ]
then
	yum -y install gcc
fi
if [ `rpm -qa | grep ^gcc-c++ | wc -l` -eq 0 ]
then
	yum -y install gcc-c++
fi
if [ `rpm -qa | grep ^php-devel | wc -l` -eq 0 ]
then
	yum -y install php-devel
fi
unzip librdkafka.zip
cd ./librdkafka-master
./configure

make && make install
ldconfig

pecl install rdkafka
#扩展配置写入php.ini
if [ `grep "extension=rdkafka.so" /etc/php.ini | wc -l` -eq 0 ]
 then
	echo -e "\n[rdkafka]\nextension=rdkafka.so" >> /etc/php.ini
fi
#重启php-fpm和webserver httpd
#kill -SIGUSR2 `cat /var/run/php-fpm.pid`
service httpd restart
