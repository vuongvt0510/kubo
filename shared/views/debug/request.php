<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="ja">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=9" />
        <meta charset="utf-8" />

        <title>API テストリクエスト</title>

        <style type="text/css">
            <?php echo file_get_contents(dirname(__FILE__) . "/stylesheets/cssreset-min.css") ?>
            <?php echo file_get_contents(dirname(__FILE__) . "/bootstrap/css/bootstrap.min.css") ?>
            <?php echo file_get_contents(dirname(__FILE__) . "/bootstrap/css/bootstrap-responsive.min.css") ?>
        </style>

        <script type="text/javascript">
            <?php echo file_get_contents(dirname(__FILE__) . "/javascripts/jquery.js") ?>
            <?php echo file_get_contents(dirname(__FILE__) . "/bootstrap/js/bootstrap.min.js") ?>
            <?php echo file_get_contents(dirname(__FILE__) . "/javascripts/jquery.dump.js") ?>
            <?php echo file_get_contents(dirname(__FILE__) . "/javascripts/jquery.cookie.js") ?>
        </script>
        <script type="text/javascript">
            var isSeed = 0;
            $.extend($.fn, {
                id : function(prefix) {
                    return this.each(function (ind, obj) {
                        $(this).attr("id", (prefix || "jqgen_") + (++isSeed));
                    });
                },

                createdom: function(opts) {
                    var defaultSettings = {
                        target: this
                    };
                    var settings = $.extend({}, defaultSettings, opts);

                    if(!settings.id && !settings.target.attr('id')){
                        this.id();
                    }
                    if(settings.attr){
                        this.attr(settings.attr);
                    }
                    if(settings.cls){
                        this.addClass(settings.cls);
                    }
                    if(settings.style){
                        this.css(settings.style);
                    }
                    return this;
                }
            });

            var num = 5;

            $(document).ready(function(){

                //cookieの設定
                $.cookie.json = true;
                    
                var vbase = $('#vbase');
                //パラメータ欄を追加
                var addparam = function(){
                for(var i = 0; i < num; i++){
                    var fm = $('<div/>').createdom({
                        cls:'form',
                        style:{
                            clear:'both',
                            height: '35px'
                        }
                        });
                    fm.append($('<input/>').attr({
                        type:"text",
                        name:"name",
                        style:"width:200px; margin-right: 10px; float:left;"
                    }));
                    fm.append($('<span>:</span>').attr({
                        style:"margin-right: 10px; float:left;"
                        }));
                    fm.append($('<input/>').attr({
                        type:"text",
                        name:"value",
                        style:"width:200px; margin-right: 10px; float:left;"
                    }));

                    vbase.append(fm);
                }};
                addparam();

                //履歴ボタンを追加
                var paths = [];
                var results = [];
                var rwap = $('#resultwrap');
                var prwap = $('#resultpathwrap');
                var addResult = function(data){

                    var pst = JSON.stringify(data);

                    //パス 
                    var pbtn = $('<button class="btn btn-info" style="margin:10px;">'+($('#request').val())+'</button>');
                    pbtn.data('params',$('#request').val());
                    pbtn.on('click', function(){
                        $('#request').val($(this).data('params'));
                    });
                    prwap.prepend(pbtn);
                    paths.unshift(pbtn);

                    //パラメータ
                    var btn = $('<button class="btn btn-warning" style="margin:10px;">'+pst+'</button>');
                    btn.data('params',pst);
                    btn.on('click', function(){
                        setParam($(this).data('params'));
                    });
                    rwap.prepend(btn);
                    results.unshift(btn);

                    if(results.length > 10){
                        var t = results.pop();
                        t.remove();
                    }
                    if(paths.length > 10){
                        var pt = paths.pop();
                        pt.remove();
                    }

                    //cookieに保存
                    $.cookie('data01', data);
                };

                var setParam = function(data){

                    var fms = $('.form');
                    $.each(fms, function(index, el){
                        $(el).find('input[name=name]').val(null);
                        $(el).find('input[name=value]').val(null);
                    });
                    
                    if(!data){
                        return;
                    }

                    var p = data;
                    if(typeof data == 'string'){
                        p = JSON.parse(data);
                    }

                    var cnt = 0;
                    $.each(p, function(key, val){
                        $('input[name=name]:eq('+cnt+')').val(key);
                        $('input[name=value]:eq('+cnt+')').val(val);
                        cnt++;
                    });
                };
                
                var pastdata = $.cookie('data01');
                if(pastdata){
                    setParam(pastdata);
                }

                $('#resetprm').on('click', function(){
                    setParam();
                });
                
                $('#addparam').on('click', addparam);

                $('#submit').on('click', function(e){

                    e.stopPropagation()
                    var p = {};
                    var fms = $('.form');
                    $.each(fms, function(index, el){
                        if($(el).find('input[name=name]').val()){
                            p[($(el).find('input[name=name]').val())] = $(el).find('input[name=value]').val()
                        }
                    });

                $.ajax({
                    url: $('#request').val(),
                    dataType: 'json',
                    data: p,
                    type: $('#method').val(),
                    success: function(data){

                        console.info($.parseJSON(p));
                        addResult(p);

                        console.info(data);
                        console.info($('#result'));
                        console.info($(data).dump());
                        var res = $(data).dump();
                        $('#result').html(res);
                    },
                    error: function(data){
                        var res = $(data).dump();
                        $('#result').html(res);
                    }
                });
                });
            });
        </script>
        <style type="text/css">
        .jumbotron {
        position: relative;
        padding: 20px 0 0px;
        color: #fff;
        text-align: left;
        text-shadow: 0 1px 3px rgba(0,0,0,.4), 0 0 30px rgba(0,0,0,.075);
        background: #020031; /* Old browsers */
        background: -moz-linear-gradient(45deg,  #020031 0%, #6d3353 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left bottom, right top, color-stop(0%,#020031), color-stop(100%,#6d3353)); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(45deg,  #020031 0%,#6d3353 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(45deg,  #020031 0%,#6d3353 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(45deg,  #020031 0%,#6d3353 100%); /* IE10+ */
        background: linear-gradient(45deg,  #020031 0%,#6d3353 100%); /* W3C */
        -webkit-box-shadow: inset 0 3px 7px rgba(0,0,0,.2), inset 0 -3px 7px rgba(0,0,0,.2);
        -moz-box-shadow: inset 0 3px 7px rgba(0,0,0,.2), inset 0 -3px 7px rgba(0,0,0,.2);
        box-shadow: inset 0 3px 7px rgba(0,0,0,.2), inset 0 -3px 7px rgba(0,0,0,.2);
        }
        .jumbotron h1 {
        font-size: 32px;
        font-weight: bold;
        letter-spacing: -1px;
        line-height: 1;
        }
        .jumbotron p {
        font-size: 16px;
        font-weight: 300;
        line-height: 1.25;
        margin-bottom: 30px;
        }
        </style>
    </head>
    <body>
        <header class="jumbotron" id="overview">
            <div class="container">
            <h1>APIのテストリクエスト</h1>
            <p class="lead">APP test Request</p>
            </div>
        </header>
        <div id="viewport" style="padding:3em">
            <div id="form" class="container-fluid">
                <div class="wrapper" class="row-fluid">
                    <div class="span5">
                        <p>リクエスト先（例:/login/info）</p>
                        <input id="request" type="text" name="request" style="margin-right: 10px; emargin: 3px;" value="/login/info"/>
                        <select class="medium" name="method" id="method">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                        </select>
                        <p style="clear:both;">
                        <button id="addparam" class="btn">パラメータ追加</button>
                        </p>
                        <p>パラメータ（key : value）</p> 
                        <div id="vbase">
                        </div>
                        <p style="clear:both;">
                        <button id="resetprm" class="btn btn-danger btn-large">リセット</button>
                        <button id="submit" class="btn btn-success btn-large">送信</button>
                        </p>
                        <h3>結果</h3>
                        <textarea id="result" name="kanso" rows="8" cols="60">結果を表示</textarea>
                    </div>
                    <div class="span2">
                        <p>リクエスト先履歴</p>
                        <div id="resultpathwrap"></div>
                    </div>
                    <div class="span4">
                        <p>リクエストパラメータ履歴</p>
                        <div id="resultwrap"></div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
