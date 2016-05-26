<?php
require_once dirname(__FILE__) . '/include/config.inc.php';
require_once dirname(__FILE__) . '/include/hosts.inc.php';
include_once dirname(__FILE__) . '/include/items.inc.php';
require_once dirname(__FILE__) . '/include/graphs.inc.php';

$page['title'] = _('Graph trees');
$page['file'] = 'graphtree.right.php';
$page['hist_arg'] = ['hostid', 'groupid', 'graphid'];
$page['scripts'] = ['class.calendar.js', 'gtlc.js', 'flickerfreescreen.js'];
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

define("ZBX_PAGE_NO_MENU", 1);
define("ZBX_PAGE_NO_HEADER", 1);
require_once dirname(__FILE__) . '/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
    'groupid'       => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'hostid'        => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'graphid'       => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'applicationid' => [T_ZBX_INT, O_OPT, P_SYS, DB_ID, null],
    'period'        => [T_ZBX_INT, O_OPT, P_SYS, null, null],
    'stime'         => [T_ZBX_STR, O_OPT, P_SYS, null, null],
    'fullscreen'    => [T_ZBX_INT, O_OPT, P_SYS, IN('0,1'), null],
    // ajax
    'filterState'   => [T_ZBX_INT, O_OPT, P_ACT, null, null],
    'favobj'        => [T_ZBX_STR, O_OPT, P_ACT, null, null],
    'favid'         => [T_ZBX_INT, O_OPT, P_ACT, null, null],
    'favaction'     => [T_ZBX_STR, O_OPT, P_ACT, IN('"add","remove"'), null]
];
check_fields($fields);

/*
 * Filter
 */
if (hasRequest('filterState')) {
    CProfile::update('web.graphtree.filter.state', getRequest('filterState'), PROFILE_TYPE_INT);
}

/*
 * ajax update timelinefixedperiod
 */
if (isset($_REQUEST['favobj'])) {
    // saving fixed/dynamic setting to profile
    if ($_REQUEST['favobj'] == 'timelinefixedperiod' && isset($_REQUEST['favid'])) {
        CProfile::update('web.graphtree.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
    }
}

if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
    require_once dirname(__FILE__) . '/include/page_footer.php';
    exit;
}

$hostid = getRequest("hostid", -1);
$groupid = getRequest("groupid", -1);
$applicationid = getRequest("applicationid", -1);

$curtime = time(); //当前时间
$timeline = [];

if (!empty($_REQUEST['period']) || !empty($_REQUEST['stime'])) {
    $timeline = CScreenBase::calculateTime([
        'period' => getRequest('period'),
        'stime'  => getRequest('stime')
    ]);


    $screen = new CScreenBuilder();
    CScreenBuilder::insertScreenStandardJs([
        'timeline'   => $timeline,
        'profileIdx' => 'web.graphtree'
    ]);
} else {
    $timeline = [
        'period' => 3600,
        'stime'  => $curtime - 3600
    ];

    $screen = new CScreenBuilder();
    CScreenBuilder::insertScreenStandardJs([
        'timeline'   => $screen->timeline,
        'profileIdx' => "web.graphtree"
    ]);
}

$graph_list = []; //记录结果信息数
$value_list = [];
$item_list = [];
$graphids = null;
$pagingLine = null;
if ($groupid > 0 || $hostid > 0 || $applicationid > 0) {
    DBstart();
    $result = [];

    if ($groupid !== -1 && $groupid > 0) {
        $sql = "
                    SELECT DISTINCT
                      g.graphid,g.graphtype
                    FROM
                      `graphs` g,
                      `graphs_items` gi,
                      `items` i,
                      `hosts` h,
                      `hosts_groups` hg
                    WHERE i.hostid = h.`hostid`
                      AND gi.graphid = g.graphid
                      AND i.itemid = gi.itemid
                      AND g.flags IN ('0', '4')
                      AND hg.groupid = {$groupid}
                      AND hg.hostid = h.hostid
                      AND h.status = 0
                      AND EXISTS
                      (SELECT
                        NULL
                      FROM
                        `items` i
                      WHERE h.hostid = i.hostid
                        AND i.status = 0
                        AND i.flags IN (0, 4))
                    ORDER BY g.name ";

        $dbRes = DBselect($sql);
        while ($graph = DBfetch($dbRes)) {
            $result["graph_" . $graph['graphid']] = $graph;
        }
        $graph_list = $result;

    } elseif ($hostid !== -1 && $hostid > 0) {
        $sql = "
                    SELECT DISTINCT
                      g.*
                    FROM
                      `graphs` g,
                      `graphs_items` gi,
                      `items` i,
                      `hosts` h
                    WHERE i.hostid = {$hostid}
                      AND gi.graphid = g.graphid
                      AND i.itemid = gi.itemid
                      AND g.flags IN ('0', '4')
                      AND h.status = 0
                      AND EXISTS
                      (SELECT
                        NULL
                      FROM
                        `items` i
                      WHERE h.hostid = i.hostid
                        AND i.status = 0
                        AND i.flags IN (0, 4))
                    ORDER BY g.name ";

        $dbRes = DBselect($sql);
        while ($graph = DBfetch($dbRes)) {
            $result["graph_" . $graph['graphid']] = $graph;
        }
        $graph_list = $result;
    } elseif ($applicationid !== -1 && $applicationid > 0) {
        //graph list
        $sql = "
                    SELECT DISTINCT
                      g.graphid,g.graphtype
                    FROM
                      items_applications ia,
                      `graphs_items` gi,
                      `graphs` g
                    WHERE ia.applicationid = {$applicationid}
                      AND gi.`itemid` = ia.`itemid`
                      AND g.`graphid` = gi.`graphid`
                      AND g.flags IN ('0', '4')
                    ORDER BY g.name";

        $dbRes = DBselect($sql);
        while ($graph = DBfetch($dbRes)) {
            $result["graph_" . $graph['graphid']] = $graph;
        }
        $graph_list = $result;

        //no graphid
        $result = [];
        $sql = "
                    SELECT
                      i.itemid,i.name,i.value_type
                    FROM
                      `items` i,
                      `items_applications` ia
                    WHERE i.itemid = ia.itemid
                      AND ia.applicationid = {$applicationid}
                      AND i.state = 0
                      AND i.flags IN ('0', '4')
                      AND NOT EXISTS
                      (SELECT
                        NULL
                      FROM
                        `items_applications` ia,
                        `graphs_items` gi,
                        `graphs` g
                      WHERE ia.applicationid = {$applicationid}
                        AND gi.`itemid` = i.`itemid`
                        AND g.`graphid` = gi.`graphid`
                        AND g.`flags` IN ('0', '4'))
                    ";

        $dbRes = DBselect($sql);
        while ($item = DBfetch($dbRes)) {
            $result["item_" . $item['itemid']] = $item;
        }

        $item_list = $result;

        /*
         * 0 float
         * 1 character
         * 2 log
         * 3 unsign float
         * 4 text
         */
        foreach ($item_list as $k => $item) {
            switch ($item['value_type']) {
                case 1:
                case 2:
                case 4:
                    $value_list[$item['itemid']] = $item;
                    unset($item_list[$k]);
                    break;
                default:
                    break;
            }
        }

        $graph_list = zbx_array_merge($graph_list, $item_list);

        if ($value_list) {
            // get history
            if ($history = Manager::History()->getLast($value_list, 1, ZBX_HISTORY_PERIOD)) {
                foreach ($value_list as &$item) {
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
    }
    $url = new CUrl();
    $pagingLine = getPagingLine($graph_list, ZBX_SORT_UP,$url);
    //DBend(true);
}

/*
 * Display
 */
$graphWidth = 520; //图形宽度
$displayWidth = "50%";//"50%"; //显示宽度
$height = 260;

$chartsWidget = (new CWidget())->setTitle("Graphs");

$chartForm = (new CForm('get'))->addVar('fullscreen', getRequest('fullscreen'));
$chartsWidget->setControls($chartForm);

$filterForm = (new CFilter('web.graphtree.filter.state'))->addNavigator();
$chartsWidget->addItem($filterForm);
if (!is_null($pagingLine)) {
    $chartsWidget->addItem($pagingLine);
}

if (is_array($graph_list) && !empty($graph_list)) {
    $imgDiv = (new CDiv())->setId("imgDiv");
    //graphid --> graph
    foreach ($graph_list as $key => $item) {
        if (strpos($key, "graph_") === 0) {
            $chart = 'chart2.php';
            if($item['graphtype'] == 2 || $item['graphtype'] == 3){
                $chart = 'chart6.php';
            }

            $small_graph = $chart.'?graphid=' . $item['graphid'] . '&width=' . $graphWidth . '&height=' . $height . '&stime=' . $timeline['stime'] . '&period=' . $timeline['period'];
            $big_graph = 'biggraph.php?graphid=' . $item['graphid'] . '&stime=' . $timeline['stime'] . '&period=' . $timeline['period'];
            $div_tmp = (new CDiv())->setId("div_" . $item['graphid']);
        }
        if (strpos($key, "item_") === 0) {
            $small_graph = "chart.php?itemids=" . $item['itemid'] . "&width=" . $graphWidth . "&height=" . $height . "&stime=" . $timeline['stime'] . "&period=" . $timeline['period'];
            $big_graph = "history.php?action=showgraph&itemids[]=" . $item['itemid'];
            $div_tmp = (new CDiv())->setId("div_" . $item['itemid']);
        }

        $div_tmp->addStyle("box-sizing: border-box;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;width:{$displayWidth};float: left;text-align: center;padding: 6px 6px 0px 0px;");

        $img_tmp = new CImg($small_graph, null, null, $height);
        $img_tmp->setAttribute('style', "width:100%");
        $a_tmp = new CLink($img_tmp, $big_graph);

        if (strpos($key, "item_") === 0) {
            $a_tmp->setAttribute("target", "_blank");
        }

        $div_tmp->addItem($a_tmp);
        $imgDiv->addItem($div_tmp);

    }
    $chartsWidget->addItem($imgDiv);
    unset($item);

    $chartsWidget->addItem(BR());

    $chartsWidget->addItem((new CDiv($pagingLine))->addStyle("clear:both;padding:12px 0;"));

}

if ($value_list) {
    $valueTable = (new CTableInfo())->setHeader([
        _("Name"),
        _("Last check"),
        _("Last value"),
        _("Option"),
    ]);

    foreach ($value_list as $item) {
        $history_link = new CLink(new CSpan("history"), "history.php?action=showvalues&period=86400&itemids[]=" . $item['itemid']);
        $history_link->setTarget("_blank");
        $valueTable->addRow([
            new CCol(new CSpan($item['name'], "item")),
            new CCol(new CSpan(zbx_date2str(DATE_TIME_FORMAT_SECONDS, $item['clock']), "item")),
            new CCol(new CSpan(($item['value'] !== null) ? $item['value'] : _("No Value"), "item")),
            new CCol($history_link)
        ]);
        unset($history_link);
    }

    $valueTable->addStyle("clear:both");
    $chartsWidget->addItem($valueTable);
    unset($item);
}

if(empty($graph_list) && empty($value_list)){
    $chartsWidget->addItem(new CTableInfo());
}

$chartsWidget->show();

require_once dirname(__FILE__) . '/include/page_footer.php';
