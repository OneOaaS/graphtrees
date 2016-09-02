<?php
/**
 * Created by PhpStorm.
 * User: Zhanhao
 * Date: 2016/4/18
 * Time: 16:07
 */

/**
 * Ajax方式返回数据到客户端
 * @access protected
 * @param mixed $data 要返回的数据
 * @param String $type AJAX返回数据格式
 * @param int $json_option 传递给json_encode的option参数
 * @return void
 */
function ajaxReturn($data, $type = 'JSON', $json_option = 0) {
    switch (strtoupper($type)) {
        case 'JSON':
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($data, $json_option));
        case 'XML':
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            //exit(xml_encode($data));
            break;
        case 'JSONP':
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            //$handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
            //exit($handler . '(' . json_encode($data, $json_option) . ');');
            break;
        case 'EVAL':
            // 返回可执行的js脚本
            header('Content-Type:text/html; charset=utf-8');
            exit($data);
    }
}


function checkFields(&$fields) {
    // VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
    $system_fields = [
        'sid'           => [T_ZBX_STR, O_OPT, P_SYS, HEX(), null],
        'triggers_hash' => [T_ZBX_STR, O_OPT, P_SYS, NOT_EMPTY, null],
        'print'         => [T_ZBX_INT, O_OPT, P_SYS, IN('1'), null],
        'page'          => [T_ZBX_INT, O_OPT, P_SYS, null, null], // paging
        'ddreset'       => [T_ZBX_INT, O_OPT, P_SYS, null, null]
    ];
    $fields = zbx_array_merge($system_fields, $fields);

    $err = ZBX_VALID_OK;
    foreach($fields as $field => $checks) {
        $err |= checkField($fields, $field, $checks);
    }

    unset_not_in_list($fields);
    unset_if_zero($fields);

    if ($err != ZBX_VALID_OK) {
        unset_action_vars($fields);
    }

    $fields = null;

    return ($err == ZBX_VALID_OK);
}

function checkField(&$fields, &$field, $checks) {
    if(!isset($checks[5])) {
        $checks[5] = $field;
    }
    list($type, $opt, $flags, $validation, $exception, $caption) = $checks;

    if($flags & P_UNSET_EMPTY && isset($_REQUEST[$field]) && $_REQUEST[$field] == '') {
        unset_request($field);
    }
    $except = !is_null($exception) ? calc_exp($fields, $field, $exception) : false;
    if($except) {
        if($opt == O_MAND) {
            $opt = O_NO;
        }
        elseif($opt == O_OPT) {
            $opt = O_MAND;
        }
        elseif($opt == O_NO) {
            $opt = O_MAND;
        }
    }
    if($opt == O_MAND) {
        if(!isset($_REQUEST[$field])) {
            info(_s('Field "%1$s" is mandatory.', $caption));

            return ($flags & P_SYS) ? ZBX_VALID_ERROR : ZBX_VALID_WARNING;
        }
    }
    elseif($opt == O_NO) {
        if(!isset($_REQUEST[$field])) {
            return ZBX_VALID_OK;
        }

        unset_request($field);

        info(_s('Field "%1$s" must be missing.', $caption));

        return ($flags & P_SYS) ? ZBX_VALID_ERROR : ZBX_VALID_WARNING;
    }
    elseif($opt == O_OPT) {
        if(!isset($_REQUEST[$field])) {
            return ZBX_VALID_OK;
        }
        elseif($flags & P_ACT) {
            if(!isset($_REQUEST['sid']) || (isset($_COOKIE['zbx_sessionid']) && $_REQUEST['sid'] != substr($_COOKIE['zbx_sessionid'], 16, 16))) {
                return ZBX_VALID_ERROR;
            }
        }
    }

    if(!($flags & P_NO_TRIM)) {
        check_trim($_REQUEST[$field]);
    }

    $err = check_type($field, $flags, $_REQUEST[$field], $type, $caption);
    if($err != ZBX_VALID_OK) {
        return $err;
    }

    if((is_null($exception) || $except) && $validation && !calc_exp($fields, $field, $validation)) {
        if($validation == NOT_EMPTY) {
            info(_s('Incorrect value for field "%1$s": cannot be empty.', $caption));
        }

        // check for BETWEEN() function pattern and extract numbers e.g. ({}>=0&&{}<=999)&&
        elseif(preg_match('/\(\{\}\>=([0-9]*)\&\&\{\}\<=([0-9]*)\)\&\&/', $validation, $result)) {
            info(_s('Incorrect value "%1$s" for "%2$s" field: must be between %3$s and %4$s.', $_REQUEST[$field], $caption, $result[1], $result[2]));
        }
        else {
            info(_s('Incorrect value "%1$s" for "%2$s" field.', $_REQUEST[$field], $caption));
        }

        return ($flags & P_SYS) ? ZBX_VALID_ERROR : ZBX_VALID_WARNING;
    }

    return ZBX_VALID_OK;
}


//oos = oneoaas
function oos_getPagingLine(&$items, $sortorder){
    $rowsPerPage = CWebUser::$data['rows_per_page'];
    $itemsCount = count($items);

    $pagesCount = ($itemsCount > 0) ? ceil($itemsCount / $rowsPerPage) : 1;
    $currentPage = (int)getRequest('page',1);

    if($currentPage < 1) {
        $currentPage = 1;
    }
    elseif($currentPage > $pagesCount) {
        $currentPage = $pagesCount;
    }

    $start = ($currentPage - 1) * $rowsPerPage;
    $url = CUrlFactory::getContextUrl();
    $ul = new CTag('ul',true);
    if($pagesCount > 1) {
        // viewed pages (better to use not odd)
        $pagingNavRange = 11;

        $endPage = $currentPage + floor($pagingNavRange / 2);
        if($endPage < $pagingNavRange) {
            $endPage = $pagingNavRange;
        }
        if($endPage > $pagesCount) {
            $endPage = $pagesCount;
        }

        $startPage = ($endPage > $pagingNavRange) ? $endPage - $pagingNavRange + 1 : 1;

        if($startPage > 1) {
            $url->setArgument('page', 1);
            $tags[] = (new CTag('li',true))->addItem(new CLink(_('首页'), $url->getUrl()));
        }

        if($currentPage > 1) {
            $url->setArgument('page', $currentPage - 1);
            $tags[] = (new CTag('li',true))->addItem(new CLink((new CSpan('&laquo;')), $url->getUrl()));
        }

        for($p = $startPage; $p <= $endPage; $p++) {
            $url->setArgument('page', $p);
            $link = (new CTag('li',true))->addItem(new CLink($p, $url->getUrl()));
            if($p == $currentPage) {
                $link->addClass('active');
            }

            $tags[] = $link;
        }

        if($currentPage < $pagesCount) {
            $url->setArgument('page', $currentPage + 1);
            $tags[] = (new CTag('li',true))->addItem(new CLink((new CSpan('&raquo;')), $url->getUrl()));
        }

        if($p < $pagesCount) {
            $url->setArgument('page', $pagesCount);
            $tags[] = (new CTag('li',true))->addItem(new CLink(_('末页'), $url->getUrl()));
        }
    }

    if($pagesCount == 1) {
        /*$url->setArgument('page', 1);
        $tags[] = (new CTag('li',true))->addItem(new CLink(_('First'), $url->getUrl()));
        $tags[] = (new CTag('li',true))->addClass('active')->addItem(new CLink(_('&laquo;'), $url->getUrl()));
        $tags[] = (new CTag('li',true))->addItem(new CLink(_('1'), $url->getUrl()));
        $tags[] = (new CTag('li',true))->addItem(new CLink(_('&raquo;'), $url->getUrl()));
        $tags[] = (new CTag('li',true))->addItem(new CLink(_('Last'), $url->getUrl()));*/
    }
    else {
        $config = select_config();
        $end = $start + $rowsPerPage;
        if($end > $itemsCount) {
            $end = $itemsCount;
        }
        $total = $itemsCount;

        if($config['search_limit'] < $itemsCount) {
            if($sortorder == ZBX_SORT_UP) {
                array_pop($items);
            }
            else {
                array_shift($items);
            }

            $total .= '+';
        }
    }

    // trim array with items to contain items for current page
    $items = array_slice($items, $start, $rowsPerPage, true);

    return $ul->addClass('pagination')->addItem($tags);
}

//多维数组按key的值排序
function array_orderby() {
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}

/*
 * debug echo print_r var_dump or exit
 */
function _ex($obj = null, $exit = true) {
    static $cnt = 1;
    $type = gettype($obj);
    switch($type) {
        case "boolean":
            var_dump($obj);
            echo("Step " . $cnt . " >> boolean -- " . date("m-d H:i:s") . " <br>");
            break;
        case "integer":
        case "double":
        case "string":
            echo("Step " . $cnt . " >> " . $obj . " -- " . date("m-d H:i:s") . " <br>");
            break;
        case "array":
            print_r($obj);
            echo "<br>";
            echo("Step " . $cnt . " >> array count:" . count($obj) . " -- " . date("m-d H:i:s") . " <br>");
            break;
        case "object":
            var_dump($obj);
            echo("Step " . $cnt . " >> object -- " . date("m-d H:i:s") . " <br>");
            break;
        case "resource":
            var_dump($obj);
            echo("Step " . $cnt . " >> resource -- " . date("m-d H:i:s") . " <br>");
            break;
        case "NULL":
            var_dump($obj);
            echo("Step " . $cnt . " >> NULL -- " . date("m-d H:i:s") . " <br>");
            break;
        default:
            break;
    }

    if($exit) {
        exit();
    }

    $cnt++;
}