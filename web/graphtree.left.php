<?php

require_once dirname(__FILE__) . '/include/config.inc.php';

$type = getRequest("type", "");
if ($type === "zTree") {
    $page['scripts'] = ["jquery.ztree.core-3.5.js", "graphtree.js"];
    $page['type'] = detect_page_type(PAGE_TYPE_HTML);
    $page['css'] = ["ztree/zTreeStyle.css"];
    define("ZBX_PAGE_NO_MENU", 1);
    define("ZBX_PAGE_NO_HEADER", 1);
    define("ZBX_PAGE_NO_THEME", 1);

    require_once dirname(__FILE__) . '/include/page_header.php';

    $left_tree = new CTag("ul", "yes");
    $left_tree->setAttribute("id", "graphtree");
    $left_tree->setAttribute("class", "ztree");

    $graphtreeJs = <<<GRAPHJS

        var zTree;
        var setting = {
            view: {
                dblClickExpand: false
            },
            async: { //异步加载请求数据
                enable: true,
                url: "graphtree.left.php",
                autoParam: ["groupid=groupid", "hostid=hostid"], //请求的参数即groupid=nodeid
                otherParam: {"timestamp": new Date().getTime()},
                type: "get"
            },
            callback: {}
        };

        jQuery(document).ready(function () {
            jQuery.fn.zTree.init(jQuery("#graphtree"), setting);
            //右键菜单
            zTree = jQuery.fn.zTree.getZTreeObj("graphtree");
        });
GRAPHJS;
    insert_js($graphtreeJs, true);
    $left_tree->show();
} else {
    $groupid = getRequest("groupid", 0);
    $hostid = getRequest("hostid", 0);

    if ($groupid > 0) {
        //根据分组id查询分组下的机器
        $hosts = API::Host()->get([
            "output"          => ["hostid", "host", "name"],
            "monitored_hosts" => true,
            "groupids"        => [$groupid],
            "sortfield"       => ["host"],
            "sortorder"       => ["ASC"]
        ]);

        $new_list = $hosts;
        foreach ($hosts as &$each_host) {
            $each_host['target'] = 'rightFrame';
            $each_host['isParent'] = "true";
            $app_count = API::Application()->get([
                'countOutput' => "1",
                'hostids'     => $each_host['hostid']
            ]);
            $each_host['name'] = $each_host['name'] . '(' . $app_count . ')';
            $each_host['url'] = 'graphtree.right.php?hostid=' . $each_host['hostid'];
        }
        unset($each_host);
        echo json_encode(array_values($hosts));
    } else {
        if ($groupid == 0) {
            if ($hostid == 0) {
                //查询所有的分组列表
                $groups = API::HostGroup()->get([
                    "output"               => "extend",
                    "monitored_hosts"      => true,
                    "with_monitored_items" => 1,
                    "sortfield"            => "name"
                ]);

                //$groups = getAvailableHostGroup();
                foreach ($groups as &$each) {
                    $each['id'] = $each['groupid'];
                    $each['isParent'] = true;
                    $each['target'] = 'rightFrame';
                    $each['url'] = 'graphtree.right.php?groupid=' . $each['groupid'];

                    //查询下面有多少机器
                    $hosts = API::Host()->get([
                        "output"          => "extend",
                        "monitored_hosts" => true,
                        "groupids"        => [$each['groupid']]
                    ]);

                    $each['name'] = $each['name'] . '(' . count($hosts) . ')';
                }
                /*$groups[] = array(
                    'groupid' => -1,
                    'name' => 'others',
                    'internal' => 0 ,
                    'flags'=> 0,
                    'id' => -1,
                    'isParent' => 1,
                    'target' => 'rightFrame',
                    'url' => 'graphtree.right.php?groupid=-1'
                );*/
                echo json_encode($groups);
            } else {
                $applications = API::Application()->get([
                    "output"    => "extend",
                    "hostids"   => [$hostid],
                    "sortfield" => ["name"],
                    "sortorder" => ["ASC"]
                ]);

                if (is_array($applications)) {
                    foreach ($applications as &$each) {
                        $each['target'] = 'rightFrame';
                        $each['url'] = 'graphtree.right.php?applicationid=' . $each['applicationid'];
                    }
                }

                echo json_encode($applications);
            }
        }
    }/*else{
            $other_items = array(
                'applicationid' => 10000,
                'name' => "other_001",
                'target' => 'rightFrame',
                'url' => 'graphtree.right.php?applicationid=10000'
            );
            echo json_encode($other_items);
        }*/
}



