#!/usr/bin/php
<?php

$servername = "localhost";
$username = "admin";
$password = "admin";


// $nbr_semaine = 12;

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error . "\n");
}

$sql = "USE imag;";
$conn->query($sql);

$sql = "DROP TABLE IF EXISTS conso_weekly";
if ($conn->query($sql) === TRUE) {
    	// echo "Database created successfully\n";
} else {
	echo "Error deleting database: " . $conn->error . "\n";
}

$sql ="CREATE TABLE conso_weekly(
id_machine INTEGER not null,";

for ($i=0; $i < 12; $i++) { 
	$sql .= "value".$i." INTEGER,";
}
$sql .= "primary key (id_machine))";
if ($conn->query($sql) === TRUE) {
	echo "Table conso_weekly created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}


// Get outlet / machine
$sql = "SELECT * from outlet natural join machine;";
$result = $conn->query($sql);
$mesure = array();

while ($row = $result->fetch_assoc()) {

	$id_machine = $row['id_machine'];
	$outlet = $row['id_outlet'];
	$pdu = $row['id_PDU'];
	$baie = $row['nom_baie'];

	if (!isset($mesure[$id_machine])) {
		$mesure[$id_machine] = array_fill(1, 13, 0);
	}

	$mesure[$id_machine] = query_gen_week($outlet, $pdu, $baie, $mesure[$id_machine]);
}

foreach ($mesure as $id_machine => $value) {
	$sql = "INSERT INTO conso_weekly VALUES (".$id_machine.", ";

	for ($k=1; $k < 12; $k++) { 
		$sql .= $mesure[$id_machine][$k].", ";
	}

	$sql .= $mesure[$id_machine][12].")";

	if ($conn->query($sql) === TRUE) {
	// echo "New record created successfully\n";
	} 
	else {
		echo "Error: " . $sql . " " . $conn->error . "\n";
	}
}

$conn->close(); 

function query_gen_week($outlet, $pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	for ($i=1; $i < 13; $i++) { 

		$query = "avg_over_time(rPDU2OutletMeteredStatusPower{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\", rPDU2OutletMeteredStatusIndex=\"".$outlet."\"}[1w] offset ".$i."w)";
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

function query_gen_temp($pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	for ($i=1; $i < 25; $i++) { 

		$query = "avg_over_time(rPDU2SensorTempHumidityStatusTempC{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\"}[1w] offset ".$i."w)";
		$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
		$result_decode = json_decode($res_api);

		// If error
		if ($result_decode->{'status'}=='error') {
			echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
		}

		//If success
		else{
			$tab[$i] = floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1])/10; 
		}
	}
	return $tab;
}

function query_gen_humidity($pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	for ($i=1; $i < 25; $i++) { 

		$query = "avg_over_time(rPDU2SensorTempHumidityStatusRelativeHumidity{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\"}[1w] offset ".$i."w)";
		$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
		$result_decode = json_decode($res_api);

		// If error
		if ($result_decode->{'status'}=='error') {
			echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
		}

		//If success
		else{
			if($result_decode->{'data'}->{'result'}[0]->{'value'}[1]!=-1){
				$tab[$i] = floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]); 
			}
		}
	}
	return $tab;
}
?>