<?php

$baie_hd = array("b6", "c6", "d6", "e6", "f6");
$all_baie = array("a1", "a2", "a3", "a4", "a5", "a6", "a7",
"b1", "b2", "b3", "b4", "b5", "b6",
"c1", "c2", "c3", "c4", "c5", "c6",
"d1", "d2", "d3", "d4", "d5", "d6",
"e1", "e2", "e3", "e4", "e5", "e6",
"f1", "f2", "f3", "f4", "f5", "f6");

$type = array("serveur", "stockage", "cluster", "reseaux", "aci"); 

$ss_categorie = array(
	'serveur' => array('Virtualisation', 'Serveur classique vieux', 'Classique récents', 'Autres'),
	'stockage' => array('Stockage disque', 'SSD', 'Controleur', 'Autres'),
	'aci' => array('Hub', 'Switch', 'Routeur', 'Gateway', 'KVM', 'Autres'),
	'reseaux' => array('Hub', 'Switch', 'Routeur', 'Gateway', 'KVM', 'Autres'),
	'cluster' => array('Calcul', 'Big Data', 'Autres', 'Blade')
);

function init($dc){
	
	$servername = "localhost";
	$username = "admin";
	$password = "admin";

	$conn = new mysqli($servername, $username, $password);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error . "\n");
	}
	$sql = "USE " . $dc . ";";
	$conn->query($sql);

	return($conn);
}

function queryGen($outlet, $pdu, $baie, $tab, $time, $metric){
	
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';
	if($time=="w"){
		$n = 13;
	} else{
		$n = 25;
	}

	for ($i=1; $i < $n; $i++) { 

		switch ($metric) {
			case 'rPDU2OutletMeteredStatusPower':
			$query = "avg_over_time(" . $metric . "{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\", rPDU2OutletMeteredStatusIndex=\"".$outlet."\"}[1" . $time . "] offset " . $i . $time . ")";
			break;
			case 'rPDU2SensorTempHumidityStatusTempC':
			$query = "avg_over_time(rPDU2SensorTempHumidityStatusTempC{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\"}[1" . $time . "] offset " . $i . $time . ")";
			break;
			case 'rPDU2SensorTempHumidityStatusRelativeHumidity':
			$query = "avg_over_time(rPDU2SensorTempHumidityStatusRelativeHumidity{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\"}[1" . $time . "] offset " . $i . $time . ")";
			break;
			default:
			break;
		}
		
		$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
		$result_decode = json_decode($res_api);

		// If error
		if ($result_decode->{'status'}=='error') {
			echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
		}

		//If success
		else{
			if(!empty($result_decode->{'data'}->{'result'})){
				$tab[$i] += floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]); 
			}
		}
	}
	return $tab;

}

function string_to_id_U($id_U){
	$len=strlen($id_U);
	if($len==3 || $len==7){
		return substr($id_U, 1, 2);
	}
	else{
		return substr($id_U, 1, 1);
	}
}

function menuDeroulant($field, $table, $conn, $name){

	$sql = "SELECT " . $field . " as val FROM " . $table;
	$result = $conn->query($sql);

	if ($result->num_rows > 0){
		echo "<select name=\"". $name ."\" id=\"". $name ."\">";
		while($row = $result->fetch_assoc()) {
			echo "<option value=\"" . $row["val"] . "\">" . $row["val"] . "</option>";
		}
		echo("</select>");
	} 
	else {
		echo "0 results";
	}

}
?>