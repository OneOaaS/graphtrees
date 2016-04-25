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

require_once dirname(__FILE__) . '/include/config.inc.php';
require_once dirname(__FILE__) . '/include/hosts.inc.php';
require_once dirname(__FILE__) . '/include/graphs.inc.php';


$page['title'] = _('Graphtrees');
$page['file'] = 'graphtrees.php';
$page['hist_arg'] = ['graphid', 'groupid', 'hostid'];
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

//define('ZBX_PAGE_DO_JS_REFRESH', 1); no refresh

ob_start();
require_once dirname(__FILE__) . '/include/page_header.php';

$fields = [
    'groupid'     => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'hostid'      => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'graphid'     => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'period'      => [T_ZBX_INT, O_OPT, P_SYS, null, null],
    'stime'       => [T_ZBX_STR, O_OPT, P_SYS, null, null],
    'fullscreen'  => [T_ZBX_INT, O_OPT, P_SYS, IN('0,1'), null],
    // ajax
    'filterState' => [T_ZBX_INT, O_OPT, P_ACT, null, null],
    'favobj'      => [T_ZBX_STR, O_OPT, P_ACT, null, null],
    'favid'       => [T_ZBX_INT, O_OPT, P_ACT, null, null],
    'favaction'   => [T_ZBX_STR, O_OPT, P_ACT, IN('"add","remove"'), null]
];
check_fields($fields);
$gtreeWidget = (new CWidget())->setTitle(_('Graphtrees'))->setControls((new CList())->addItem(get_icon('fullscreen', ['fullscreen' => getRequest('fullscreen')])));


$gtree_iframe_left = new CIFrame("graphtree.left.php?type=zTree", "100%", "100%", "auto", "zatree_iframe_left");
$gtree_iframe_right = new CIFrame("graphtree.right.php", "100%", "100%", "auto", "zatree_iframe_right");
$gtree_iframe_left->setAttribute("name", "leftFrame");
$gtree_iframe_left->setAttribute("id", "leftFrame");
$gtree_iframe_right->setAttribute("name", "rightFrame");
$gtree_iframe_right->setAttribute("id", "rightFrame");
$gtree_div_left = (new CDiv($gtree_iframe_left))->setAttribute('style', "width:20%;height:800px;display:inline-block");
$gtree_div_right = (new CDiv($gtree_iframe_right))->setAttribute('style', "width:80%;height:800px;display:inline-block");

$gtreeWidget->addItem($gtree_div_left);
$gtreeWidget->addItem($gtree_div_right);

$gtreeWidget->show();

require_once dirname(__FILE__) . '/include/page_footer.php';

