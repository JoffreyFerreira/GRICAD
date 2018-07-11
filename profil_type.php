<?php
$servername = "localhost";
$username = "admin";
$password = "admin";
$dbname = "imag";
$type = $_POST['type'];
						// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

						// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

	// requete pour liste machine
$sql = "select distinct * from machine natural join capacite natural join ss_categorie where $type=1;";
$result = $conn->query($sql);

$liste_id = array();

$ss_categorie = array(
	'serveur' => array('Virtualisation', 'Serveur classique vieux', 'Classique récents', 'Autres'),
	'stockage' => array('Stockage disque', 'SSD', 'Controleur', 'Autres'),
	'aci' => array('Hub', 'Switch', 'Routeur', 'Gateway', 'KVM', 'Autres'),
	'reseaux' => array('Hub', 'Switch', 'Routeur', 'Gateway', 'KVM', 'Autres'),
	'cluster' => array('Calcul', 'Big Data', 'Autres', 'Blade')
);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$info[$row['id_machine']] = array(
			"nom_baie" => $row['nom_baie'],
			"nom_modele" => $row['nom_modele'],
			"serveur" => $row['serveur'],
			"stockage" => $row['stockage'],
			"reseaux" => $row['reseaux'],
			"cluster" => $row['cluster'],
			"aci" => $row['aci'],
			"nom_ss_categorie" => $row['nom_ss_categorie'],
			"puissance_theorique" => $row['puissance_theorique'],
			"num_serie" => $row['num_serie'],
			"nbr_U" => $row['nbr_U'],
			"capaciteTo" => $row['capaciteTo']
		);
		$liste_id[] = $row['id_machine'];
	}
} else {
	$info=NULL;
}

foreach ($ss_categorie[$type] as $t) {
	$valeur_h[$t] = array();
	$valeur_w[$t] = array();
	$moyenne_h[$t] = 0;
	$moyenne_w[$t] = 0;
	$moyenne_carre_h[$t] = 0;
	$moyenne_carre_w[$t] = 0;
	$min_h[$t] = 999999;
	$min_w[$t] = 999999;
	$max_h[$t] = 0; 	
	$max_w[$t] = 0; 	
	$val_null;
	for ($i=0; $i < 24; $i++) { 
		
		$sql = "SELECT avg(value".$i.") as val, avg(nbr_U) as U, min(value".$i.") as min, max(value".$i.") as max from machine natural join conso_daily natural join ss_categorie where nom_ss_categorie=\"$t\" and $type=1 and value".$i.">50;";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		if($row['U']!=NULL){
			$valeur_h[$t][23-$i] = array("label" => 23-$i, "y" => $row["val"]/$row['U']);
			$moyenne_h[$t] += $row["val"]/24/$row['U'];
			$moyenne_carre_h[$t] += pow($row["val"]/$row['U'], 2)/24;			
			
			if ($min_h[$t] >= $row["min"]/$row['U']) {
				$min_h[$t] = $row["min"]/$row['U'];
			}
			if ($max_h[$t] <= $row["max"]/$row['U']) {
				$max_h[$t] = $row["max"]/$row['U'];
			}
		}
		
		


		if ($i<11) {
			$sql = "SELECT avg(value".$i.") as val, avg(nbr_U) as U, min(value".$i.") as min, max(value".$i.") as max from machine natural join conso_weekly natural join ss_categorie where nom_ss_categorie=\"$t\" and $type=1 and value".$i.">50;";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			if($row['U']!=NULL){
				$valeur_w[$t][10-$i] = array("label" => 10-$i, "y" => $row["val"]/$row['U']);
				$moyenne_w[$t] += $row["val"]/11/$row['U'];
				$moyenne_carre_w[$t] += pow($row["val"]/$row['U'], 2)/11;
				if ($min_w[$t] >= $row["min"]/$row['U']) {
					$min_w[$t] = $row["min"]/$row['U'];
				}
				if ($max_w[$t] <= $row["max"]/$row['U']) {
					$max_w[$t] = $row["max"]/$row['U'];
				}
			}
			
			
			

		}
	}

	$ecart_type_h[$t] = $moyenne_carre_h[$t] - pow($moyenne_h[$t], 2);
	$ecart_type_w[$t] = $moyenne_carre_w[$t] - pow($moyenne_w[$t], 2);

	
}

// var_dump($valeur_w);

foreach ($ss_categorie[$type] as $t) {
	ksort($valeur_w[$t]);
	ksort($valeur_h[$t]);		
}

?>

<html>
<head>
	<title>Profil type</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<script>

		window.onerror = function(msg, url, linenumber) {
			alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
			return true;
		}

		window.onload = function () {

			var liste_id = [];
			var liste_id = <?php echo json_encode($ss_categorie[$type], JSON_NUMERIC_CHECK); ?>;
			var tab = <?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?>;
			var tab_w = <?php echo json_encode($valeur_w, JSON_NUMERIC_CHECK); ?>;
			var data_list = [];
			var data_list_w = [];

			console.log(tab);

			for (id in tab) {
				data_list.push({
					toolTipContent : "id : "+id+", y: {y} ",
					type: "line",
					name: String(id),
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: tab[id]
				});
			}

			var chart_h = new CanvasJS.Chart("chartContainer_h", {
				animationEnabled: true,
				theme: "light2",
				axisY:{
					minimum: 50,
				},
				legend:{
					cursor: "pointer",
					verticalAlign: "center",
					horizontalAlign: "right",
				},
				data : data_list
			});



			for (id in tab_w) {
				data_list_w.push({
					toolTipContent : "id : "+id+", y: {y} ",
					type: "line",
					name: String(id),
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: tab_w[id]
				});
			}			

			var chart_w = new CanvasJS.Chart("chartContainer_w", {
				animationEnabled: true,
				theme: "light2",
				axisY:{
					minimum: 50,
				},
				legend:{
					cursor: "pointer",
					verticalAlign: "center",
					horizontalAlign: "right",
				},
				data : data_list_w
			});

			chart_h.render();
			chart_w.render();

		}
	</script>
</head>

<body>
	<p id="demo"></p>

	<h2>Liste des appareils <?php echo $_POST['type']?></h2>
		<div id="liste machines">


			<?php

			echo "<table><tr><td>ID machine</td><td>Baie</td><td>Modele</td><td>Serveur</td><td>Stockage</td><td>Réseaux</td><td>Cluster</td><td>ACI</td><td>Sous catégorie</td><td>Puissance théorique</td><td>Numéro de série</td><td>Nombre de U</td><td>Capacite en To</td><tr/>";
			foreach ($info as $key => $value) {
				echo "<tr><td><a href=machine.php?id_machine=".$key." >".$key."</a></td>";
				foreach ($value as $v) {
					echo "<td>".$v."</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
			?>

		</div>
		
		<h2>Puissance par U (moyenne faite sur 1h)</h2>
		<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>
		<script src="canvasjs/canvasjs.min.js"></script>

		<h2>Moyenne et écart type</h2>
		
		<div>
			<?php
			echo "<table><tr><td>ID machine</td><td>Minimum</td><td>Moyenne</td><td>Maximum</td></tr>";
			foreach ($moyenne_h as $t => $value) {
				echo "<tr><td>".$t."</td><td>" . $min_h[$t] . "</td><td>".$value."</td><td>" . $max_h[$t] . "</td></tr>";
			}
			echo "</table>";

			$total_h = 0;
			for ($i=0; $i < 24; $i++) { 
				$sql = "select sum(value" . $i . ") as sum from machine natural join conso_daily where " . $type . "=1;";
				$result = $conn->query($sql);
				if ($result!=false) {
					$row = $result->fetch_assoc();
					$total_h += intval($row['sum'])/24;
				}
			}	
			?>

			<p>Moyenne de la consommation totale : <?php echo($total_h)?></p>

		</div>

		<div>

			<h2>Puissance par U (moyenne hebdomadaire)</h2>
			<div id="chartContainer_w" style="width: 90%; height: 450px;display: inline-block;"></div>
			<script src="canvasjs/canvasjs.min.js"></script>

		</div>

		<h2>Moyenne et écart type</h2>
		<div>

			<?php
			echo "<table><tr><td>ID machine</td><td>Minimum</td><td>Moyenne</td><td>Maximum</td></tr>";
			foreach ($moyenne_w as $t => $value) {
				echo "<tr><td>".$t."</td><td>" . $min_w[$t] . "</td><td>".$value."</td><td>" . $max_w[$t] . "</td></tr>";
			}
			echo "</table>";

			$total_w = 0;
			for ($i=0; $i < 12; $i++) { 
				$sql = "select sum(value" . $i . ") as sum from machine natural join conso_weekly where " . $type . "=1;";
				$result = $conn->query($sql);
				if ($result!=false) {
					$row = $result->fetch_assoc();
					$total_w += intval($row['sum'])/12;
				}
			}	
			?>

			<p>Moyenne de la consommation totale : <?php echo($total_w)?></p>

		</div>

	</body>
	</html>