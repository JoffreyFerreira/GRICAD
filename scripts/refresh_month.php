#!/usr/bin/php
<?php

$servername = "localhost";
$username = "admin";
$password = "admin";


// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error . "\n");
}

$sql = "USE imag;";
$conn->query($sql);

// Get outlet / machine / equipe
$sql = "SELECT * from outlet natural join machine natural join projet_equipe natural join conso_equipe;";
$result = $conn->query($sql);
$mesure = array();
$old_mesure = array();

while ($row = $result->fetch_assoc()) {

	$equipe = $row['nom_projet'];
	$outlet = $row['id_outlet'];
	$pdu = $row['id_PDU'];
	$baie = $row['nom_baie'];

	if (!isset($mesure[$equipe])) {
		$mesure[$equipe] = 0;
		$old_mesure[$equipe] = $row['conso_mois'];
	}

	$mesure[$equipe] = query_gen_month($outlet, $pdu, $baie, $mesure[$equipe]);
}

foreach ($mesure as $equipe => $value) {
	$sql = "UPDATE conso_equipe SET conso_mois_dernier=".$old_mesure[$equipe].", conso_mois=".$mesure[$equipe]." where nom_projet=\"".$equipe."\"";

	if ($conn->query($sql) === TRUE) {
	// echo "New record created successfully\n";
	} 
	else {
		echo "Error: " . $sql . " " . $conn->error . "\n";
	}
}

$conn->close(); 

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