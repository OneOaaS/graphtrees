<?php
/**
 * Created by PhpStorm.
 * User: Zhanhao
 * Date: 2016/4/11
 * Time: 17:15
 */
require dirname(dirname(__FILE__)).'/libs/Smarty.class.php';
require dirname(__FILE__).'/css.inc.php';
require dirname(dirname(dirname(__FILE__))).'/include/config.inc.php';

$userData = CWebUser::$data;

if(CWebUser::isGuest()){
    redirect('/');
}

$smarty = new Smarty;

//$smarty->force_compile = true;
//$smarty->debugging = true;
$smarty->caching = false;
$smarty->setConfigDir("conf");
$smarty->setTemplateDir(dirname(dirname(__FILE__))."/templates");
$smarty->cache_lifetime = 120;

$perfors = DBfetchArray(DBselect('select cateid,name from category where type = 1 and parentid = 0'));
$infras = DBfetchArray(DBselect('select cateid,name from category where type = 0 and parentid = 0'));
$smarty->assign('infras',$infras);
$smarty->assign('perfors',$perfors);
$smarty->assign('user',$userData);