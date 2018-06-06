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

$table = array('machine', 'baie', 'projet_equipe', 'rattachement_admin');

foreach ($table as $t) {
	$sql = "DROP TABLE IF EXISTS ".$t;
	if ($conn->query($sql) === TRUE) {
    	echo "Database created successfully\n";
	} else {
    	echo "Error creating database: " . $conn->error . "\n";
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
    nom_modele                  VARCHAR(40)     not null,
    serveur                     INT             not null,
    baie                        INT             not null,
    reseaux                     INT             not null,
    cluster                     INT             not null,
    puissance_theorique         VARCHAR(10),
    num_serie                   VARCHAR(40),
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


// API call
$res = shell_exec("curl -H \"api-key: \" 'https://gricad.univ-grenoble-alpes.fr/export_soumissions/webform/07264df1-aacc-4b3a-bcaa-fe462fc6d05a/submission's   ");
$tab = json_decode($res);


// dc : 31  - rack : 32 - admin : 4 - equipe : 3 - puissance : 13 - nbr U : 12 - modele : 6 - num serie : 9 - type : 5 -  
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


		$sql = "INSERT IGNORE INTO baie (nom_baie) VALUES ('$baie')";
		if ($conn->query($sql) === TRUE) {
		    echo "New record created successfully\n";
		} else {
		    echo "Error: " . $sql . " " . $conn->error . "\n";
		}
		

		$sql = "INSERT IGNORE INTO rattachement_admin (nom_admin) VALUES ('$admin')";
		if ($conn->query($sql) === TRUE) {
		    echo "New record created successfully\n";
		} else {
		    echo "Error: " . $sql . " " . $conn->error . "\n";
		}


		$sql = "INSERT IGNORE INTO projet_equipe (nom_admin, nom_projet) VALUES ('$admin', '$projet')";
		if ($conn->query($sql) === TRUE) {
		    echo "New record created successfully\n";
		} else {
		    echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		for ($j=34; $j < 42; $j++) { 
			$outlet=$tab[$i]->{'data'}->{$j}->{'values'}[0];
			$pdu = ($j-33)%4;
			if($outlet!=""){
				$outlet = value_to_int($outlet);
				$sql = "INSERT INTO machine(id, nom_projet, id_machine, nom_baie, id_outlet, id_PDU, nom_modele, serveur, baie, reseaux, cluster, nbr_U, puissance_theorique, num_serie, id_U)
		    	VALUES('$compt', '$projet', '$id', '$baie', '$outlet', '$pdu', '$modele', '$type[1]', '$type[2]', '$type[3]', '$type[4]', '$nbr_U', '$puissance_theorique', '$num_serie', '$id_U')";
				if ($conn->query($sql) === TRUE) {
				    echo "New record created successfully\n";
				} else {
				    echo "Error: " . $sql . " " . $conn->error . "\n";
				}
				$compt++;
			}
		}
	}
}


$conn->close(); 

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
