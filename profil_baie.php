<?php
$servername = "localhost";
$username = "admin";
$password = "admin";
$dbname = "imag";
$baie= $_POST['rack'];
$taux_remplissage = 0.0;
$alley = substr($baie, 0, 1);
$rack = intval(substr($baie, 1, 1));

					// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

					// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


// requete pour liste machine
$sql = "select distinct * from machine natural join capacite natural join ss_categorie where nom_baie=\"".$baie."\";";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$info[id_to_int($row['id_U'])] = array(
			"id_machine" => $row['id_machine'], 
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
		$taux_remplissage += $row['nbr_U'];
	}
} else {
	$info=NULL;
}
$query = array("bas" => array(), "haut" => array(), "humidite_1" => array(), "humidite_2" => array()); 
$baie_h = 0;
$baie_w = 0;
$baie_et_h = 0;
$baie_et_w = 0;

foreach ($info as $t => $value) {
	$id_machine = $value['id_machine'];
	$valeur_h[$t] = array();
	$valeur_w[$t] = array();
	$moyenne_h[$t] = 0;
	$moyenne_w[$t] = 0;
	$moyenne_carre_h[$t] = 0;
	$moyenne_carre_w[$t] = 0;
	$val_null;
	for ($i=0; $i < 24; $i++) { 
		$x = 23-$i;
		$sql = "SELECT value".$x." as val from machine natural join conso_daily where id_machine=".$id_machine." and value".$x.">50;";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		$valeur_h[$t][$i] = array("label" => $i, "y" => $row["val"]);
		$moyenne_h[$t] += $row["val"]/24;
		$baie_h += $row["val"]/24;
		$moyenne_carre_h[$t] += pow($row["val"], 2)/24;

		$sql = "SELECT humidite1_".$x.", humidite2_".$x." from humidity where nom_baie=\"".$baie."\";";
		$result = $conn->query($sql);
		if ($result!=false) {
			$row = $result->fetch_assoc();
			$query['humidite_1'][$i] = array("lable" => $i, "y" => $row["humidite1_".$x]);
			$query['humidite_2'][$i] = array("lable" => $i, "y" => $row["humidite2_".$x]);
		}


		$sql = "SELECT temp_bas".$x.", temp_haut".$x." from temperature where nom_baie=\"".$baie."\";";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		$query['bas'][$i] = array("lable" => $i, "y" => $row["temp_bas".$x]);
		$query['haut'][$i] = array("lable" => $i, "y" => $row["temp_haut".$x]);

		if ($x<12) {
			$sql = "SELECT value".$x." as val from machine natural join conso_weekly where id_machine=".$id_machine." and value".$x.">50;";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			$valeur_w[$t][] = array("label" => $i-11, "y" => $row["val"]);
			$moyenne_w[$t] += $row["val"]/12;
			$moyenne_carre_w[$t] += pow($row["val"] , 2)/12;
			$baie_w += $row['val']/12;
		}
	}

	$ecart_type_h[$t] = $moyenne_carre_h[$t] - pow($moyenne_h[$t], 2);
	$baie_et_h += $ecart_type_h[$t];
	$ecart_type_w[$t] = $moyenne_carre_w[$t] - pow($moyenne_w[$t], 2);
	$baie_et_w += $ecart_type_w[$t];
}
$taux_remplissage /=0.42;


ksort($info);
ksort($moyenne_h);
ksort($moyenne_w);

function id_to_int($str){
	$length = strlen($str);
	switch ($length) {
		case 3 || 7:
		return(substr($str, 1, 2));
		case 2 || 5 || 6:
		return(substr($str, 1, 2));
		default:
		return("X");
	}
}

?>

<html>
<head>
	<title>Profil baie</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<script>

		window.onerror = function(msg, url, linenumber) {
			alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
			return true;
		}

		window.onload = function () {

			var tab = <?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?>;
			var tab_w = <?php echo json_encode($valeur_w, JSON_NUMERIC_CHECK); ?>;
			var data_list = [];
			var data_list_w = [];

			console.log(tab);

			for (id in tab) {
				data_list.push({
					toolTipContent : "U : "+id+", y: {y} ",
					type: "line",
					name: String(id),
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: tab[id]
				});
			}

			var chart_h = new CanvasJS.Chart("chartContainer_h", {
				// animationEnabled: true,
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
					toolTipContent : "U : "+id+", y: {y} ",
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

			var chart_temp = new CanvasJS.Chart("chartContainer_temp", {
				animationEnabled: true,
				theme: "light2",
				legend:{
					cursor: "pointer",
					verticalAlign: "center",
					horizontalAlign: "right",
				},
				axisY:{
					minimum: 20,
				},
				data: [{
					type: "line",
					name: "T bas",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($query['bas'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "T haut",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($query['haut'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "Humidité PDU 1",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($query['humidite_1'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "Humidité PDU 2",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($query['humidite_2'], JSON_NUMERIC_CHECK); ?>
				}]
			});

			chart_h.render();
			chart_w.render();
			chart_temp.render();

		}
	</script>
</head>

<body>
	<p id="demo"></p>

	

	<div id="liste machines">
		<h2>Liste des appareils dans la baie <?php echo $baie?></h2>
			<p>Taux de remplissage <?php echo $taux_remplissage;?> %</p>


			<?php

			echo "<table><tr><td>ID U</td><td>ID machine</td><td>Baie</td><td>Modele</td><td>Serveur</td><td>Stockage</td><td>Réseaux</td><td>Cluster</td><td>ACI</td><td>Sous catégorie</td><td>Puissance théorique</td><td>Numéro de série</td><td>Nombre de U</td><td>Capacité en To</td><tr/>";
			foreach ($info as $key => $value) {
				echo "<tr><td>".$key."</td><td><a href=machine.php?id_machine=".$value['id_machine'].">".$value['id_machine']."</a></td>";
				foreach ($value as $k => $v) {
					if(strcmp("id_machine", $k)){echo "<td>".$v."</td>";}
				}
				echo "</tr>";
			}
			echo "</table>";
			?>

		</div>

		<h2>Température et hygrométrie moyenne hebdomadaire</h2>
		<div id="chartContainer_temp" style="width: 90%; height: 450px;display: inline-block;"></div>
		<script src="canvasjs/canvasjs.min.js"></script>

		<h2>Puissance par U (moyenne horaire)</h2>
		<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>
		<script src="canvasjs/canvasjs.min.js"></script>

		<h2>Moyenne et écart type</h2>

		<div>
			<?php
			echo "<table><tr><td>ID machine</td><td>Moyenne</td><td>Ecart type</td></tr>";
			foreach ($moyenne_h as $t => $value) {
				echo "<tr><td>".$t."</td><td>".$value."</td><td>".sqrt($ecart_type_h[$t])."</td></tr>";
			}
			echo "<tr><td>Baie</td><td>" . $baie_h . "</td><td>" . sqrt($baie_et_h) . "</td></tr>";
			echo "</table>";
			?>
		</div>

		<div>

			<h2>Puissance par U (moyenne hebdomadaire)</h2>
			<div id="chartContainer_w" style="width: 90%; height: 450px;display: inline-block;"></div>
			<script src="canvasjs/canvasjs.min.js"></script>

		</div>

		<div>
			<h2>Moyenne et écart type</h2>

			<?php
			echo "<table><tr><td>ID machine</td><td>Moyenne</td><td>Ecart type</td></tr>";
			foreach ($moyenne_w as $t => $value) {
				echo "<tr><td>".$t."</td><td>".$value."</td><td>".sqrt($ecart_type_w[$t])."</td></tr>";
			}
			echo "<tr><td>Baie</td><td>" . $baie_w . "</td><td>" . sqrt($baie_et_w) . "</td></tr>";
			echo "</table>";
			?>


		</div>
	</body>
	</html>