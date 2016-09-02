# graphtrees
###功能
```
一、集中展示所有分组设备
二、集中展示一个分组图像
三、集中展示一个设备图像
四、展示设备下的Application
五、展示每个Application下的图像
六、展示每个Application下的日志
七、对原生无图的监控项进行绘图
注意问题:
    在组和主机级别，默认只显示系统配置的graph
    点击application后，会显示3种数据：
    1. 系统默认有graph的；
    2. 系统默认无graph的；
    3. 日志类的
```


```
Function:
Display all monitor things in one page.
1.All group graph 
2.One Application graph 
3.One group graph
4.One host graph
5.All Application one host
6.All Application log text 
7.draw graph if no exist graph in host if item type is Numeric 
Note:
   click group and host,display graph in host graph
   click Application,dispaly 3 types:
   1.default graph
   2.Draw graph for not exist graph(item type is Numeric)
   4.item tpye is Character\Log\Text
```
##zabbix version  3.0.4
###安装
```

#How to Install:

cd /usr/share/zabbix
ZBXVERSION=3.0.4
#Update it sometimes.
wget https://raw.githubusercontent.com/OneOaaS/graphtrees/master/graphtree${ZBXVERSION}.patch
yum install -y patch
patch  -Np0 <graphtree${ZBXVERSION}.patch
```

#screenshot
http://t.cn/RqAeAxT
