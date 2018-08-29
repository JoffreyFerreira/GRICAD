#!/usr/bin/php
<?php

include 'ressources.php';
$conn = init("imag");
$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
$port = 47807;

// Get outlet / machine / equipe
$sql = "SELECT * from outlet natural join machine natural join projet_equipe natural join conso_equipe;";
$result = $conn->query($sql);
$info = array();

while ($row = $result->fetch_assoc()) {

	$equipe = $row['nom_projet'];
	$outlet = $row['id_outlet'];
	$pdu = $row['id_PDU'];
	$baie = $row['nom_baie'];
	

	if (!isset($info[$equipe]['mesure'])) {
		$info[$equipe]['mesure'] = 0;
		$info[$equipe]['old_mesure'] = $row['conso_mois'];
		$info[$equipe]['mail'] = $row['mail'];
	}

	$info[$equipe]['mesure'] = query_gen_month($outlet, $pdu, $baie, $info[$equipe]['mesure']);
}

foreach ($info as $equipe => $value) {
	$sql = "UPDATE conso_equipe SET conso_mois_dernier=".$value['old_mesure'].", conso_mois=".$value['mesure']." where nom_projet=\"".$equipe."\"";

	if ($conn->query($sql) === TRUE) {
		$msg = $equipe. ";" .strval($value['mesure']-$value['old_mesure']). ";" .$value['mail'];
		$len = strlen($msg);
		socket_sendto($sock, $msg, $len, 0, '127.0.0.1', $port);
		//ajouter nombre de U
	} 
	else {
		echo "Error: " . $sql . " " . $conn->error . "\n";
	}
}

$sql = "SELECT * from baie_hd_equipe;";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	$query = "sum(pRealEnergy{rack=\"" . $row['nom_baie'] . "\"})";
	$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
	$result_decode = json_decode($res_api);

	// If error
	if ($result_decode->{'status'}=='error') {
 		echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
	}

	//If success
	$info[$row['nom_projet']]['mesure'] += intval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]);
}

$conn->close(); 
socket_close($sock);

function query_gen_month($outlet, $pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	$query = "rPDU2OutletMeteredStatusEnergy{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\", rPDU2OutletMeteredStatusIndex=\"".$outlet."\"}";
	$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
	$result_decode = json_decode($res_api);

	// If error
	if ($result_decode->{'status'}=='error') {
		echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
	}

	//If success
	$tab += intval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]); 

	return $tab;
}
?>
