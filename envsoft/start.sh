#!/bin/bash
#假定所有支持软件(包括但不限于apache php等)都以yum,apt或者zypper方式提前安装
#自行编译安装支持软件(包括但不限于apache php等)不在此脚本支持范围
#重启php-fpm和webserver httpd
#####kill -SIGUSR2 `cat /var/run/php-fpm.pid`
#####kill -USR2 $(ps -aux | grep php-fpm:\ master\ process | awk '{print $2}' | head -n 1)


if [ `whoami` != 'root' ]
then
    echo "编译需要root用户"
    exit 1
fi

################### 第0部分 判断Linux的发行版 ###################
pkg=""
get_release_pkg(){
	if [[ $(cat /proc/version | grep -i "Red Hat") != "" ]]
	then
		pkg="yum"
	elif [[ $(cat /proc/version | grep -i "Ubuntu" ) != "" ]] ||  [[ $(cat /proc/version | grep -i "Debian" ) != "" ]]
	then
		pkg="apt"
	elif [[ $(cat /proc/version | grep -i "SUSE") != "" ]]
	then
		pkg="zypper"
	fi
}
get_release_pkg
if [ ${pkg} == "yum" ]
then
	if [ `rpm -qa | grep httpd | wc -l` -eq 0 ] && [ `rpm -qa | grep nginx | wc -l` -eq 0 ]
	then
		echo "还未安装任何 Web Server,请先安装 Apache 或者 Nginx"
		exit 1
	elif [ `rpm -qa | grep php | wc -l` -eq 0 ]
	then
		echo "还未安装 PHP,请先安装 PHP"
		exit 1
	fi
elif [ ${pkg} == "apt" ]
then
	if [ `dpkg -l | grep apache2 | wc -l` -eq 0 ] && [ `dpkg -l | grep nginx | wc -l` -eq 0 ]
	then
		echo "还未安装任何 Web Server,请先安装 Apache 或者 Nginx"
		exit 1
	elif [ `dpkg -l | grep php | wc -l` -eq 0 ]
	then
		echo "还未安装 PHP,请先安装 PHP"
		exit 1
	fi
elif [ ${pkg} == "zypper" ]
then
	if [ `rpm -qa | grep apache2 | wc -l` -eq 0 ] && [ `rpm -qa | grep nginx | wc -l` -eq 0 ]
	then
		echo "还未安装任何 Web Server,请先安装 Apache 或者 Nginx"
		exit 1
	elif [ `rpm -qa | grep php | wc -l` -eq 0 ]
	then
		echo "还未安装 PHP,请先安装 PHP"
		exit 1
	fi
else
	echo "未能识别该Linux发行版"
	exit 1
fi

################### 第一部分 安装基础软件 ###################
if [ ${pkg} == "yum" ]
then
	if [ `rpm -qa | grep epel-release | wc -l` -eq 0 ]
	then
		yum -y install epel-release
	fi
	if [ `rpm -qa | grep re2c | wc -l` -eq 0 ]
	then
		yum -y install re2c
	fi
	if [ `rpm -qa | grep unzip | wc -l` -eq 0 ]
	then
		yum -y install unzip
	fi
	if [ `rpm -qa | grep ^make | wc -l` -eq 0 ]
	then
		yum -y install make
	fi
	if [ `rpm -qa | grep gcc-c++ | wc -l` -eq 0 ]
	then
		yum -y install gcc-c++
	fi
	if [ `rpm -qa | grep php-devel | wc -l` -eq 0 ]
	then
		yum -y install php-devel
	fi
	if [ `rpm -qa | grep  php-pecl-zip | wc -l` -eq 0 ]
	then
		yum -y install php-zip 
	fi
	if [ `rpm -qa | grep  java | wc -l` -eq 0 ]
	then
		yum -y install java
	fi
	if [ `rpm -qa | grep  java-devel | wc -l` -eq 0 ]
	then
		yum -y install java-devel
	fi
elif [ ${pkg} == "apt" ]
then
	apt update
	if [ `dpkg -l | grep re2c | wc -l` -eq 0 ]
	then
		apt -y install re2c
	fi
	if [ `dpkg -l | grep unzip | wc -l` -eq 0 ]
	then
		apt -y install unzip
	fi
	if [ `dpkg -l | grep "ii  make" | wc -l` -eq 0 ]
	then
		apt -y install make
	fi
	if [ `dpkg -l | grep g++ | wc -l` -eq 0 ]
	then
		apt -y install g++
	fi
	if [ `dpkg -l | grep php-dev | wc -l` -eq 0 ]
	then
		apt -y install php-dev
	fi
	if [ `dpkg -l | grep  php-pecl-zip | wc -l` -eq 0 ]
	then
		apt -y install php-zip 
	fi
	if [ `dpkg -l | grep  default-jdk | wc -l` -eq 0 ]
	then
		apt -y install default-jdk
	fi
elif [ ${pkg} == "zypper" ]
then
	if [ `rpm -qa | grep re2c | wc -l` -eq 0 ]
	then
		zypper -n install re2c
	fi
	if [ `rpm -qa | grep unzip | wc -l` -eq 0 ]
	then
		zypper -n install unzip
	fi
	if [ `rpm -qa | grep ^make | wc -l` -eq 0 ]
	then
		zypper -n install make
	fi
	if [ `rpm -qa | grep gcc-c++ | wc -l` -eq 0 ]
	then
		zypper -n install gcc-c++
	fi
	if [ `rpm -qa | grep php-devel | wc -l` -eq 0 ]
	then
		zypper -n install php-devel
	fi
	if [ `rpm -qa | grep  php-pecl-zip | wc -l` -eq 0 ]
	then
		zypper -n install php-zip
	fi
	if [ `rpm -qa | grep  java | wc -l` -eq 0 ]
	then
		zypper -n install java
	fi
	if [ `rpm -qa | grep  java-devel | wc -l` -eq 0 ]
	then
		zypper -n install java-devel
	fi
fi
	
################### 第二部分 编译librdkafka基础库和php扩展rdkafka ###################
#判断已经是否已经编译安装过librdkafka
#当前执行脚本的绝对路径
currdir=$(cd $(dirname $0); pwd)
echo "/usr/local/lib" >>/etc/ld.so.conf
ldconfig
if [ `ldconfig -p | grep librdkafka | wc -l` -eq 0 ]
#编译rdkafka
then
	if [ ! -f  /tmp/librdkafka-master.zip ]
	then
		cp ${currdir}/librdkafka-master.zip /tmp/librdkafka-master.zip
	fi
	#删除旧文件夹
	if [ -d /tmp/librdkafka-master ]
	then
	rm -rf /tmp/librdkafka-master
	fi
	unzip -d /tmp /tmp/librdkafka-master.zip
    	cd /tmp/librdkafka-master
	./configure
	make && make install
	ldconfig
fi

#编译rdkafka TODO判断是否已经编译安装 rdkafka
if [ -d '/tmp/rdkafka' ] || [ -f '/tmp/rdkafka']
then
	rm -rf /tmp/rdkafka
fi
mkdir /tmp/rdkafka

if [ ! -f  /tmp/rdkafka-6.0.3.tgz ]
then
	cp ${currdir}/rdkafka-6.0.3.tgz /tmp/rdkafka-6.0.3.tgz
fi

if [ -d /tmp/rdkafka ]
then
	tar -zxvf  /tmp/rdkafka-6.0.3.tgz -C /tmp/rdkafka
else
	echo "/tmp/rdkafka文件夹不存在"
	exit 1
fi

cd /tmp/rdkafka/rdkafka-6.0.3
phpize=`which phpize`
${phpize}
with_php_config=`which php-config`
./configure --with-php-config=${with_php_config} 
make && make install

################### 第三部分 写PHP配置文件 php.ini并重启 webserver和php-fpm ###################
if [ ${pkg} == "yum" ]
then
	if [ `grep "extension=/usr/lib64/php/modules/rdkafka.so" /etc/php.ini | wc -l` -eq 0 ]
	then
		echo -e "\n[rdkafka]\nextension=/usr/lib64/php/modules/rdkafka.so" >> /etc/php.ini
	fi
	if [ `ps -aux | grep php-fp[m] | wc -l` -gt 0 ]
	then
		service php-fpm restart
	fi
	if [ `ps -aux | grep /http[d] | wc -l` -gt 0 ]
	then
		service httpd restart
	fi
elif [ ${pkg} == "apt" ]
then
	#获取php版本
	php_version=`php -r 'echo PHP_VERSION;' |  grep -o '^[[:digit:]].[[:digit:]]'`
	#获取php扩展路径
	php_extension_dir=`php-config --extension-dir`
	#写入apache2 / cli / fpm 三种类型的php.ini
	#TODO 循环写入
	if [ -f /etc/php/${php_version}/apache2/php.ini ]
	then
		if [ `grep "extension=${php_extension_dir}/rdkafka.so" /etc/php/${php_version}/apache2/php.ini | wc -l` -eq 0 ]
		then
			echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php/${php_version}/apache2/php.ini
		fi
	fi
	if [ -f /etc/php/${php_version}/cli/php.ini ]
	then
		if [ `grep "extension=${php_extension_dir}/rdkafka.so" /etc/php/${php_version}/cli/php.ini | wc -l` -eq 0 ]
		then
			echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php/${php_version}/cli/php.ini
		fi
	fi
	if [ -f /etc/php/${php_version}/fpm/php.ini ]
	then
		if [ `grep "extension=${php_extension_dir}/rdkafka.so" /etc/php/${php_version}/fpm/php.ini | wc -l` -eq 0 ]
		then
			echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php/${php_version}/fpm/php.ini
		fi
	fi
	#重启php-fpm和webserver httpd
	if service --status-all | grep php${php_version}-fpm
	then
		service php${php_version}-fpm restart
	fi
	if service --status-all | grep 'apache2'
	then
		service apache2 restart
	fi
elif [ ${pkg} == "zypper" ]
then
	#获取php大版本号
	php_big_version=`php -r 'echo PHP_VERSION;' |  grep -o '^[[:digit:]]'`
	#获取php扩展路径
	php_extension_dir=`php-config --extension-dir`
	if [ -f /etc/php${php_big_version}/apache2/php.ini ]
	then
		if [ `grep "extension=${php_extension_dir}/rdkafka.so" /etc/php${php_big_version}/apache2/php.ini | wc -l` -eq 0 ]
		then
			echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php${php_big_version}/apache2/php.ini
		fi
	fi
	if [ -f /etc/php${php_big_version}/cli/php.ini ]
	then
		if [ `grep "extension=${php_extension_dir}/rdkafka.so" /etc/php${php_big_version}/cli/php.ini | wc -l` -eq 0 ]
		then
			echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php${php_big_version}/cli/php.ini
		fi
	fi
	if [ -f /etc/php${php_big_version}/fpm/php.ini ]
	then
		if [ `grep "extension=${php_extension_dir}/rdkafka.so" /etc/php${php_big_version}/fpm/php.ini | wc -l` -eq 0 ]
		then
			echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php${php_big_version}/fpm/php.ini
		fi
	elif [ -d /etc/php${php_big_version}/conf.d/ ]
	then
		echo -e "\n[rdkafka]\nextension=${php_extension_dir}/rdkafka.so" >> /etc/php${php_big_version}/conf.d/rdkafka.ini
	else
		echo "rdkafka扩展配置写入失败"
		exit 20
	fi
	#重启php-fpm和webserver httpd
	if service --status-all | grep php-fpm
	then
		service php-fpm restart
	fi
	if service --status-all | grep 'apache2'
	then
		service apache2 restart
	fi
fi


################### 第四部分 启动zookeeper ###################
cd ${currdir}
netstat="netstat"
if [ ${pkg} == "zypper" ]
then
	netstat="ss"
fi
./kafka_2.12-2.6.0/bin/zookeeper-server-start.sh  -daemon ./kafka_2.12-2.6.0/config/zookeeper.properties&
##TODO 使用exec调用shell脚本来收集执行结果
sleep 5
################### 第五部分 启动kafka     ###################
if [ `${netstat} -tnlp | grep  ":2181 " | wc -l` -eq 0 ]
then
#echo '请先启动zookeeper'
#如果zookeeper启动失败 则使用普通模式再启动一次以便输出启动日志
./kafka_2.12-2.6.0/bin/zookeeper-server-start.sh ./kafka_2.12-2.6.0/config/zookeeper.properties&
exit 2
fi

./kafka_2.12-2.6.0/bin/kafka-server-start.sh -daemon ./kafka_2.12-2.6.0/config/server.properties&

sleep 5
################### 第六部分 启动spark     ###################
if [ `${netstat} -tnlp | grep  ":9092 " | wc -l` -eq 0 ]
then
#echo '请先启动kafka'
#如果kafka启动失败 则使用普通模式再启动一次以便输出启动日志
./kafka_2.12-2.6.0/bin/kafka-server-start.sh ./kafka_2.12-2.6.0/config/server.properties&
exit 3
fi

##配置密钥并追加公钥以便 Spark 免密启动
if [ ! -f ~/.ssh/id_rsa.spark.lionu ]
then
	ssh-keygen -t rsa -f ~/.ssh/id_rsa.spark.lionu -q -N ''
	if [ -f ~/.ssh/id_rsa.spark.lionu.pub ]
	then 
		cat ~/.ssh/id_rsa.spark.lionu.pub >> ~/.ssh/authorized_keys
		##追加config配置文件 如果config文件不存在 会自动新建
		echo -e "\nHost localhost\nIdentityFile ~/.ssh/id_rsa.spark.lionu\nUser root\n"	>> ~/.ssh/config
	else
		echo '公钥文件不存在'
		exit 11
	fi
else
	echo '密钥文件已存在'
	exit 10
fi

./spark-2.4.7-bin-hadoop2.7/sbin/start-all.sh
#提交作业
#./bin/spark-submit --master spark://127.0.0.1:7077 --class sparkSteamReConsitution ../lionu-stream.jar


