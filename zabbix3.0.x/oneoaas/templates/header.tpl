<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{$page.title}</title>

    <!-- Bootstrap core CSS -->
    <link href="/oneoaas/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/oneoaas/assets/css/fonts/css/font-awesome.min.css" rel="stylesheet">
    <link href="/oneoaas/assets/css/animate.min.css" rel="stylesheet">
    <link href="/oneoaas/assets/css/custom.css" rel="stylesheet">
    {if $page.css}
        {foreach $page.css as $css}
            <link href="{$css}" rel="stylesheet" type="text/css">
        {/foreach}
    {/if}

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head><body class="nav-md">