<!DOCTYPE html>
<html lang="[+lc+]">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=[+charset+]" />
    <link rel="stylesheet" href="[+site_url+]assets/modules/error404logger/e404logger.css" type="text/css" media="screen" />
    <link rel="stylesheet" type="text/css" href="[+theme_path+]/style.css" />
    <title>Error 404 Logger</title>
    <script>
        var  queryString = "?a=[+_GET_a+]&id=[+_GET_id+]";

        function navAllInactive()
        {
            oNav = document.getElementById("nav");
            oLis = oNav.getElementsByTagName("LI");

            for (i = 0; i < oLis.length; i++)
            {
                oLis[i].className = "";
            }
        }

        function hideAllData()
        {
            oData = document.getElementById("data");
            oDivs = oData.getElementsByTagName("DIV");

        }

        function doRemove(url)
        {
            if (confirm("Really delete entries for '" + url + "'?"))
            {
                url = escape(url);
                window.location = "[+_SERVER_SCRIPT_NAME+]"+queryString+"&tab=top&do=remove&url="+url;
            }
            return false;
        }

        function clearAll()
        {
            if (confirm("Really delete ALL entries?"))
            {
                window.location = "[+_SERVER_SCRIPT_NAME+]"+queryString+"&do=clearAll";
            }
            return false;
        }

        function clearLast(num)
        {
            if (confirm("Really delete all entries except for the last "+num+" days?"))
            {
                window.location = "[+_SERVER_SCRIPT_NAME+]"+queryString+"&do=clearLast&days="+num;
            }
            return false;
        }
    </script>
</head>
<body>
<h1>Error 404 Logger</h1>
<div class="sectionBody">
    <div id="actions">
        <ul class="actionButtons">
            <li onclick="clearAll();"><a href="#">[+_lang_clear_log+]</a></li>
            <li onclick="clearLast([+keepLastDays+]);"><a href="#">[+_lang_clear_log+] recent [+keepLastDays+] days</a></li>
        </ul>
    </div>
    <div class="tab-pane" id="pane1">
        <script type="text/javascript" src="media/script/tabpane.js"></script>
        <script type="text/javascript"> pane1 = new WebFXTabPane(document.getElementById("pane1"),false); </script>
        <div class="tab-page" id="all">
            <h2 class="tab">All entries</h2>
            <script type="text/javascript">pane1.addTabPage(document.getElementById("all"));</script>
            [+logs+]
        </div>
        <div class="tab-page" id="top">
            <h2 class="tab">Most wanted</h2>
            <script type="text/javascript">pane1.addTabPage(document.getElementById("top"));</script>
            <div>[+showing+]</div>
            [+showtop+]
        </div>
    </div>
</div>
</body>
</html>
