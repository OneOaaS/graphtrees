{include file="header.tpl"}
<div class="container body">
    <div class="main_container">
        <div class="row">
            <div class="x_panel" style="border-bottom: 0">
                <div class="x_title">
                    <h3>
                        <div class="pull-left">
                            <a href="http://www.oneoaas.com" style="background-color: #008ed8;padding: 8px;">
                                <img src="/oneoaas/assets/img/logo.png">
                            </a>
                        </div>
                        <div class="pull-left" style="margin: 0 10px;">Graphtree</div>
                        <div class="pull-right">
                            <a class="btn btn-dark" href="/">返回Dashboard</a>
                        </div>
                    </h3>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div class="col-sm-2 col-md-2 col-lg-2">
                        <div class="x_panel" style="min-width: 200px;overflow-x: auto">
                            <ul id="graphtree" class="ztree"></ul>
                        </div>
                    </div>
                    <div class="col-sm-10 col-md-10 col-lg-10">
                        <div class="x_panel">
                            <div class="x_content">
                                <div class="row">
                                    <div class="col-md-55">
                                        <span>开始时间</span>
                                        <input id="starttime" name="starttime" type="text" class="form-control datepicker" value="{$timeline.starttime}" placeholder="">
                                    </div>
                                    <div class="col-md-55">
                                        <span>结束时间</span>
                                        <input id="endtime" name="endtime" type="text" class="form-control datepicker" value="{$timeline.endtime}" placeholder="">
                                    </div>
                                    <div class="col-md-55">
                                        <span>搜索</span>
                                        <input id="search_q" type="text" class="form-control" placeholder="">
                                    </div>
                                    <div class="col-md-55" style="height: 53px;line-height: 53px;">
                                        <input id="search" type="button" class="btn btn-primary" value="查询">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="x_panel">
                            <div class="x_content"><div id="pagination-top"></div></div>
                            <div class="x_content">
                                <div id="graphs">
                                    <div class="oos-center">请选择分组或输入查询条件</div>
                                </div>
                            </div>
                            <div class="x_content"><div id="pagination-bottom"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<footer id="footer" class="footer navbar-inverse">
    <div class="container">
        <ul>
            <li class="qrcode">
                <img src="/oneoaas/assets/img/qrcode.jpg">
                <p>专业 合作 开放 </p>
                <p>运维方案解决专家</p>
            </li>
            <li class="business">
                <p>Zabbix监控项目承接</p>
                <p>运维解决方案咨询</p>
                <p>运维产品咨询</p>
                <p><a href="http://www.oneoaas.com">www.oneoaas.com</a></p>
                <p>请联系 suppert#oneoaas.com (#替换为@)</p>
            </li>
            <li>
                <p><a href="http://weibo.com/itnihao">@itnihao</a></p>
                <p><a href="http://weibo.com/hell0wor1d">@hell0wor1d</a></p>
            </li>
        </ul>
    </div>
</footer>
<script src="/oneoaas/assets/js/jquery-1.11.3.min.js"></script>
<script src="/oneoaas/assets/js/jquery.datetimepicker.full.min.js"></script>
<script src="/oneoaas/assets/js/jquery.simplePagination.js"></script>
<script src="/oneoaas/assets/js/jquery.ztree.all.min.js"></script>
<script src="/oneoaas/assets/js/oneoaas.js"></script>
<script>

    var timeline = {
        stime:'{$timeline.stime}',
        period:'{$timeline.period}',
        etime:'{$timeline.etime}'
    };

    var form = {
        groupid:0,
        hostid:0,
        applicationid:0,
        from:'',
        groupids:[],
        hostids:[],
        applicationids:[],
        starttime:'',
        endtime:'',
        search:'',

        init:function(){
            $('#search_q').on('keydown',function(e){
                if(e.which == 13){
                    $('#search').trigger('click');
                }
            });

            $('#search').on('click',function(){
                form.search = $('#search_q').val();
                form.from = "main";
                if(form.groupids.length === 0 && form.hostids.length === 0 && form.search === ''){
                    alert("请输入搜索条件或选择主机组!");
                }else{
                    graph.getGraphs(form.groupids,form.hostids,null,form.search,true);
                }
            });

            $('#starttime').on('change',function(){
                var stime = $('#starttime').val(),
                    etime = $('#endtime').val();

                if(stime !== ''){
                    stime = $.myTime.DateToUnix(stime);
                }
                if(etime !== ''){
                    etime = $.myTime.DateToUnix(etime);
                }
                if(stime && etime && (etime - stime < 3600)){
                    alert("请选择合适的时间范围!");
                    $('#starttime').val('');
                }else{
                    timeline.stime = stime;
                }
            });

            $('#endtime').on('change',function(){
                var stime = $('#starttime').val(),
                    etime = $('#endtime').val();

                if(stime !== ''){
                    stime = $.myTime.DateToUnix(stime);
                }
                if(etime !== ''){
                    etime = $.myTime.DateToUnix(etime);
                }

                if(stime && etime && (etime - stime < 3600)){
                    alert("请选择合适的时间范围!");
                    $('#endtime').val('');
                }else{
                    timeline.etime = etime;
                }

                if(timeline.period < 0){
                    alert('请选择合理的时间范围');
                    return false;
                }
            })
        },

        remove:function(type,id){
            var idx;
            switch (type){
                case 'group':
                    idx = $.inArray(id,form.groupids);
                    if(idx !== -1){
                        form.groupids.splice(idx,1);
                    }
                    break;
                case 'host':
                    idx = $.inArray(id,form.hostids);
                    if(idx !== -1){
                        form.hostids.splice(idx,1);
                    }
                    break;
                case 'application':
                    idx = $.inArray(id,form.applicationids);
                    if(idx !== -1){
                        form.applicationids.splice(idx,1);
                    }
                    break;
            }
        },

        add:function(type,id){
            switch (type){
                case 'group':
                    form.groupids.push(id);
                    break;
                case 'host':
                    form.hostids.push(id);
                    break;
                case 'application':
                    form.applicationids.push(id);
                    break;
            }
        }

    };

    var graph = {
        selector:$('#graphs'),
        data:undefined,

        getGraphs:function(groupids,hostids,applicationids,search,pagination,page){
            var params = {
                action:"graphs.get",
                from:form.from
            };

            if(form.from === 'sidebar'){
                params.groupids = form.groupid !== 0 ? [form.groupid] : undefined;
                params.hostids = form.hostid !== 0 ? [form.hostid] : undefined;
                params.applicationids = form.applicationid !== 0 ? [form.applicationid] : undefined;
            }else{
                if(groupids !== null){
                    params.groupids = groupids;
                }
                if(hostids !== null){
                    params.hostids = hostids;
                }
                if(applicationids !== null){
                    params.applicationids = applicationids;
                }
            }

            if(search !== null){
                params.search = search;
            }
            if(page !== null){
                params.page = page;
            }

            $.post('api/graph.php',
                    params,
                    function(data){
                        if(data.code === 200){
                            if(pagination){
                                graph.pagination(data.content);
                            }
                            graph.makeGraph(data);
                        }
                    },
                    'JSON'
            );
        },

        pagination:function(data){
            var items = data.total,
                total = items + data.itemsCount,
                itemsOnPage = data.itemsOnPage;
            if(total === 0){
                $('#pagination-top,#pagination-bottom').children().remove();
                return;
            }
            var toNextPage = function(page){
                graph.getGraphs(form.groupids,form.hostids,form.applicationids,form.search,false,page);
            };

            $('#pagination-top,#pagination-bottom').pagination({
                items:items,
                itemsOnPage: itemsOnPage,
                displayedPages:5,
                edges:1,
                onInit:function(){
                    $('#pagination-top').append('<div class="total">共'+ total +'项</div>');
                    $('#pagination-bottom').append('<div class="total">共'+ total +'项</div>');
                },
                onPageClick: function(page){
                    toNextPage(page);
                    $('#pagination-top').append('<div class="total">共'+ total +'项</div>');
                    $('#pagination-bottom').append('<div class="total">共'+ total +'项</div>');
                },
                cssStyle: 'light-theme'
            });
        },

        makeGraph:function(data){
            if(timeline.etime == '' || timeline.stime == ''){
                timeline.period = 3600
            }

            if(timeline.etime && timeline.stime){
                timeline.period = parseInt(timeline.etime) - parseInt(timeline.stime);
            }else{
                timeline.period = 3600;
            }

            if(timeline.stime == ''){
                timeline.stime = timeline.etime !== '' ? parseInt(timeline.etime) - parseInt(timeline.period) : $.myTime.CurTime - timeline.period;
            }

            graph.selector.children().remove();
            jQuery.each(data.content.graphs,function(idx,ctx){
                var tmp_a = jQuery('<a class="graph_img" href="/charts.php?graphid='+ctx.graphid+'&stime='+timeline.stime+'&period='+timeline.period+'"></a>');
                var handle = 'chart2.php';
                if(ctx.graphtype ==2 || ctx.graphtype == 3){
                    handle = 'chart6.php';
                }
                tmp_a.append(
                        '<img src="/'+ handle +'?graphid='+ ctx.graphid +'&width=520&height=260&stime='+timeline.stime+'&period='+timeline.period+'">'
                );
                graph.selector.append(tmp_a);
            });

            jQuery.each(data.content.items,function(idx,ctx){
                var tmp_a = jQuery('<a class="graph_img"></a>');
                var handle = 'chart.php';
                tmp_a.append(
                        '<img src="/'+ handle +'?itemids='+ ctx.itemid +'&width=520&height=260&stime='+timeline.stime+'&period='+timeline.period+'">'
                );
                graph.selector.append(tmp_a);
            });
            graph.selector.append('<div class="clearfix"></div>');
            if(data.content.values.length > 0){
                var table = jQuery('<table class="table table-bordered margin-top">' +
                        '<tr><th>名称</th><th>检查时间</th><th>值</th><th>操作</th></tr>' +
                        '</table>');
                jQuery.each(data.content.values,function(idx,ctx){
                    table.append('<tr>' +
                            '<td>'+ ctx.name +'</td>'+
                            '<td>'+ $.myTime.UnixToDate(ctx.clock,true) +'</td>'+
                            '<td>'+ ctx.value +'</td>'+
                            '<td><a href="/history.php?action=showvalues&period=86400&itemids[]='+ ctx.itemid+'" target="_blank">查看历史</a></td>'+
                            '</tr>');
                });

                graph.selector.append(table);
            }

            if(data.content.graphs.length == 0 && data.content.items.length == 0 && data.content.values.length == 0){
                graph.selector.append('<div class="oos-center">该分组下不存在图形或监控项</div>');
            }
        }
    };

    var getGraphs = function(type,id){
        var params = {
            action : 'graphs.get',
            from: 'sidebar',
            groupids:[],
            hostids:[],
            applicationids:[]
        };
        form.groupid = 0;
        form.hostid = 0;
        form.applicationid = 0;
        form.from = "sidebar";
        switch (type){
            case 'group':
                params.groupids = [id];
                form.groupid = id;
                break;
            case 'host':
                params.hostids = [id];
                form.hostid = id;
                break;
            case 'application':
                params.applicationids = [id];
                form.applicationid = id;
                break;
        }
        graph.getGraphs(params.groupids,params.hostids,params.applicationids,'',true);
    };

    var zTree;
    var setting = {
        view: {
            dblClickExpand: false
        },
        check:{
            enable:true,
            chkboxType: { "Y": "s", "N": "ps" },
            chkStyle: "checkbox"
        },
        callback:{
            onCheck:function(event, treeId, treeNode){
                var chk_status = treeNode.checked,
                    groupid = treeNode.groupid,
                    hostid = treeNode.hostid;
                if(groupid !== undefined){
                    switch (treeNode.check_Child_State){
                        case 0:
                        case 1:
                        case 2:
                            $.each(treeNode.children,function(idx,ctx){
                                if(chk_status){
                                    form.add('host',ctx.hostid);
                                }else{
                                    form.remove('host',ctx.hostid);
                                }
                            });
                            break;
                        case -1:
                            if(chk_status){
                                form.add('group',groupid);
                            }else{
                                form.remove('group',groupid);
                            }
                            break;
                    }
                }

                if(hostid !== undefined){
                    var ptreeNode = treeNode.getParentNode();
                    if(ptreeNode.checked){
                        zTree = $.fn.zTree.getZTreeObj("graphtree");
                        zTree.checkNode(ptreeNode,false,false,false);
                        form.remove('group',ptreeNode.groupid);
                    }

                    if(chk_status){
                        form.add('host',hostid);
                    }else{
                        form.remove('host',hostid);
                    }
                }

            }
        },
        async: { //异步加载请求数据
            enable: true,
            url: "api/graph.php?action=getZtree",
            autoParam: ["groupid=groupid", "hostid=hostid"], //请求的参数即groupid=nodeid
            otherParam: { "timestamp": new Date().getTime()},
            type: "get"
        }
    };

    $(function () {
        $.fn.zTree.init(jQuery("#graphtree"), setting);
        $('.datepicker').datetimepicker({
            lang: "ch",
            format:"Y-m-d H:i"
        });
        form.init();
    });
</script>
