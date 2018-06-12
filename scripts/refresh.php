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
if ($conn->query($sql) === TRUE) {
	echo "Table rattachement_admin created successfully\n";
} else {
	echo "Error creating table: " . $conn->error."\n";
}

$table = array('machine', 'baie', 'projet_equipe', 'rattachement_admin', 'conso_daily', 'conso_weekly');

foreach ($table as $t) {
	$sql = "DROP TABLE IF EXISTS ".$t;
	if ($conn->query($sql) === TRUE) {
    	// echo "Database created successfully\n";
	} else {
		echo "Error deleting database: " . $conn->error . "\n";
	}
}

// sql to create table rattachement_admin
$sql = "CREATE TABLE rattachement_admin(
nom_admin VARCHAR(15) not null,
primary key (nom_admin)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table rattachement_admin created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

// sql to create table projet_equipe
$sql = "CREATE TABLE projet_equipe(
nom_projet VARCHAR(30) not null,
nom_admin VARCHAR(15) not null,
primary key (nom_projet),
foreign key (nom_admin) references rattachement_admin(nom_admin)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table projet_equipe created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

// sql to create table baie
$sql = "CREATE TABLE baie (
nom_baie VARCHAR(10) not null,
primary key (nom_baie)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table baie created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

// sql to create table machine
$sql = "CREATE TABLE machine(
id                          INTEGER         not null,
nom_projet                  VARCHAR(30)     not null,
id_machine                  INTEGER         not null,
nom_baie                    VARCHAR(10)     not null,
id_outlet                   INTEGER,
id_PDU                      INTEGER,
nom_modele                  VARCHAR(255)     not null,
serveur                     INT             not null,
baie                        INT             not null,
reseaux                     INT             not null,
cluster                     INT             not null,
puissance_theorique         VARCHAR(10),
num_serie                   VARCHAR(255),
nbr_U                       INT,
id_U                        VARCHAR(7),
primary key (id),
foreign key (nom_baie) references baie(nom_baie),
foreign key (nom_projet) references projet_equipe(nom_projet)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table machine created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

$sql ="CREATE TABLE conso_daily(
id_machine INTEGER not null,";

for ($i=0; $i < 24; $i++) { 
	$sql .= "value".$i." INTEGER,";
}
$sql .= "primary key (id_machine))";
if ($conn->query($sql) === TRUE) {
	echo "Table conso_daily created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
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


// API call
$res = shell_exec("curl -H \"api-key: \" 'https://gricad.univ-grenoble-alpes.fr/export_soumissions/webform/07264df1-aacc-4b3a-bcaa-fe462fc6d05a/submission's   ");
$tab = json_decode($res);


// dc : 31  - rack : 32 - admin : 4 - equipe : 3 - puissance : 13 - nbr U : 12 - modele : 6 - num serie : 9 - type : 5 -  .
$mesure = array();
$compt=1;
for ($i=0; $i < count($tab); $i++) { 
	
	$dc=$tab[$i]->{'data'}->{'31'}->{'values'}[0];
	if($dc=="imag"){

		$baie = $tab[$i]->{'data'}->{'32'}->{'values'}[0];
		$admin = strval(id_to_nom($tab[$i]->{'data'}->{'4'}->{'values'}[0]));
		$projet = strval($tab[$i]->{'data'}->{'3'}->{'values'}[0]);
		$modele = strval($tab[$i]->{'data'}->{'6'}->{'values'}[0]);
		$type = array();
		$type = int_to_array_type($tab[$i]->{'data'}->{'5'}->{'values'});
		$nbr_U = $tab[$i]->{'data'}->{'12'}->{'values'}[0];
		$num_serie = $tab[$i]->{'data'}->{'9'}->{'values'}[0];
		$puissance_theorique = $tab[$i]->{'data'}->{'13'}->{'values'}[0];
		$id=$i+1;
		$id_U = $tab[$i]->{'data'}->{'42'}->{'values'}[0];

		if (!isset($mesure[$id])) {
			$mesure[$id] = array_fill(1, 25, 0);
		}
		if (!isset($mesure_week[$id])) {
			$mesure_week[$id] = array_fill(1, 13, 0);
		}


		$sql = "INSERT IGNORE INTO baie (nom_baie) VALUES ('$baie')";
		if ($conn->query($sql) === TRUE) {
		    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}
		

		$sql = "INSERT IGNORE INTO rattachement_admin (nom_admin) VALUES ('$admin')";
		if ($conn->query($sql) === TRUE) {
		    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}


		$sql = "INSERT IGNORE INTO projet_equipe (nom_admin, nom_projet) VALUES ('$admin', '$projet')";
		if ($conn->query($sql) === TRUE) {
		    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		for ($j=34; $j < 42; $j++) { 
			$outlet=$tab[$i]->{'data'}->{$j}->{'values'}[0];
			$pdu = ($j-33)%4 +1;
			if($outlet!=""){
				$outlet = value_to_int($outlet);
				$sql = "INSERT INTO machine(id, nom_projet, id_machine, nom_baie, id_outlet, id_PDU, nom_modele, serveur, baie, reseaux, cluster, nbr_U, puissance_theorique, num_serie, id_U)
				VALUES('$compt', '$projet', '$id', '$baie', '$outlet', '$pdu', '$modele', '$type[1]', '$type[2]', '$type[3]', '$type[4]', '$nbr_U', '$puissance_theorique', '$num_serie', '$id_U')";
				if ($conn->query($sql) === TRUE) {
				    // echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql . " " . $conn->error . "\n";
				}

				// insertion daily / call api
				$mesure[$id] = query_gen($outlet, $pdu, $baie, $mesure[$id]);
				$mesure_week[$id] = query_gen_week($outlet, $pdu, $baie, $mesure_week[$id]);
				$compt++;
			}
		}

		$sql = "INSERT INTO conso_daily VALUES (".$id.", ";
		for ($k=1; $k < 24; $k++) { 
			$sql .= $mesure[$id][$k].", ";
		}
		$sql .= $mesure[$id][24].")";
		if ($conn->query($sql) === TRUE) {
		    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		$sql = "INSERT INTO conso_weekly VALUES (".$id.", ";
		for ($k=1; $k < 12; $k++) { 
			$sql .= $mesure_week[$id][$k].", ";
		}
		$sql .= $mesure_week[$id][12].")";
		if ($conn->query($sql) === TRUE) {
		    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}
	}
}


$conn->close(); 

function query_gen($outlet, $pdu, $baie, $tab){
	
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
			// var_dump($result_decode);
			$tab[$i] += floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]); 
		}
	}
	return $tab;
}

function query_gen_week($outlet, $pdu, $baie, $tab){
	
	//URL construction
	$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';
	
	for ($i=1; $i < 12; $i++) { 

		$query = "avg_over_time(rPDU2OutletMeteredStatusPower{instance=\"imag-dc-pdu-".$baie."-".$pdu.".u-ga.fr\", rPDU2OutletMeteredStatusIndex=\"".$outlet."\"}[1w] offset ".$i."w)";
		$res_api = shell_exec('curl -k -s '.$url.urlencode($query));
		$result_decode = json_decode($res_api);

		// If error
		if ($result_decode->{'status'}=='error') {
			echo "QUERY : ".urldecode($query)." ERROR ON QUERY : ".var_dump($result_decode);
		}

		//If success
		else{
			$tab[$i] += floatval($result_decode->{'data'}->{'result'}[0]->{'value'}[1]); 
		}
	}
	return $tab;
}

function int_to_array_type($values){
	$res = array_fill(1, 4, 0);
	foreach ($values as $t) {
		$res[intval($t)]=1;
	}
	return $res;
}

function value_to_int($value){
	return substr($value, 2);
}

function id_to_nom($id_admin){
	switch ($id_admin) {
		case '1':
		return "uga";
		case '2':
		return "inp";
		case '3':
		return "comue";
		case '4':
		return "autre";
		case '5':
		return "cnrs";
		default:
		break;
	}
	return $id_admin;

}
?>
