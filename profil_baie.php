<?php

include 'scripts/ressources.php';
$conn = init("imag");

$baie= $_POST['rack'];
$taux_remplissage = 0.0;
$alley = substr($baie, 0, 1);
$rack = intval(substr($baie, 1, 1));

// requete pour liste machine
$sql = "select distinct * from machine natural join capacite natural join ss_categorie where nom_baie=\"".$baie."\";";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		$info[string_to_id_U($row['id_U'])] = array(
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
$temp = array("bas" => array(), "haut" => array());
$hum = array("humidite_1" => array(), "humidite_2" => array());
$baie_h = 0;
$baie_w = 0;
$baie_et_h = 0;
$baie_et_w = 0;

for ($i=0; $i < 24; $i++) { 
	$x = 23-$i;
	$sql = "SELECT humidite1_".$x.", humidite2_".$x." from humidity where nom_baie=\"".$baie."\";";
	$result = $conn->query($sql);
	if ($result!=false) {
		$row = $result->fetch_assoc();
		$hum['humidite_1'][$i] = array("label" => $i, "y" => $row["humidite1_".$x]);
		$hum['humidite_2'][$i] = array("label" => $i, "y" => $row["humidite2_".$x]);
	}


	$sql = "SELECT temp_bas".$x.", temp_haut".$x." from temperature where nom_baie=\"".$baie."\";";
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$temp['bas'][$i] = array("label" => $i, "y" => ($row["temp_bas".$x]/10));
	$temp['haut'][$i] = array("label" => $i, "y" => ($row["temp_haut".$x]/10));
}

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
			$hum['humidite_1'][$i] = array("label" => $i, "y" => $row["humidite1_".$x]);
			$hum['humidite_2'][$i] = array("label" => $i, "y" => $row["humidite2_".$x]);
		}


		$sql = "SELECT temp_bas".$x.", temp_haut".$x." from temperature where nom_baie=\"".$baie."\";";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		$temp['bas'][$i] = array("label" => $i, "y" => ($row["temp_bas".$x]/10));
		$temp['haut'][$i] = array("label" => $i, "y" => ($row["temp_haut".$x]/10));
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

?>

<html>
<head>
	<title>Profil baie</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
	<script src="canvasjs/canvasjs.min.js"></script>
	<script src="graphe.js" type="text/javascript"></script>
	
	<script type="text/javascript">

		window.onerror = function(msg, url, linenumber) {
			alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
			return true;
		}

		window.onload = function () {

			var baie_hum = ['a1', 'a7', 'b1', 'b4', 'c1', 'c4','d1', 'd4','e1', 'e4','f1', 'f4'];

			var chart_h = genGraph(<?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?>, 50, "W", "Heures");
			console.log(chart_h);
			chart_h = new CanvasJS.Chart("chartContainer_h", chart_h);
			chart_h.render();

			var chart_w = genGraph(<?php echo json_encode($valeur_w, JSON_NUMERIC_CHECK); ?>, 50, "W", "Semaines");
			chart_w = new CanvasJS.Chart("chartContainer_w", chart_w);
			chart_w.render();

			var chart_temp = genGraph(<?php echo json_encode($temp, JSON_NUMERIC_CHECK); ?>, 15, "Température", "Heures");
			chart_temp = new CanvasJS.Chart("chartContainer_temp", chart_temp);
			chart_temp.render();

			var baie = '<?php echo $baie;?>'; 

			if(baie_hum.indexOf(baie)>0){
				var chart_hum = genGraph(<?php echo json_encode($hum, JSON_NUMERIC_CHECK); ?>, 15, "%", "Heures");
				chart_hum = new CanvasJS.Chart("chartContainer_hum", chart_hum);
				chart_hum.render();		
			}
		}

	</script>
</head>

<body>

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

	<?php

	$baie_hum = array('a1', 'a7', 'b1', 'b4', 'c1', 'c4','d1', 'd4','e1', 'e4','f1', 'f4');
	if(in_array($baie, $baie_hum)){
		echo "<br><div id=\"chartContainer_hum\" style=\"width: 90%; height: 450px;display: inline-block;\"></div>";
	}
	?>

	<h2>Puissance moyenne horaire</h2>

	<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>

	<h2>Moyenne et écart type</h2>

	<div>
		<?php
		echo "<table><tr><td>ID machine</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_h as $t => $value) {
			if($value!=0){
				echo "<tr><td>".$t."</td><td>".strval(floor(10*$value)/10)."</td><td>". strval(floor(10*sqrt($ecart_type_h[$t]))/10)."</td></tr>";
			}
		}
		echo "<tr><td>Baie</td><td>" . strval(floor(10*$baie_h)/10) . "</td><td>" . strval(floor(10*sqrt($baie_et_h))/10) . "</td></tr>";
		echo "</table>";
		?>
	</div>

	<div>

		<h2>Puissance moyenne hebdomadaire</h2>
		<div id="chartContainer_w" style="width: 90%; height: 450px;display: inline-block;"></div>
		<script src="canvasjs/canvasjs.min.js"></script>

	</div>

	<div>
		<h2>Moyenne et écart type</h2>

		<?php
		echo "<table><tr><td>ID machine</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_w as $t => $value) {
			if($value!=0){
				echo "<tr><td>".$t."</td><td>".strval(floor(10*$value)/10)."</td><td>". strval(floor(10*sqrt($ecart_type_w[$t]))/10)."</td></tr>";
			}
		}
		echo "<tr><td>Baie</td><td>" . strval(floor(10*$baie_w)/10) . "</td><td>" . strval(floor(10*sqrt($baie_et_w))/10) . "</td></tr>";
		echo "</table>";
		?>


	</div>
</body>
</html>