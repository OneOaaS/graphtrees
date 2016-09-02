<?php
/**
 * Created by PhpStorm.
 * User: Zhanhao
 * Date: 2016/4/18
 * Time: 15:50
 */

require_once dirname(dirname(dirname(__FILE__))) . '/include/config.inc.php';
require_once dirname(dirname(__FILE__)) . '/inc/func.inc.php';

$userData = CWebUser::$data;
if (CWebUser::isGuest()) {
    $msg = '没有权限';
    $code = 1001;
    $content = null;
    ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
}

$fields = [
    'groupid'        => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'groupids'       => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'hostid'         => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'hostids'        => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'applicationid'  => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'applicationids' => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'period'         => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'stime'          => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'starttime'      => [T_ZBX_STR, O_OPT, P_SYS, null, null],
    'endtime'        => [T_ZBX_STR, O_OPT, P_SYS, null, null],
    'search'         => [T_ZBX_STR, O_OPT, P_SYS, null, null],
    'from'           => [T_ZBX_STR, O_OPT, P_SYS, null, null],
    // ajax
    'action'         => [
        T_ZBX_STR,
        O_MAND,
        P_ACT,
        IN([
            '"getGraphs"',
            '"graphs.get"',
            '"getZtree"'
        ]),
        null
    ],
];
$check = checkFields($fields);

if (!$check) {
    $msg = '参数错误';
    $code = 2001;
    $content = null;
    ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
}

$action = getRequest('action', '');
$groupid = getRequest('groupid', 0);
$hostid = getRequest('hostid', 0);
$applicationid = getRequest('applicationid', 0);

if (!$action) {
    $msg = '请求方法不能为空';
    $code = 3001;
    $content = null;
    ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
}

switch ($action) {
    case 'getZtree':
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
            foreach($hosts as &$each_host) {
                $each_host['isParent'] = "true";
                $app_count = API::Application()->get([
                    'countOutput' => "1",
                    'hostids'     => $each_host['hostid']
                ]);
                $each_host['name'] = $each_host['name'] . '(' . $app_count . ')';
                $each_host['url'] = 'javascript:getGraphs("host",' . $each_host['hostid'] . ')';
            }
            unset($each_host);
            echo json_encode(array_values($hosts));
        } elseif ($groupid == 0) {
            if ($hostid == 0) {
                //查询所有的分组列表
                $groups = API::HostGroup()->get([
                    "output"               => "extend",
                    "monitored_hosts"      => true,
                    "with_monitored_items" => 1,
                    "sortfield"            => "name"
                ]);

                foreach($groups as &$each) {
                    $each['id'] = $each['groupid'];
                    $each['isParent'] = true;
                    $each['url'] = 'javascript:getGraphs("group",' . $each['groupid'] . ')';
                    //查询下面有多少机器
                    $host_count = API::Host()->get([
                        "countOutput"     => "1",
                        "monitored_hosts" => true,
                        "groupids"        => [$each['groupid']]
                    ]);

                    $each['name'] = $each['name'] . '(' . $host_count . ')';
                }
                echo json_encode($groups);
            } else {
                $applications = API::Application()->get([
                    "output"    => ['name'],
                    "hostids"   => [$hostid],
                    "sortfield" => ["name"],
                    "sortorder" => ["ASC"]
                ]);

                if (is_array($applications)) {
                    foreach($applications as &$each) {
                        $each['nocheck'] = true;
                        $each['url'] = 'javascript:getGraphs("application",' . $each['applicationid'] . ')';
                    }
                }

                echo json_encode($applications);
            }
        }
        break;
    case "graphs.get":
        $groupids = getRequest('groupids', []);
        $hostids = getRequest('hostids', []);
        $applicationids = getRequest('applicationids', []);
        $search = getRequest('search', '');
        $from = getRequest('from', '');

        $graphs = $group_graphs = $host_graphs = $application_graphs = $item_list = $value_list = [];
        if ($groupids) {
            $group_graphs = API::Graph()->get([
                'output'    => ['name', 'graphtype'],
                'templated' => false,
                'groupids'  => $groupids,
                'search'    => [
                    'name' => $search
                ]
            ]);
        }

        if ($hostids) {
            $host_graphs = API::Graph()->get([
                'output'    => ['name', 'graphtype'],
                'templated' => false,
                'hostids'   => $hostids,
                'search'    => [
                    'name' => $search
                ]
            ]);
        }

        if ($applicationids) {
            $applicationid = $applicationids[0];
            $sql = "
                    SELECT DISTINCT
                      g.graphid,g.graphtype
                    FROM
                      items_applications ia,
                      graphs_items gi,
                      graphs g
                    WHERE ia.applicationid = {$applicationid}
                      AND gi.itemid = ia.itemid
                      AND g.graphid = gi.graphid
                      AND g.flags IN ('0', '4')
                    ORDER BY g.name";

            $application_graphs = DBfetchArray(DBselect($sql));
        }
        $graphs = array_merge($group_graphs, $host_graphs, $application_graphs);

        if (!$groupids && !$hostids && !$applicationids) {
            $graphs = API::Graph()->get([
                'output'    => ['name', 'graphtype'],
                'templated' => false,
                'search'    => [
                    'name' => $search
                ]
            ]);
        }

        $rowsPerPage = CWebUser::$data['rows_per_page'];
        $itemsCount = count($graphs);

        $rowsPerPage = CWebUser::$data['rows_per_page'];
        $pagesCount = ($itemsCount > 0) ? ceil($itemsCount / $rowsPerPage) : 1;
        $currentPage = getRequest('page',1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }elseif ($currentPage > $pagesCount) {
            $currentPage = $pagesCount;
        }
        $start = ($currentPage - 1) * $rowsPerPage;
        $graphs = array_slice($graphs, $start, $rowsPerPage, true);

        if ($from === "sidebar" && $applicationids) {
            $applicationid = $applicationids[0];
            $sql = "
                    SELECT
                      i.itemid,i.name,i.value_type,i.key_
                    FROM
                      items i,
                      items_applications ia
                    WHERE i.itemid = ia.itemid
                      AND ia.applicationid = {$applicationid}
                      AND i.state = 0
                      AND i.flags IN ('0', '4')
                      AND NOT EXISTS
                      (SELECT
                        NULL
                      FROM
                        items_applications ia,
                        graphs_items gi,
                        graphs g
                      WHERE ia.applicationid = {$applicationid}
                        AND gi.itemid = i.itemid
                        AND g.graphid = gi.graphid
                        AND g.flags IN ('0', '4'))
                    ";
            $item_list = DBfetchArray(DBselect($sql));
            $item_list = CMacrosResolverHelper::resolveItemNames($item_list);

            /*
             * 0 float
             * 1 character
             * 2 log
             * 3 unsign float
             * 4 text
             */
            foreach($item_list as $k => $item) {
                switch ($item['value_type']) {
                    case 1:
                    case 2:
                    case 4:
                        $item['name'] = $item['name_expanded'];
                        $value_list[$item['itemid']] = $item;
                        unset($item_list[$k]);
                        break;
                    default:
                        break;
                }
            }

            if ($value_list) {
                // get history
                if ($history = Manager::History()->getLast($value_list, 1, ZBX_HISTORY_PERIOD)) {
                    foreach($value_list as &$item) {
                        if (isset($history[$item['itemid']])) {
                            $item = zbx_array_merge($item, $history[$item['itemid']][0]);
                        } else {
                            $item = zbx_array_merge($item, ['clock' => null, 'value' => null, 'ns' => null]);
                        }
                    }
                } else {
                    $value_list = [];
                }
                unset($item);
            }

            $item_list = array_values($item_list);
            $value_list = array_values($value_list);
        }

        ajaxReturn([
            'code'    => 200,
            'msg'     => '',
            'content' => [
                'total'       => $itemsCount,
                'itemsCount'  => count($item_list),
                'itemsOnPage' => $rowsPerPage,
                'graphs'      => $graphs,
                'items'       => $item_list,
                'values'      => $value_list,
            ]
        ]);

        break;
}