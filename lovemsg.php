<?php
$host='localhost';
$dbname='lovemsg';
$user='root';
$pass='';
function GetIP(){
if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
$ip = getenv("HTTP_CLIENT_IP");
else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
$ip = getenv("HTTP_X_FORWARDED_FOR");
else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
$ip = getenv("REMOTE_ADDR");
else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
$ip = $_SERVER['REMOTE_ADDR'];
else
$ip = "unknown";
return($ip);
}
if(!isset($_REQUEST['action']))exit;
$act=$_REQUEST['action'];
$curtime=date('Y-m-d H:i:s');
$ip=GetIP();
if($act=='login'){
	if(!isset($_REQUEST['group'])||!isset($_REQUEST['member']))exit;
	$group=$_REQUEST['group'];
	$member=$_REQUEST['member'];
	if($group==''||$member=='')exit;
	$dbh = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
	$sql=$dbh->prepare("select * from groupinfo where name=?");
	$sql->execute(array($group));
	$groupinfo=$sql->fetch();
	$sql=null;
	if($groupinfo!=FALSE){
		$sql=$dbh->prepare("select * from groupmember where name=? and groupid=?");
		$sql->execute(array($member,$groupinfo['id']));
		$memberinfo=$sql->fetch();
		$sql=null;
		if($memberinfo!=FALSE){
			echo '欢迎回来。';
		}else{
			$sql=$dbh->prepare("insert into groupmember (groupid,name,lastlogin,ip) values (?,?,?,?)");
			$sql->execute(array($groupinfo['id'],$member,$curtime,$ip));
			$sql=null;
			echo str_replace("#*","","欢迎加入“$group#*”组，用名字“$member#*”登陆可以同步你的消息。");
		}
	}else{
		$sql=$dbh->prepare("insert into groupinfo (name,ip) values (?,?)");
		$sql->execute(array($group,$ip));
		$sql=null;
		$groupid=$dbh->lastInsertId();
		$sql=$dbh->prepare("insert into groupmember (groupid,name,lastlogin,ip) values (?,?,?,?)");
		$sql->execute(array($groupid,$member,$curtime,$ip));
		$sql=null;
		echo str_replace("#*","","其他人可以加入新的“$group#*”组，用名字“$member#*”登陆可以同步你的消息。");
	}
	$dbh=null;
}
?>