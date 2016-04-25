# graphtrees
功能
```
一、集中展示所有分组设备
二、集中展示一个分组图像
三、集中展示一个设备图像
四、展示设备下的Application
五、展示每个Application下的图像
六、展示每个Application下的日志
七，对原生无图的监控项进行绘图
注意问题:
    在组和主机级别，默认只显示系统配置的graph。
    点击application后，会显示3种数据，1.系统默认有graph的；2.系统默认无graph的；3.日志类的
```
zabbix版本要求 3.0.1
#安装
```
cd 您的Zabbix-WEB目录
wget https://raw.githubusercontent.com/OneOaaS/graphtrees/master/graphtree3-0-1.patch
yum install -y patch
patch  -Np0 <graphtree3-0-1.patch

或者下载web下的所有文件替换您的Zabbbix-WEB目录下的所有文件，修改配置即可
```
#官方RPM包安装的如下
```
mkdir  /tmp/zbx
mv  /usr/share/zabbix /usr/share/zabbix-old
wget http://sourceforge.net/projects/zabbix/files/ZABBIX%20Latest%20Stable/3.0.1/zabbix-3.0.1.tar.gz
tar xf zabbix-3.0.1.tar.gz
cd zabbix-3.0.1/frontends/php
wget https://raw.githubusercontent.com/OneOaaS/graphtrees/master/graphtree3-0-1.patch
yum install -y patch
patch  -Np0 <graphtree3-0-1.patch
cp -r /tmp/zbx/zabbix-3.0.1/frontends/php /usr/share/zabbix
cp /usr/share/zabbix-old/conf/zabbix.conf.php /usr/share/zabbix/conf/

```

#实现效果
http://t.cn/RqAeAxT
