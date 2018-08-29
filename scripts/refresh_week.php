#!/usr/bin/php
<?php
include 'ressources.php';
$conn = init("imag");

$n = 12;


// Creation de la table conso_weekly
$sql = "DROP TABLE IF EXISTS conso_weekly";
if ($conn->query($sql) === TRUE) {
    	// echo "Database created successfully\n";
} else {
	echo "Error deleting database: " . $conn->error . "\n";
}

$sql ="CREATE TABLE conso_weekly(
id_machine INTEGER not null,";

for ($i=0; $i < $n; $i++) { 
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
		$mesure[$id_machine] = array_fill(1, $n+1, 0);
	}

	$mesure[$id_machine] = queryGen($outlet, $pdu, $baie, $mesure[$id_machine], "w", "rPDU2OutletMeteredStatusPower");
}

foreach ($mesure as $id_machine => $value) {
	$sql = "INSERT INTO conso_weekly VALUES (".$id_machine.", ";

	for ($k=1; $k < $n; $k++) { 
		$sql .= $mesure[$id_machine][$k].", ";
	}

	$sql .= $mesure[$id_machine][$n].")";

	if ($conn->query($sql) === TRUE) {
	// echo "New record created successfully\n";
	} 
	else {
		echo "Error: " . $sql . " " . $conn->error . "\n";
	}
}

$conn->close();
?>