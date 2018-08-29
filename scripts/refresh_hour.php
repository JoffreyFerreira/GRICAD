#!/usr/bin/php
<?php

include 'ressources.php';
$conn = init("imag");

$n = 24;

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

for ($i=0; $i < $n; $i++) { 
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
		$mesure[$id_machine] = array_fill(1, $n+1, 0);
	}

	$mesure[$id_machine] = queryGen($outlet, $pdu, $baie, $mesure[$id_machine], "h", "rPDU2OutletMeteredStatusPower");
}

foreach ($mesure as $id_machine => $value) {
	$sql = "INSERT INTO conso_daily VALUES (".$id_machine.", ";

	for ($k=1; $k < $n; $k++) { 
		$sql .= $mesure[$id_machine][$k].", ";
	}
	$sql .= $mesure[$id_machine][$n].") ";

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

	$query = array("bas" => array_fill(1, $n, 0), "haut" => array_fill(1, $n, 0), "humidite_1" => array_fill(1, $n, 0), "humidite_2" => array_fill(1, $n, 0)); 
	$baie = $row['nom_baie'];
	$alley = substr($baie, 0, 1);
	$rack = intval(substr($baie, 1, 1));

	if ($alley!="e" && $baie!="a4") {
		
		if ($rack==5 && $alley!="a") {
			$query["bas"] = queryGen(NULL, "1", $baie, $query["bas"], "h", "rPDU2SensorTempHumidityStatusTempC");
			$query["haut"] = queryGen(NULL, "2", $baie, $query["haut"], "h", "rPDU2SensorTempHumidityStatusTempC");
		} elseif ($rack==6 && $alley!="a") {
			$query["bas"] = queryGen(NULL, "3", $alley."5", $query["bas"], "h", "rPDU2SensorTempHumidityStatusTempC");
			$query["haut"] = queryGen(NULL, "4", $alley."5", $query["haut"], "h", "rPDU2SensorTempHumidityStatusTempC");	
		} else {
			$query["bas"] = queryGen(NULL, "3", $baie, $query["bas"], "h", "rPDU2SensorTempHumidityStatusTempC");	
			$query["haut"] = queryGen(NULL, "4", $baie, $query["haut"], "h", "rPDU2SensorTempHumidityStatusTempC");
		}

		if ($rack==1 || $rack==4 || $rack== 7) {
			
			$query['humidite_1'] = queryGen(NULL, "1", $baie, $query['humidite_1'], "h", "rPDU2SensorTempHumidityStatusRelativeHumidity");
			
			if ($baie == "a7" || $baie == "f1") {
			
				$query['humidite_2'] = queryGen(NULL, "2", $baie, $query['humidite_2'], "h", "rPDU2SensorTempHumidityStatusRelativeHumidity");
				$sql_humidite1 = "INSERT INTO humidity VALUES ('$baie', ";
				$sql_humidite2="";
				for ($k=1; $k < $n; $k++) { 
					$sql_humidite1 .= $query['humidite_1'][$k].", ";
					$sql_humidite2 .= $query['humidite_2'][$k].", ";
				}
				$sql_humidite1 .= $query['humidite_1'][$n].", ";
				$sql_humidite2 .= $query['humidite_2'][$n].") ";

				if ($conn->query($sql_humidite1.$sql_humidite2) === TRUE) {
					// echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql_humidite1.$sql_humidite2 . " " . $conn->error . "\n";
				}

			} else {

				$sql_humidite1 = "INSERT INTO humidity VALUES ('$baie', ";
				$sql_humidite2="";
				for ($k=1; $k < $n; $k++) { 
					$sql_humidite1 .= $query['humidite_1'][$k].", ";
					$sql_humidite2 .= "0, ";
				}
				$sql_humidite1 .= $query['humidite_1'][$n].", ";
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
		for ($k=1; $k < $n; $k++) { 
			$sql_temp .= $query['bas'][$k].", ";
			$sql_temp_2 .= $query['haut'][$k].", ";
		}
		$sql_temp .= $query['bas'][$n].", ";
		$sql_temp_2 .= $query['haut'][$n].") ";
		

		if ($conn->query($sql_temp.$sql_temp_2) === TRUE) {
			// echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql_temp . $sql_temp_2 . " " . $conn->error . "\n";
		}

	}	
}
$conn->close(); 
?>