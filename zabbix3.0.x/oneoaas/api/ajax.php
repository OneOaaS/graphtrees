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
    'groupid'       => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'groupids'      => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'hostid'        => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'hostids'       => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'applicationid' => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'period'        => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    // ajax
    'action'        => [
        T_ZBX_STR,
        O_MAND,
        P_ACT,
        IN([
            "getGroups",
            "getHosts",
            "getApplications",
            "getItems",
            "getItemkeys",
            "getSysCount",
            "getProblemsCount"
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
//$action = strtolower($action);
switch ($action) {
    case 'getGroups':
        $condition = [
            'output'     => ['groupid', 'name'],
            'real_hosts' => true,
            'sortfield'  => ['name'],
            'sortorder'  => 'ASC'
        ];
        if ($groupid) {
            $condition['groupids'] = $groupid;
        }

        $content = API::HostGroup()->get($condition);

        $code = 200;
        $msg = "OK";
        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
        break;
    case 'getHosts':
        $condition = [
            'output'    => ['hostid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
            //'monitored_hosts'         => true,
            //'with_monitored_triggers' => true,
        ];
        if ($groupid) {
            $condition['groupids'] = $groupid;
        }

        $content = API::Host()->get($condition);

        $code = 200;
        $msg = "OK";
        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
        break;
    case 'getApplications':
        $condition = [
            'output'    => ['applicationid', 'name'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ];
        if ($hostid) {
            $condition['hostids'] = $hostid;
        }

        $content = API::Application()->get($condition);

        $code = 200;
        $msg = "OK";
        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
        break;
    case 'getItems':
        $condition = [
            'output'    => ['itemid', 'hostid', 'name', 'key_'],
            'sortfield' => 'name',
            'sortorder' => 'ASC',
        ];
        if ($hostid) {
            $condition['hostids'] = $hostid;
        } elseif ($applicationid) {
            $condition['applicationids'] = $applicationid;
        }

        $items = API::Item()->get($condition);
        $content = CMacrosResolverHelper::resolveItemNames($items);
        foreach($content as &$item) {
            $item['name'] = $item['name_expanded'];
            unset($item['hostid'], $item['key_'], $item['name_expanded']);
        }
        $code = 200;
        $msg = "OK";
        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
        break;

    case 'getItemkeys':
        $code = 200;
        $msg = "OK";
        $content = [];

        $groupids = getRequest('groupids', []);
        $hostids = getRequest('hostids', []);
        $flags = '(0,2,4)';
        if ($from && $from == 'dataview') {
            $flags = "(0,2,4)";
        } elseif ($from && $from == 'report.datagraph') {
            $flags = "(0,2,4)";
        } elseif ($from && $from == 'report.datadiff') {
            $flags = "(0,4)";
        }
        if ($hostids) {
            $hostids = "(" . implode(",", $hostids) . ")";
            $sql = "
            SELECT
              i.itemid,
              i.key_ name
            FROM
              items i
            WHERE 1 = 1
              AND i.hostid IN {$hostids}
              AND i.interfaceid IS NOT NULL
              AND i.value_type IN (0, 3)
              AND i.flags IN {$flags}
              GROUP BY i.key_
              ORDER BY i.key_
        ";
            $content = DBfetchArray(DBselect($sql));
        } elseif ($groupids) {
            $groupids = "(" . implode(",", $groupids) . ")";
            $sql = "
            SELECT
              i.itemid,
              i.key_ name
            FROM
              items i,
              hosts_groups hg
            WHERE 1 = 1
              AND hg.groupid IN {$groupids}
              AND i.hostid = hg.hostid
              AND i.interfaceid IS NOT NULL
              AND i.value_type IN (0, 3)
              AND i.flags IN {$flags}
              GROUP BY i.key_
              ORDER BY i.key_
        ";
            $content = DBfetchArray(DBselect($sql));
        } else {
            $code = 201;
            $content = null;
            $msg = "请选择主机组或者主机";
        }

        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $content]);
        break;

    case 'getProblemsCount':
        $period = getRequest('period', ONEOAAS_DAY);
        $problem_counts = get_problems_count(['period' => $period]);
        $code = 200;
        $msg = "OK";

        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => array_values($problem_counts)]);
        break;
    case 'getSysCount':
        $sysCount = getSysCount();
        $code = 200;
        $msg = "OK";

        $result[] = [
            'element' => 'c_items',
            'title'   => '监控项',
            'data'    => [
                [
                    'label' => '启用',
                    'value' => $sysCount['items_count_monitored']
                ],
                [
                    'label' => '未启用',
                    'value' => $sysCount['items_count_disabled']
                ],
                [
                    'label' => '不支持',
                    'value' => $sysCount['items_count_not_supported']
                ]
            ],
            'colors'  => ['#26B99A', 'grey', '#E74C3C']
        ];

        $result[] = [
            'element' => 'c_triggers',
            'title'   => '故障规则',
            'data'    => [
                [
                    'label' => '故障',
                    'value' => $sysCount['items_count_monitored']
                ],
                [
                    'label' => '已关闭',
                    'value' => $sysCount['items_count_disabled']
                ],
                [
                    'label' => '正常',
                    'value' => $sysCount['items_count_not_supported']
                ]
            ],
            'colors'  => ['#E74C3C', 'grey', '#26B99A']
        ];

        $result[] = [
            'element' => 'c_groups',
            'title'   => '主机组',
            'data'    => [
                [
                    'label' => '故障',
                    'value' => $sysCount['problem_groups_count']
                ],
                [
                    'label' => '正常',
                    'value' => $sysCount['with_realhosts_groups_count'] - $sysCount['problem_groups_count']
                ]
            ],
            'colors'  => ['#E74C3C', '#26B99A']
        ];

        $result[] = [
            'element' => 'c_hosts',
            'title'   => '主机',
            'data'    => [
                [
                    'label' => '故障',
                    'value' => $sysCount['problem_hosts_count']
                ],
                [
                    'label' => '正常',
                    'value' => $sysCount['with_monitored_hosts_count'] - $sysCount['problem_hosts_count']
                ]
            ],
            'colors'  => ['#E74C3C', '#26B99A']
        ];

        $result[] = [
            'element' => 'c_warnings',
            'title'   => '告警',
            'data'    => [
                [
                    'label' => '已发送',
                    'value' => $sysCount['alerts_sent_count']
                ],
                [
                    'label' => '未发送',
                    'value' => $sysCount['alerts_unsent_count']
                ],
                [
                    'label' => '发送失败',
                    'value' => $sysCount['alerts_failed_count']
                ]
            ],
            'colors'  => ['#26B99A', 'grey', '#E74C3C']
        ];

        $result[] = [
            'element' => 'c_appflows',
            'title'   => '业务流',
            'data'    => [
                [
                    'label' => '故障',
                    'value' => $sysCount['appflowsProblemCount']
                ],
                [
                    'label' => '正常',
                    'value' => $sysCount['appflowsCount'] - $sysCount['appflowsProblemCount']
                ]
            ],
            'colors'  => ['#E74C3C', '#26B99A']
        ];

        ajaxReturn(['code' => $code, 'msg' => $msg, 'content' => $result]);
        break;
}