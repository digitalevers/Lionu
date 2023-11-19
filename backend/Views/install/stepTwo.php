<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>量U-安装</title>
	<meta name="description" content="The small framework with powerful features">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel='stylesheet' id='dashicons-css'  href='/install_style/css/dashicons.min.css?ver=5.4.2' type='text/css' media='all' />
	<link rel='stylesheet' id='buttons-css'  href='/install_style/css/buttons.min.css?ver=5.4.2' type='text/css' media='all' />
	<link rel='stylesheet' id='forms-css'  href='/install_style/css/forms.min.css?ver=5.4.2' type='text/css' media='all' />
	<link rel='stylesheet' id='l10n-css'  href='/install_style/css/l10n.min.css?ver=5.4.2' type='text/css' media='all' />
	<link rel='stylesheet' id='install-css'  href='/install_style/css/install.min.css?ver=5.4.2' type='text/css' media='all' />
	<link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
	
	<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
</head>
<body>

<p id="logo"><a href="https://www.lion-u.com"></a></p>
<form method="post" action="index?step=3" name="config" onsubmit="return false">
	<p class="step">量U广告归因—用数据挖掘您的广告价值</p>
	<p class="step">配置写入完成，点击开始安装</p>
	<p class="step"><input name="submit" type="submit" value="开始安装" class="button button-large" /></p>
</form>
</body>
<script type="text/javascript">
	$("form[name='config']").submit(function(){
		var pass = true;
		$.ajax({
			'url':'/install/step3',
			'type':'POST',
			'dataType':'json',
			'success':function(res){
				if(res.code != 200){
					alert(res.msg)
					pass = false;
				} else {
					//页面跳转
					window.location.href = '?step=3'
				}
			}
		})
		return pass
	})
</script>
</html>
