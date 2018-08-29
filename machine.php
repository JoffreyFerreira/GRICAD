<?php

include 'scripts/ressources.php';
$conn = init("imag");

$id = $_GET['id_machine'];
$page = "machine.php?id_machine=".$id;

$sql = "SELECT * from machine natural join capacite natural join ss_categorie where id_machine=".$id.";";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

	$row = $result->fetch_assoc();

	$info = array(
		"nom_baie" => $row['nom_baie'],
		"nom_modele" => $row['nom_modele'],
		"serveur" => $row['serveur'],
		"stockage" => $row['stockage'],
		"reseaux" => $row['reseaux'],
		"cluster" => $row['cluster'],
		"nom_ss_categorie" => $row['nom_ss_categorie'],
		"puissance_theorique" => $row['puissance_theorique'],
		"num_serie" => $row['num_serie'],
		"nbr_U" => $row['nbr_U'],
		"id_U" => string_to_id_U($row['id_U']),
		"capaciteTo" => $row['capaciteTo'],
	);

	$sql = "SELECT * from machine natural join conso_daily where id_machine=".$id.";";
	$result = $conn->query($sql);
	$row_h = $result->fetch_assoc();
	$sql = "SELECT * from machine natural join conso_weekly where id_machine=".$id.";";
	$result = $conn->query($sql);
	$row_w = $result->fetch_assoc();

	$valeur_h = array();
	$valeur_w = array();

	for ($i=0; $i < 24; $i++){
		$valeur_h[$i] = array("label" => $i, "y" => $row_h["value".(23-$i)]);
		if ($i<12){
			$valeur_w[$i] = array("label" => $i, "y" => $row_w["value".(11-$i)]);
		}
	}

	?>

	<html>
	<head>
		<title>Détail machine</title>
		<link rel="stylesheet" type="text/css" href="style/style.css">
		<script src="canvasjs/canvasjs.min.js"></script>

		<script src="graphe.js" type="text/javascript"></script>

		<script type="text/javascript">

			window.onerror = function(msg, url, linenumber) {
				alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
				return true;
			}

			window.onload = function () {

				var chart_h = new CanvasJS.Chart("chartContainer_h",{
					
					axisY:{
						minimum: 50,
						title: "W/U",
					},
					axisX:{
						title: "Heures",
					},
					theme: "light2",
					
					data:[{
						type: "line",
						dataPoints: <?php echo json_encode($valeur_h, JSON_NUMERIC_CHECK); ?> 
					}]
				});

				chart_h.render();

				var chart_w = new CanvasJS.Chart("chartContainer_w",{
					axisY:{
						minimum: 50,
						title: "W/U",
					},
					axisX:{
						title: "Semaines",
					},
					theme: "light2",
					data:[{
						type: "line",
						dataPoints: <?php echo json_encode($valeur_w, JSON_NUMERIC_CHECK); ?> 
					}]
				});

				chart_w.render();
			}

		</script>
	</head>

	<body>



		<div id="liste machine">
			<h2>Information sur la machine</h2>

			<?php


			echo "<table><tr><td>ID machine</td><td>Baie</td><td>Modele</td><td>Serveur</td><td>Stockage</td><td>Réseaux</td><td>Cluster</td><td>Sous catégorie</td><td>Puissance théorique</td><td>Numéro de série</td><td>Nombre de U</td><td>U</td><td>capacité en To</td><tr/><tr><td>".$id."</td>";

			foreach ($info as $value) {
				echo "<td>".$value."</td>";
			}
			echo "</tr><table/>";

		} else {
			echo "0 results";
		}

		?>

	</div>

	<h2>Puissance moyenne horaire</h2>
	<div id="chartContainer_h" style="width: 90%; height: 450px;display: inline-block;"></div>

	<h2>Puissance moyenne hebdomadaire</h2>
	<div id="chartContainer_w" style="width: 90%; height: 450px;display: inline-block;"></div>

	<form method="post" action=<?php echo $page;?>>
		<p>
			<p><input type="text" name="capaciteTo" /></p>
			<?php
			if (isset($_POST['capaciteTo'])) {
				$sql = "UPDATE capacite SET capaciteTo=".$_POST['capaciteTo']." where id_machine=\"".$id."\"";

				if ($conn->query($sql) === TRUE) {
					// echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql . " " . $conn->error . "\n";
				}

			}
			?>
			<input type="submit" value="Modifier capacité de stockage"/>
		</p>
	</form>

	<form method="post" action=<?php echo $page;?>>
		<p>
			<select name="ss_categorie" id="ss_categorie">

				<?php

				$ss_categorie = array('Stockage disque', 'SSD', 'Controleur', 'Hub', 'Switch', 'KVM', 'Routeur', 'Gateway', 'Virtualisation', 'Serveur classique vieux', 'Classique récents', 'Blade', 'Calcul', 'Big Data', 'Autres');

				foreach ($ss_categorie as $value) {
					echo "<option value=\"$value\">$value</option>";
				}

				echo "</select><input type=\"submit\" value=\"Modifier sous catégorie\"/>";

				if (isset($_POST['ss_categorie'])) {
					echo "Sous catégorie mis à jour";
					$sql = "UPDATE ss_categorie SET nom_ss_categorie=\"".$_POST['ss_categorie']."\" where nom_modele=\"".$info['nom_modele']."\"";

					if ($conn->query($sql) === TRUE) {
					// echo "New record created successfully\n";
					} else {
						echo "Error: " . $sql . " " . $conn->error . "\n";
					}

				}
				?>
			</p>
		</form>	

	</body>
	</html>