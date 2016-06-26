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
$dbh = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
if($act=='login'){
	if(!isset($_REQUEST['group'])||!isset($_REQUEST['member']))exit;
	$group=$_REQUEST['group'];
	$member=$_REQUEST['member'];
	if($group==''||$member=='')exit;
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
			$sql=$dbh->prepare("update groupmember set lastlogin=? where id=?");
			$sql->execute(array($curtime,$memberinfo['id']));
			$sql=null;
			echo '1:欢迎回来。';
		}else{
			$sql=$dbh->prepare("insert into groupmember (groupid,name,lastlogin,ip) values (?,?,?,?)");
			$sql->execute(array($groupinfo['id'],$member,$curtime,$ip));
			$sql=null;
			echo str_replace("#*","","1:欢迎加入“$group#*”组，用名字“$member#*”登陆可以同步你的消息。");
		}
	}else{
		$sql=$dbh->prepare("insert into groupinfo (name,ip,startdate) values (?,?)");
		$sql->execute(array($group,$ip));
		$sql=null;
		$groupid=$dbh->lastInsertId();
		$sql=$dbh->prepare("insert into groupmember (groupid,name,lastlogin,ip) values (?,?,?,?)");
		$sql->execute(array($groupid,$member,$curtime,$ip));
		$sql=null;
		echo str_replace("#*","","1:其他人可以加入新的“$group#*”组，用名字“$member#*”登陆可以同步你的消息。");
	}
}else if($act=='getdate'){
	if(!isset($_REQUEST['group']))exit;
	$group=$_REQUEST['group'];
	if($group=='')exit;
	$sql=$dbh->prepare("select * from groupinfo where name=?");
	$sql->execute(array($group));
	$groupinfo=$sql->fetch();
	$sql=null;
	if($groupinfo!=FALSE&&isset($groupinfo['startdate'])){
		$date=$groupinfo['startdate'];
		echo "1:$date";
	}else{
		echo str_replace("#*","","0:");
	}
}else if($act=='setdate'){
	if(!isset($_REQUEST['group'])||!isset($_REQUEST['value']))exit;
	$group=$_REQUEST['group'];
	$value=$_REQUEST['value'];
	if($group==''||$value=='')exit;
	$sql=$dbh->prepare("update groupinfo set startdate=? where name=?");
	$sql->execute(array($value,$group));
	$sql=null;
}else if($act=='getmsg'){
	if(!isset($_REQUEST['group'])||!isset($_REQUEST['member']))exit;
	$group=$_REQUEST['group'];
	$member=$_REQUEST['member'];
	if($group==''||$member=='')exit;
	$sql=$dbh->prepare("select groupmember.id as mid from groupmember,groupinfo where groupmember.name=? and groupinfo.id=groupmember.groupid and groupinfo.name=?");
	$sql->execute(array($member,$group));
	$memberinfo=$sql->fetch();
	$sql=null;
	if($memberinfo!=FALSE){
		$mid=$memberinfo['mid'];
		$sql=$dbh->prepare("SELECT message.id,message.content,groupmember.name,messagestat.id AS statid FROM message,groupmember,messagestat WHERE message.senderid=groupmember.id AND message.senderid!=? AND message.id=messagestat.messageid AND messagestat.status=0 AND messagestat.memberid=?");
		$sql->execute(array($mid,$mid));
		$msg=$sql->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($msg);
		$sql=null;
		$sql=$dbh->prepare("UPDATE messagestat SET status=1 WHERE memberid=? AND status=0");
		$sql->execute(array($mid));
		$sql=null;
	}
}else if($act=='sendmsg'){
	if(!isset($_REQUEST['group'])||!isset($_REQUEST['member'])||!isset($_REQUEST['content']))exit;
	$group=$_REQUEST['group'];
	$member=$_REQUEST['member'];
	$content=$_REQUEST['content'];
	if($group==''||$member=='')exit;
	$sql=$dbh->prepare("select groupmember.id as `mid`,groupinfo.id AS `gid` from groupmember,groupinfo where groupmember.name=? and groupinfo.id=groupmember.groupid and groupinfo.name=?");
	$sql->execute(array($member,$group));
	$memberinfo=$sql->fetch();
	$sql=null;
	if($memberinfo!=FALSE){
		$mid=$memberinfo['mid'];
		$gid=$memberinfo['gid'];
		$sql=$dbh->prepare("INSERT into message (groupid,senderid,content,ip) values(?,?,?,?)");
		$sql->execute(array($gid,$mid,$content,$ip));
		$sql=null;
		$msgid=$dbh->lastInsertId();
		$sql=$dbh->prepare("SELECT id FROM groupmember WHERE groupid=?");
		$sql->execute(array($gid));
		while($row=$sql->fetch()){
			if($row['id']==$mid)continue;
			$sql2=$dbh->prepare("INSERT into messagestat (messageid,memberid,status) values(?,?,0)");
			$sql2->execute(array($msgid,$row['id']));
			$sql2=null;
		}
		$sql=null;
	}
}
$dbh=null;
?>