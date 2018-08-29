#!/usr/bin/php
<?php

$dc = "imag";
include 'ressources.php';
$conn = init($dc);

$table = array('capacite', 'outlet', 'machine', 'baie', 'projet_equipe', 'rattachement_admin', 'conso_equipe', 'conso_daily', 'conso_weekly', 'ss_categorie', 'baie_hd_equipe', 'mail_equipe');

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


// sql to create table projet_equipe
$sql = "CREATE TABLE mail_equipe(
nom_projet VARCHAR(30) not null,
mail VARCHAR(255),
primary key (nom_projet)
)";


if ($conn->query($sql) === TRUE) {
	echo "Table projet_equipe created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

// sql to create table projet_equipe
$sql = "CREATE TABLE conso_equipe(
nom_projet VARCHAR(30) not null,
conso_mois_dernier INTEGER,
conso_mois INTEGER,
primary key (nom_projet)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table conso_equipe created successfully\n";
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

// sql to create table baie
$sql = "CREATE TABLE baie_hd_equipe (
nom_baie VARCHAR(10) not null,
nom_projet VARCHAR(30),
primary key (nom_baie)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table baie created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}


// sql to create table machine
$sql = "CREATE TABLE machine(
nom_projet                  VARCHAR(30)     not null,
id_machine                  INTEGER         not null,
nom_baie                    VARCHAR(10)     not null,
nom_modele                  VARCHAR(255)     not null,
serveur                     INT             not null,
stockage                    INT             not null,
reseaux                     INT             not null,
cluster                     INT             not null,
aci 						INT 			not null,
puissance_theorique         VARCHAR(10),
num_serie                   VARCHAR(255),
nbr_U                       INT,
id_U                        VARCHAR(7),
primary key (id_machine),
foreign key (nom_baie) references baie(nom_baie),
foreign key (nom_projet) references projet_equipe(nom_projet)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table machine created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}


$sql = "CREATE TABLE capacite(
	id_machine				INTEGER 		not null,
	capaciteTo 				INTEGER,
	primary key (id_machine)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table capacite created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

$sql = "CREATE TABLE ss_categorie(
	nom_modele				VARCHAR(255) 	not null,
	nom_ss_categorie 		VARCHAR(255),
	primary key (nom_modele)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table capacite created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}


$sql = "CREATE TABLE outlet(
id 							INTEGER 		not null,
id_machine					INTEGER 		not null,
id_outlet                   INTEGER 		not null,
id_PDU                      INTEGER 		not null,
primary key(id),
foreign key(id_machine) references machine(id_machine)
)";

if ($conn->query($sql) === TRUE) {
	echo "Table outlet created successfully\n";
} else {
	echo "Error creating table: " . $conn->error . "\n";
}

// API call
$res = shell_exec("curl -H \"api-key: xxx\" 'https://gricad.univ-grenoble-alpes.fr/export_soumissions/webform/07264df1-aacc-4b3a-bcaa-fe462fc6d05a/submission's   ");
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

		$sql = "INSERT IGNORE INTO conso_equipe (nom_projet, conso_mois, conso_mois_dernier) VALUES ('$projet', 0, 0)";
		if ($conn->query($sql) === TRUE) {
		    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		$sql = "INSERT INTO machine(nom_projet, id_machine, nom_baie, nom_modele, serveur, stockage, reseaux, cluster, aci, nbr_U, puissance_theorique, num_serie, id_U)
		VALUES('$projet', '$id', '$baie', '$modele', '$type[1]', '$type[2]', '$type[3]', '$type[4]', 0, '$nbr_U', '$puissance_theorique', '$num_serie', '$id_U')";
		if ($conn->query($sql) === TRUE) {
				    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		$sql = "INSERT INTO capacite(id_machine, capaciteTo) VALUES ('$id', 0)";
		if ($conn->query($sql) === TRUE) {
				    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		$sql = "INSERT IGNORE INTO ss_categorie(nom_modele) VALUES ('$modele')";
		if ($conn->query($sql) === TRUE) {
				    // echo "New record created successfully\n";
		} else {
			echo "Error: " . $sql . " " . $conn->error . "\n";
		}

		for ($j=34; $j < 42; $j++) { 
			if (is_object($tab[$i]->{'data'}->{$j}->{'values'})) {
				$tab[$i]->{'data'}->{$j}->{'values'} = get_object_vars($tab[$i]->{'data'}->{$j}->{'values'});
			}
			$outlet=$tab[$i]->{'data'}->{$j}->{'values'}[0];
			$pdu = ($j-33)%4 +1;
			if($outlet!=""){
				$outlet = value_to_int($outlet);
				$sql = "INSERT INTO outlet(id, id_machine, id_outlet, id_PDU)
				VALUES('$compt', '$id', '$outlet', '$pdu')";
				if ($conn->query($sql) === TRUE) {
				    // echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql . " " . $conn->error . "\n";
				}

				// insertion daily / call api
				$compt++;
			}
		}
	}
}

foreach ($all_baie as $nom_baie) {
	$sql = "INSERT INTO baie_hd_equipe(nom_baie) VALUES ('$nom_baie')";
	$conn->query($sql);
	$sql = "INSERT INTO baie(nom_baie) VALUES ('$nom_baie')";
	$conn->query($sql);
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
