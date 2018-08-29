<?php

include 'scripts/ressources.php';
$conn = init("imag");

foreach ($type as $t) {
	$valeur_h[$t] = array();
	$valeur_w[$t] = array();
	$moyenne_h[$t] = 0;
	$moyenne_w[$t] = 0;
	$moyenne_carre_h[$t] = 0;
	$moyenne_carre_w[$t] = 0;
	$val_null;
	for ($i=0; $i < 24; $i++) { 
		
		$sql = "SELECT avg(value". $i .") as val, avg(nbr_U) as moyU from machine natural join conso_daily where ". $t ."=1 and value". $i .">50 and nbr_U<10";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		if($row['moyU']!=NULL){
			$valeur_h[$t][23-$i] = array("label" => 23-$i, "y" => $row["val"]/$row['moyU']);			
			$moyenne_h[$t] += $row["val"]/24/$row['moyU'];
			$moyenne_carre_h[$t] += pow($row["val"]/$row['moyU'], 2)/24;
		}

		if ($i<11) {
			$sql = "SELECT avg(value". $i .") as val, avg(nbr_U) as moyU from machine natural join conso_weekly where ". $t ."=1 and value". $i .">50 and nbr_U<15";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			if($row['moyU']!=NULL){
				$valeur_w[$t][10-$i] = array("label" => 10-$i, "y" => $row["val"]/$row['moyU']);
				$moyenne_w[$t] += $row["val"]/11/$row['moyU'];
				$moyenne_carre_w[$t] += pow($row["val"]/$row['moyU'], 2)/11;
			}
		}
	}

	$ecart_type_h[$t] = $moyenne_carre_h[$t] - pow($moyenne_h[$t], 2);
	$ecart_type_w[$t] = $moyenne_carre_w[$t] - pow($moyenne_w[$t], 2);
}

foreach ($type as $t) {
	ksort($valeur_w[$t]);	
	ksort($valeur_h[$t]);	
}

?>

<!DOCTYPE HTML>
<html>
<head>

	<title>Comparatif des types</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">	
	<script src="canvasjs/canvasjs.min.js"></script>
	<script src="graphe.js" type="text/javascript"></script>
	
	<script type="text/javascript">

		window.onerror = function(msg, url, linenumber) {
			alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
			return true;
		}

		window.onload = function () {

			var chart_h = genGraph(<?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?>, 40, "W/U", "Heures");
			console.log(chart_h);
			chart_h = new CanvasJS.Chart("chartContainer_h", chart_h);
			chart_h.render();

			var chart_w = genGraph(<?php echo json_encode($valeur_w, JSON_NUMERIC_CHECK); ?>, 40, "W/U", "Semaines");
			chart_w = new CanvasJS.Chart("chartContainer_w", chart_w);
			chart_w.render();

		}
		

	</script>

</head>

<body>

	<h2>Puissance moyenne horaire de chaque type</h2>

	<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>

	<h2>Moyenne et écart type pour chaques types</h2>

	<div>
		
		<?php
		echo "<table><tr><td>Type</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_h as $t => $value) {
			if($value!=0){
				echo "<tr><td>".$t."</td><td>".strval(floor(10*$value)/10)."</td><td>". strval(floor(10*sqrt($ecart_type_h[$t]))/10)."</td></tr>";
			}
		}
		echo "</table>";
		?>

	</div>

	<h2>Puissance moyenne hebdomadaire de chaque type</h2>

	<div id="chartContainer_w" style="width: 70%; height: 450px;display: inline-block;"></div>
	
	<h2>Moyenne et écart type pour chaques types</h2>

	<div>

		<?php
		echo "<table><tr><td>Type</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_w as $t => $value) {
			if($value!=0){
				echo "<tr><td>".$t."</td><td>".strval(floor(10*$value)/10)."</td><td>". strval(floor(10*sqrt($ecart_type_w[$t]))/10)."</td></tr>";
			}
		}
		echo "</table>";

		?>

	</div>
</body>
</html>