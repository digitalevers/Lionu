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
	
	<script src="/install_style/js/jquery-2.0.0.js"></script>
    <script src="/install_style/js/install.js"></script>
</head>
<body>

<p id="logo"><a href="https://www.lion-u.com/"></a></p>
<!--<p id="title">用数据挖掘您的广告价值</p>-->
<div id="container">
    <form method="post" action="/install/index?step=2" name="config" onsubmit="return false">
    <h2>系统信息</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="sysuser">SDK上报域名</label></th>
                <td><input class="conf required" name="sdkdomain" id="sdkdomain" type="text"  size="25" placeholder="请填写一个公网解析的域名" data-valid="isNonEmpty||isDomain" data-error="<i class='icon-tips'></i>域名不能为空||<i class='icon-tips'></i>请填写正确格式的域名"/><label class="focus valid"></label>
                <p>该域名的虚拟目录与量U系统虚拟目录处于同一文件夹下，并配好https</p>
                </td>
                <td id="sdkdomain-desc"></td>
            </tr>
        </table>
        <h2>管理员信息</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="sysuser">登录用户</label></th>
                <td><input class="conf required" name="sysuser" id="sysuser" type="text" size="25" value="admin" data-valid="isNonEmpty||between:4-10||isAdmin" data-error="<i class='icon-tips'></i>您还没有输入用户名||<i class='icon-tips'></i>用户名长度4-10位||<i class='icon-tips'></i>只能输入字母、数字"  /><label class="focus valid"></label>
		    </td>
                <td id="sysuser-desc"></td>
            </tr>
            <tr>
                <th scope="row"><label for="syspwd">登录密码</label></th>
                <td><input class="conf required" name="syspwd" id="syspwd" type="password" size="25" placeholder="请牢记您的密码" data-valid="isNonEmpty||between:6-20||isPwd" data-error="<i class='icon-tips'></i>您还没有输入密码||<i class='icon-tips'></i>密码长度6-20位||<i class='icon-tips'></i>只能输入字母、数字"  /><label class="focus valid"></label>
                </td>
                <td id="syspwd-desc"></td>
            </tr>
            <tr>
                <th scope="row"><label for="sysrepwd">确认密码</label></th>
                <td><input class="conf required" name="sysrepwd" id="sysrepwd" type="password" size="25" data-valid="isNonEmpty||between:6-20||isPwd||isRepeat:syspwd" data-error="<i class='icon-tips'></i>您还没有输入密码||<i class='icon-tips'></i>密码长度6-20位||<i class='icon-tips'></i>只能输入字母、数字||<i class='icon-tips'></i>重复密码不一致" /><label class="focus valid"></label>
			</td>
                <td id="sysrepwd-desc"></td>
            </tr>
        </table>
        <h2>MySQL信息</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="dbhost">数据库地址</label></th>
                <td><input class="dbconf conf required" name="dbhost" id="dbhost" type="text" size="25" value="127.0.0.1" data-valid="isNonEmpty||isIP" data-error="<i class='icon-tips'></i>您还没有输入数据库地址||<i class='icon-tips'></i>请输入合法的IP地址" readonly="readonly" /><label class="focus valid"></label>
                <p>在使用PDO连接本地MySQL服务时,使用127.0.0.1比localhost有更好的兼容性</p>
                </td>
                <td id="dbhost-desc"></td>
            </tr>
            <tr>
                <th scope="row"><label for="dbname">数据库名</label></th>
                <td><input class="dbconf conf required" name="dbname" id="dbname" type="text" size="25" value="lionu" data-valid="isNonEmpty||between:1-50" data-error="<i class='icon-tips'></i>您还没有输入数据库名||<i class='icon-tips'></i>请输入合适的长度" readonly="readonly" />
				<label class="focus valid"></label>
			</td>
                <td id="dbname-desc"></td>
            </tr>
    
            <tr>
                <th scope="row"><label for="dbuser">数据库用户名</label></th>
                <td><input class="dbconf conf required" name="dbuser" id="dbuser" type="text" size="25" value="root" data-valid="isNonEmpty||between:1-50" data-error="<i class='icon-tips'></i>您还没有输入数据库名||<i class='icon-tips'></i>请输入合适的长度" /><label class="focus valid"></label></td>
                <td id="dbuser-desc"></td>
            </tr>
            <tr>
                <th scope="row"><label for="dbpwd">数据库密码</label></th>
                <td><input class="dbconf conf" name="dbpwd" id="dbpwd" type="password" aria-describedby="dbpwd-desc" size="25" value="" autocomplete="off" /></td>
                <td id="dbpwd-desc"></td>
            </tr>
            <tr>
                <th scope="row"><label for="dbport">数据库端口</label></th>
                <td><input class="dbconf conf required" name="dbport" id="dbport" type="text" size="25" value="3306" autocomplete="off" data-valid="isNonEmpty||isPort" data-error="<i class='icon-tips'></i>您还没有输入数据库端口||<i class='icon-tips'></i>请输入合适的端口号" readonly="readonly" /><label class="focus valid"></label></td>
                <td id="dbport-desc"></td>
            </tr>
        </table>
        <h2>Redis 信息</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="redishost">Redis 地址</label></th>
                <td><input class="redisconf conf" name="redishost" id="redishost" type="text" aria-describedby="redishost-desc" size="25" value="127.0.0.1" required readonly="readonly" />
                </td>
                <td id="redishost-desc"></td>
            </tr>
    
            <tr>
                <th scope="row"><label for="kafkaport">Redis 端口</label></th>
                <td><input class="redisconf conf" name="redisport" id="redisport" type="text" aria-describedby="redisport-desc" size="25" value="6379" autocomplete="off" required readonly="readonly"/></td>
                <td id="redisport-desc"></td>
            </tr>
        </table>
            <?php
            if ( isset( $_GET['noapi'] ) ) {
                ?>
    <!-- <input name="noapi" type="hidden" value="1" /> -->
    <?php } ?>
        <!-- <input type="hidden" name="language" value=" /> -->
        <p class="step"><input name="submit" type="button" value="提交配置" class="button button-large" /></p>
    </form>
</div>
</body>
<script type="text/javascript">
	/* function checkForm(){
		$("input").each(function(){
			console.log($(this).attr('required'))
		})
	} */
	
	$(document).ready(function(){
		$("input[name='submit']").click(function(){
			if (!verifyCheck._click()){
				return false;
			}
			//检查登录用户名
			/*var sysuser = $.trim($("#sysuser").val())
			if(/^[a-zA-Z]{5,10}$/.test(sysuser) == false){
				$("#sysuser").addClass('error')
				alert('管理员用户名必须为5-10位的英文字符')
				return false
			} else {
				$("#sysuser").removeClass('error')
			}
			var pwd = $.trim($("#syspwd").val())
			var repwd = $.trim($("#sysrepwd").val())
			if(pwd.length < 6 || pwd.length > 20){
				alert('密码长度应在6-20位之间')
				return false
			} else {
    			if(pwd != repwd){
    				$("#sypwd").addClass('error')
    				$("#syrepwd").addClass('error')
    				alert('密码不一致')
    				return false
    			} else {
    				$("#sypwd").removeClass('error')
    				$("#syrepwd").removeClass('error')
    			}
			}*/

			var dbconf = $(".conf").serialize();

			$.ajax({
				'url':'/install/checkConfigAndEnvVersion',
				'type':'POST',
				'data':dbconf,
				'dataType':'json',
				'error':function(xhr, status, err){
					alert('请检查系统部署状态并提升服务器配置,关闭protobuf扩展并调大output_buffering的值')
				},
				'success':function(res){
					if(res.code != 200){
						alert(res.msg)
					} else {
						//页面跳转
						window.location.href = '?step=2'
					}
				}
			})

		})
	})
</script>
</html>
