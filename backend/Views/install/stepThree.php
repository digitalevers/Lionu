<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>量U-安装</title>
	<meta name="description" content="The small framework with powerful features">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel='stylesheet' id='dashicons-css'  href='/install_style/css/dashicons.min.css' type='text/css' media='all' />
	<link rel='stylesheet' id='buttons-css'  href='/install_style/css/buttons.min.css' type='text/css' media='all' />
	<link rel='stylesheet' id='forms-css'  href='/install_style/css/forms.min.css?' type='text/css' media='all' />
	<link rel='stylesheet' id='l10n-css'  href='/install_style/css/l10n.min.css' type='text/css' media='all' />
	<link rel='stylesheet' id='install-css'  href='/install_style/css/install.min.css' type='text/css' media='all' />
	<link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
	
	<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
</head>
<body>

<p id="logo"><a href="https://www.lion-u.com/"></a></p>
<form method="post" action="/static/" name="config">
	<p class="step">量U广告归因——用数据挖掘您的广告价值</p>
	<p class="step">安装进度</p>
	<ul id="install-process"></ul>
	<p class="step"><input name="submit" type="submit" value="正在安装" class="button button-large" /></p>
</form>
</body>
<script type="text/javascript">
	/* function checkForm(){
		$("input").each(function(){
			console.log($(this).attr('required'))
		})
	} */
	
	$(document).ready(function(){
		$.ajax({
			'url':'/install/checkDbSchema',
			'type':'POST',
			'dataType':'json',
			'success':function(res){
				if(res.code != 200){
					alert(res.msg)
				} else {
					var tables = res.data;

					for(i in tables){
						var li = tables[i]['installResult'] == 1 ? "<li>数据表"+tables[i]['tableName']+"安装成功</li>" : "<li>数据表"+tables[i]['tableName']+"安装失败</li>";
						(function(t, dom, length) {   // 注意这里是形参
					        setTimeout(function() {
					        	$("#install-process").append(dom)
					        	if(t == length - 1){
					        		$("input[name='submit']").val("安装完毕!");
					        	}
					        }, 500 * t);	// 还是每秒执行一次，不是累加的
					    })(i, li,tables.length)   // 注意这里是实参，这里把要用的参数传进去
					}
					
				}
			}
		})
	})
</script>
</html>
