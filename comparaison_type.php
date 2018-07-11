<?php

$servername = "localhost";
$username = "admin";
$password = "admin";
$dbname = "imag";
$type = array('stockage', 'reseaux', 'serveur', 'aci', 'cluster');

				// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

				// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


foreach ($type as $t) {
	$valeur_h[$t] = array();
	$valeur_w[$t] = array();
	$moyenne_h[$t] = 0;
	$moyenne_w[$t] = 0;
	$moyenne_carre_h[$t] = 0;
	$moyenne_carre_w[$t] = 0;
	$val_null;
	for ($i=0; $i < 24; $i++) { 
		
		$sql = "SELECT avg(val) from (select distinct id_machine, (value".$i.") as val from machine natural join conso_daily where ".$t."=1 and value".$i.">50) as tbl;";
		$result = $conn->query($sql);
		$row = $result->fetch_assoc();
		if ($t == "cluster") {
			$valeur_h[$t][23-$i] = array("label" => 23-$i, "y" => ($row["avg(val)"]/14));

		} else{
			$valeur_h[$t][23-$i] = array("label" => 23-$i, "y" => $row["avg(val)"]);			
		}
		$moyenne_h[$t] += $row["avg(val)"]/24;
		$moyenne_carre_h[$t] += pow($row["avg(val)"], 2)/24;

		if ($i<11) {
			$sql = "SELECT avg(val) from (select distinct id_machine, (value".$i.") as val from machine natural join conso_weekly where ".$t."=1 and value".$i.">50) as tbl;";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			if ($t == "cluster") {
				$valeur_w[$t][10-$i] = array("label" => 10-$i, "y" => $row["avg(val)"]/14);

			} else {
				$valeur_w[$t][10-$i] = array("label" => 10-$i, "y" => $row["avg(val)"]);

			}
			$moyenne_w[$t] += $row["avg(val)"]/11;
			$moyenne_carre_w[$t] += pow($row["avg(val)"], 2)/11;
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
	<script>
		window.onload = function () {

			console.log(<?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?>);
			var chart_h = new CanvasJS.Chart("chartContainer_h", {
				animationEnabled: true,
				theme: "light2",
				legend:{
					cursor: "pointer",
					verticalAlign: "center",
					horizontalAlign: "right",
				},
				axisY:{
					minimum: 50,
				},
				data: [{
					type: "line",
					name: "stockage",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_h['stockage'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "serveur",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_h['serveur'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "cluster / 10",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_h['cluster'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "reseaux",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_h['reseaux'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "aci",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_h['aci'], JSON_NUMERIC_CHECK); ?>
				}]
			});

			var chart_w = new CanvasJS.Chart("chartContainer_w", {
				animationEnabled: true,
				theme: "light2",
				legend:{
					cursor: "pointer",
					verticalAlign: "center",
					horizontalAlign: "right",
				},
				data: [{
					type: "line",
					name: "stockage",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_w['stockage'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "serveur",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_w['serveur'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "cluster / 10",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_w['cluster'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "reseaux",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_w['reseaux'], JSON_NUMERIC_CHECK); ?>
				},{
					type: "line",
					name: "aci",
					indexLabel: "{y}",
					yValueFormatString: "#0.##",
					showInLegend: true,
					dataPoints: <?php echo json_encode($valeur_w['aci'], JSON_NUMERIC_CHECK); ?>
				}]
			});

			chart_h.render();
			chart_w.render();

		}
	</script>
</head>

<body>

	<h2>Puissance moyenne horaire de chaque type</h2>

	<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>
	<script src="canvasjs/canvasjs.min.js"></script>


	<h2>Moyenne et écart type pour chaques types</h2>

	<div>
		
		<?php
		echo "<table><tr><td>Type</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_h as $t => $value) {
			echo "<tr><td>".$t."</td><td>".$value."</td><td>".sqrt($ecart_type_h[$t])."</td></tr>";
		}
		echo "</table>";
		?>

	</div>

	<h2>Puissance moyenne hebdomadaire de chaque type</h2>

	<div id="chartContainer_w" style="width: 70%; height: 450px;display: inline-block;"></div>
	<script src="canvasjs/canvasjs.min.js" ></script>

	
	<h2>Moyenne et écart type pour chaques types</h2>

	<div>

		<?php
		echo "<table><tr><td>Type</td><td>Moyenne</td><td>Ecart type</td></tr>";
		foreach ($moyenne_w as $t => $value) {
			echo "<tr><td>".$t."</td><td>".$value."</td><td>".sqrt($ecart_type_w[$t])."</td></tr>";
		}
		echo "</table>";
		?>

	</div>
</body>
</html>