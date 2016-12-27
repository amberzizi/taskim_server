<?=var_dump($message_record);?>
<?=var_dump($if_can_chat);?>
<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>微活任务im</title>
  <script type="text/javascript">
  //WebSocket = null;
  </script>
  <link href="/resource/test_im/css/bootstrap.min.css" rel="stylesheet">
  <link href="/resource/test_im/css/style.css" rel="stylesheet">
  <!-- Include these three JS files: -->
  <script type="text/javascript" src="/resource/test_im/js/swfobject.js"></script>
  <script type="text/javascript" src="/resource/test_im/js/web_socket.js"></script>
  <script type="text/javascript" src="/resource/test_im/js/jquery.min.js"></script>

  <script type="text/javascript">
    if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
    // 如果浏览器不支持websocket，会使用这个flash自动模拟websocket协议，此过程对开发者透明
    //WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
    // 开启flash的websocket debug
    //WEB_SOCKET_DEBUG = true;
	  
    var ws,uid,name,client_list={};

    // 连接服务端
    function connect() {
       // 创建websocket
       ws = new WebSocket("ws://192.168.1.140:18282");
       // 当socket连接打开时，输入用户名
       ws.onopen = onopen;
       // 当有消息时根据消息类型显示不同信息
       ws.onmessage = onmessage; 
       ws.onclose = function() {
    	  console.log("连接关闭，定时重连");
          connect();
       };
       ws.onerror = function() {
     	  console.log("出现错误");
       };
    }

    // 连接建立时发送登录信息
    function onopen()
    {
        if(!uid)
        {
            show_prompt();
        }
        // 登录
        var login_data = '{"type":"login","userid":"'+uid+'","client_name":"'+name.replace(/"/g, '\\"')+'","room_id":"<?=$gtaskid;?>"}';
        //console.log("websocket握手成功，发送登录数据:"+login_data);
        ws.send(login_data);
    }

    // 服务端发来消息时
    function onmessage(e)
    {
        console.log(e.data);
        var data = eval("("+e.data+")");
        switch(data['type']){
            // 服务端ping客户端
            case 'ping':
                ws.send('{"type":"pong"}');
                break;;
            // 登录 更新用户列表
            case 'login':
                //{"type":"login","client_id":xxx,"client_name":"xxx","client_list":"[...]","time":"xxx"}
                say(data['client_id'], data['client_name'],  data['client_name']+' 参与了会话', data['time']);
                if(data['client_list'])
                {
                    client_list = data['client_list'];
                }
                else
                {
                    client_list[data['client_id']] = data['client_name']; 
                }
                flush_client_list();
                //console.log(data['client_name']+"登录成功");
                break;
            // 发言
            case 'say':
                //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                if(data['ifsave'] == '1'){

                    $.post('/frontside/mobile/user_im_controller/save_im_char_record_ajax', 
                            {
                        		client_id: data.client_id,
                        		taskid:<?=$gtaskid;?>,
                        		usern:<?=$usern;?>,
                        		touser:<?=$touser;?>,
                                message:data['content'],
                                ifread:data['ifread']
                        	}, 
                        		function(data){}, 'json');
                }
                
                break;
            // 用户退出 更新用户列表
            case 'logout':
                //{"type":"logout","client_id":xxx,"time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['from_client_name']+' 退出了', data['time']);
                delete client_list[data['from_client_id']];
                flush_client_list();
        }
    }

    // 当前用户信息
    function show_prompt(){  
        uid = '<?=$usern;?>';
        name = '<?=$uname;?>';
    }

    // 提交对话
    function onSubmit() {
      var input = document.getElementById("textarea");
      ws.send('{"type":"say","to_client_uid":"'+<?=$touser;?>+'","to_client_name":"novalue","content":"'+input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
      input.value = "";
      input.focus();
    }

    // 刷新用户列表框
//     function flush_client_list(){
//     	var userlist_window = $("#userlist");
//     	var client_list_slelect = $("#client_list");
//     	userlist_window.empty();
//     	client_list_slelect.empty();
//     	userlist_window.append('<h4>在线用户</h4><ul>');
//     	client_list_slelect.append('<option value="all" id="cli_all">所有人</option>');
//     	for(var p in client_list){
//             userlist_window.append('<li id="'+p+'">'+client_list[p]+'</li>');
//             client_list_slelect.append('<option value="'+p+'">'+client_list[p]+'</option>');
//         }
//     	$("#client_list").val(select_client_id);
//     	userlist_window.append('</ul>');
//     }

    // 发言
    function say(from_client_id, from_client_name, content, time){
    	$("#dialog").append('<div class="speech_item"><img src="#'+from_client_id+'" class="user_icon"/> '+from_client_name+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+content+'</p> </div>');
    }

  </script>
</head>
<body onload="connect();">
    <div class="container">
	    <div class="row clearfix">
	        <div class="col-md-1 column">
	        </div>
	        <div class="col-md-6 column">
	           <div class="thumbnail">
	               <div class="caption" id="dialog"></div>
	           </div>
	           <form onsubmit="onSubmit(); return false;">
                    <textarea class="textarea thumbnail" id="textarea"></textarea>
                    <div class="say-btn"><input type="submit" class="btn btn-default" value="发送" /></div>
               </form>
               <div>
               </div>
             </div>
<!-- 	        <div class="col-md-3 column"> -->
<!-- 	           <div class="thumbnail"> -->
<!--                    <div class="caption" id="userlist"></div> -->
<!--                </div> -->
               
<!-- 	        </div> -->
	    </div>
    </div>
</body>
</html>

