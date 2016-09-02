<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

require dirname(__FILE__) . '/inc/smarty.inc.php';
require dirname(__FILE__) . '/inc/func.inc.php';

$page['title'] = '图形展示';
$page['css'] = [
    $css['oneoaas'],
    $css['ztree'],
    $css['datetimepicker'],
    $css['pagination'],
];

$pageType = detect_page_type();

$fields = [
    'hostids'   => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'groupids'  => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'itemids'   => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'starttime' => [T_ZBX_INT, O_OPT, P_SYS, null, null],
    'endtime'   => [T_ZBX_STR, O_OPT, P_SYS, null, null],
];

$check = checkFields($fields);

if (!$check) {
    redirect('/error.php');
}

$filter = [
    'starttime' => getRequest("starttime", ''),
    'endtime'   => getRequest("endtime", ''),
];

if (!empty($filter['starttime'])) {
    $starttime = strtotime($filter['starttime']);
} elseif (!empty($filter['endtime'])) {
    $starttime = strtotime($filter['endtime']) - 3600;
} else {
    $starttime = time() - 3600;
}

if (!empty($filter['endtime'])) {
    $endtime = strtotime($filter['endtime']);
} elseif (!empty($filter['starttime'])) {
    $endtime = strtotime($filter['starttime']) + 3600 <= time() ? strtotime($filter['starttime']) + 3600 : time();
} else {
    $endtime = time();
}

$timeline = [
    'starttime' => date('Y-m-d H:i', $starttime),
    'endtime'   => date('Y-m-d H:i', $endtime),
    'stime'     => $starttime,
    'etime'     => $endtime,
    'period'    => $endtime - $starttime
];

$smarty->assign("page", $page);
$smarty->assign("timeline", $timeline);
$smarty->display('graphtree/graphtree.tpl');