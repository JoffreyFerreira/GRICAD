<?php
include 'scripts/ressources.php';
$conn = init("imag");

$equipe = $_POST['equipe'];

// requete pour liste machine
$sql = "select distinct * from machine natural join capacite natural join ss_categorie where nom_projet=\"".$equipe."\";";
$result = $conn->query($sql);

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
	}
} else {
	$info=NULL;
}

foreach ($info as $t => $value) {
	$valeur_h[$t] = array();
	$valeur_w[$t] = array();
	$moyenne_h[$t] = 0;
	$moyenne_w[$t] = 0;
	$moyenne_carre_h[$t] = 0;
	$moyenne_carre_w[$t] = 0;
	$val_null;

	// collecte moyenne horaire
	for ($i=0; $i < 24; $i++) {
		$x = 23-$i; 
		
		$sql = "SELECT value".$x." as val FROM machine natural join conso_daily WHERE id_machine=".$t.";";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		$valeur_h[$t][] = array("label" => $i, "y" => $row["val"]);
		$moyenne_h[$t] += $row["val"]/24;
		$moyenne_carre_h[$t] += pow($row["val"], 2)/24;

		// collecte moyenne hebdomadaire
		if ($x<11) {
			$sql = "SELECT value".$x." as val from machine natural join conso_weekly where id_machine=".$t.";";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			$valeur_w[$t][] = array("label" => $i-12, "y" => $row["val"]);
			$moyenne_w[$t] += $row["val"]/11;
			$moyenne_carre_w[$t] += pow($row["val"], 2)/11;
		}
	}

	$ecart_type_h[$t] = $moyenne_carre_h[$t] - pow($moyenne_h[$t], 2);
	$ecart_type_w[$t] = $moyenne_carre_w[$t] - pow($moyenne_w[$t], 2);
}

$sql = "SELECT distinct * from conso_equipe WHERE nom_projet=\"".$equipe."\"";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$conso_mois = intval($row['conso_mois'])-intval($row['conso_mois_dernier']);

?>

<html>
<head>
	<title>Profil équipe</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
	
	<script src="canvasjs/canvasjs.min.js"></script>
	<script src="graphe.js" type="text/javascript"></script>
	
	<script type="text/javascript">

		window.onerror = function(msg, url, linenumber) {
			alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
			return true;
		}

		window.onload = function () {

			var chart_h = genGraph(<?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?>, 50, "W", "Heures");
			console.log(chart_h);
			chart_h = new CanvasJS.Chart("chartContainer_h", chart_h);
			chart_h.render();

			var chart_w = genGraph(<?php echo json_encode($valeur_w, JSON_NUMERIC_CHECK); ?>, 50, "W", "Semaines");
			chart_w = new CanvasJS.Chart("chartContainer_w", chart_w);
			chart_w.render();

		}

	</script>

</head>

<body>
	<p id="demo"></p>

	<h2>Liste des appareils <?php echo $equipe?></h2>
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
	
	<h2>Puissance moyenne horaire</h2>
	<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>

	<h2>Moyenne et écart type</h2>
	
	<div>
		<?php
		echo "<table><tr><td>ID U</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_h as $t => $value) {
			if($value!=0){
				echo "<tr><td>".$t."</td><td>".strval(floor(10*$value)/10)."</td><td>". strval(floor(10*sqrt($ecart_type_h[$t]))/10)."</td></tr>";
			}
		}
		echo "</table>";
		?>
	</div>

	<div>

		<h2>Puissance moyenne hebdomadaire</h2>
		<div id="chartContainer_w" style="width: 90%; height: 450px;display: inline-block;"></div>

	</div>

	<div>
		<h2>Moyenne et écart type</h2>
		
		<?php
		echo "<table><tr><td>ID U</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_w as $t => $value) {
			if($value!=0){
				echo "<tr><td>".$t."</td><td>".strval(floor(10*$value)/10)."</td><td>". strval(floor(10*sqrt($ecart_type_w[$t]))/10)."</td></tr>";
			}
		}
		echo "</table>";
		?>

		
	</div>

	<h2>Coût consommation mensuelle</h2>
	<div>
		<p>Cette équipe a consommé <?php echo "$conso_mois";?> kW le mois dernier, soit <?php echo $conso_mois/=100;?> euros</p>
	</div>
</body>
</html>