#!/usr/bin/php
<?php

$servername = "localhost";
$username = "admin";
$password = "admin";


$nbr_hour = 25;

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error . "\n");
}

$sql = "USE imag;";
$conn->query($sql);

$table = array("conso_daily", "temperature", "humidity");

foreach ($table as $v) {
	$sql = "DROP TABLE IF EXISTS ".$v;
	if ($conn->query($sql) === TRUE) {
    	// echo "Database created successfully\n";
	} else {
		echo "Error deleting database: " . $conn->error . "\n";
	}
}

$sql ="CREATE TABLE conso_daily(id_machine INTEGER not null,";
$sql_temp="CREATE TABLE temperature(nom_baie VARCHAR(10) not null,";
$sql_humidite1="CREATE TABLE humidity(nom_baie VARCHAR(10) not null,";
$sql_temp_2="";
$sql_humidite2="";

for ($i=0; $i < 24; $i++) { 
	$sql .= "value".$i." INTEGER,";
	$sql_temp .= "temp_bas".$i." FLOAT, ";
	$sql_temp_2 .= "temp_haut".$i." FLOAT, ";
	$sql_humidite1 .= "humidite1_".$i." FLOAT, ";
	$sql_humidite2 .= "humidite2_".$i." FLOAT, ";
}

$sql .= "primary key (id_machine))";
if ($conn->query($sql) === TRUE) {
	echo "Table conso_daily created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}


if ($conn->query($sql_temp.$sql_temp_2."primary key (nom_baie))") === TRUE) {
	echo "Table temperature created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

if ($conn->query($sql_humidite1.$sql_humidite2."primary key (nom_baie))") === TRUE) {
	echo "Table humidity created successfully\n";
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
		$mesure[$id_machine] = array_fill(1, 25, 0);
	}

	$mesure[$id_machine] = query_gen_hour($outlet, $pdu, $baie, $mesure[$id_machine]);
}

foreach ($mesure as $id_machine => $value) {
	$sql = "INSERT INTO conso_daily VALUES (".$id_machine.", ";

	for ($k=1; $k < 24; $k++) { 
		$sql .= $mesure[$id_machine][$k].", ";
	}
	$sql .= $mesure[$id_machine][24].") ";

	if ($conn->query($sql) === TRUE) {
	// echo "New record created successfully\n";
	} 
	else {
		echo "Error: " . $sql . " " . $conn->error . "\n";
	}
}

$sql = "SELECT nom_baie FROM baie;";
$result = $conn->query($sql);
$temp = array();
while ($row = $result->fetch_assoc()) {

	$query = array("bas" => array_fill(1, 24, 0), "haut" => array_fill(1, 24, 0), "humidite_1" => array_fill(1, 24, 0), "humidite_2" => array_fill(1, 24, 0)); 
	$baie = $row['nom_baie'];
	$alley = substr($baie, 0, 1);
	$rack = intval(substr($baie, 1, 1));

	if ($alley!="e" && $baie!="a4") {
		
		if ($rack==5 && $alley!="a") {
			$query["bas"] = query_gen_temp("1", $baie, $query["bas"]);
			$query["haut"] = query_gen_temp("2", $baie, $query["haut"]);
		} elseif ($rack==6 && $alley!="a") {
			$query["bas"] = query_gen_temp("3", $alley."5", $query["bas"]);
			$query["haut"] = query_gen_temp("4", $alley."5", $query["haut"]);	
		} else {
			$query["bas"] = query_gen_temp("3", $baie, $query["bas"]);	
			$query["haut"] = query_gen_temp("4", $baie, $query["haut"]);
		}

		if ($rack==1 || $rack==4 || $rack== 7) {
			$query['humidite_1'] = query_gen_humidity("1", $baie, $query['humidite_1']);
			if ($baie == "a7" || $baie == "f1") {
				$query['humidite_2'] = query_gen_humidity("2", $baie, $query['humidite_2']);

				$sql_humidite1 = "INSERT INTO humidity VALUES ('$baie', ";
				$sql_humidite2="";

				for ($k=1; $k < 24; $k++) { 
					$sql_humidite1 .= $query['humidite_1'][$k].", ";
					$sql_humidite2 .= $query['humidite_2'][$k].", ";
				}

				$sql_humidite1 .= $query['humidite_1'][24].", ";
				$sql_humidite2 .= $query['humidite_2'][24].") ";

				if ($conn->query($sql_humidite1.$sql_humidite2) === TRUE) {
					// echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql_humidite1.$sql_humidite2 . " " . $conn->error . "\n";
				}

			} else {

				$sql_humidite1 = "INSERT INTO humidity VALUES ('$baie', ";
				$sql_humidite2="";

				for ($k=1; $k < 24; $k++) { 
					$sql_humidite1 .= $query['humidite_1'][$k].", ";
					$sql_humidite2 .= "0, ";
				}

				$sql_humidite1 .= $query['humidite_1'][24].", ";
				$sql_humidite2 .= "0) ";

				if ($conn->query($sql_humidite1.$sql_humidite2) === TRUE) {
						// echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql_humidite1.$sql_humidite2 . " " . $conn->error . "\n";
				}
			}
		}

		$sql_temp = "INSERT INTO temperature VALUES ('$baie', ";
		$sql_temp_2 ="";

		for ($k=1; $k < 24; $k++) { 
			$sql_temp .= $query['bas'][$k].", ";
			$sql_temp_2 .= $query['haut'][$k].", ";

		}

		$sql_temp .= $query['bas'][24].", ";
		$sql_temp_2 .= $query['haut'][24].") ";
		

		if ($conn->query($sql_temp.$sql_temp_2) === TRUE) {
			// echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql_temp . $sql_temp_2 . " " . $conn->error . "\n";
		}
	}
	
}

$conn->close(); 

function query_gen_temp($pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	for ($i=1; $i < 25; $i++) { 

		$query = "avg_over_time(rPDU2SensorTempHumidityStatusTempC{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\"}[1h] offset ".$i."h)";
		$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
		$result_decode = json_decode($res_api);

		// If error
		if ($result_decode->{'status'}=='error') {
			echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
		}

		//If success
		else{
			if(!empty($result_decode->{'data'}->{'result'})){
				$tab[$i] = floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1])/10; 
			}
		}
	}
	return $tab;
}

function query_gen_humidity($pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	for ($i=1; $i < 25; $i++) { 

		$query = "avg_over_time(rPDU2SensorTempHumidityStatusRelativeHumidity{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\"}[1h] offset ".$i."h)";
		$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
		$result_decode = json_decode($res_api);

		// If error
		if ($result_decode->{'status'}=='error') {
			echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
		}

		//If success
		else{
			if(!empty($result_decode->{'data'}->{'result'})){
				$tab[$i] = floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]); 
			}
		}
	}
	return $tab;
}

function query_gen_hour($outlet, $pdu, $baie, $tab){

	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';

	for ($i=1; $i < 25; $i++) { 

		$query = "avg_over_time(rPDU2OutletMeteredStatusPower{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\", rPDU2OutletMeteredStatusIndex=\"".$outlet."\"}[1h] offset ".$i."h)";
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
?>