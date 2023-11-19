#!/bin/sh
#CentOS7.9下使用编译安装方法安装 php扩展rdkafka
#假定所有支持软件(包括但不限于apache php等)都以yum方式安装为前提
#自行编译安装支持软件(包括但不限于apache php等)不在此脚本支持范围
if [ `whoami` != 'root' ]
then
    echo "编译需要root用户"
    exit 1
fi
#cd /tmp

#查找依赖软件是否已安装
if [ `rpm -qa | grep  php-pecl-zip | wc -l` -eq 0 ]
then
	yum -y install php-zip 
fi

#扩展配置写入php.ini
if [ `grep "extension=/usr/lib64/php/modules/zip.so" /etc/php.ini | wc -l` -eq 0 ]
 then
	echo -e "\n[rdkafka]\nextension=/usr/lib64/php/modules/zip.so" >> /etc/php.ini
fi
#重启php-fpm和webserver httpd
#kill -USR2 $(ps -aux | grep php-fpm:\ master\ process | awk '{print $2}' | head -n 1)
service php-fpm restart
service httpd restart
