<html>
<head>
	<title>Profil type</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
</head>

<body>
	<div id="liste machines">
		<p>
			<?php
			echo "<h2>Liste des ".$_POST['type']."<h2/>";

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


			$sql = "select distinct id_machine, nom_baie, nom_modele, serveur, baie, reseaux, cluster, puissance_theorique, num_serie, nbr_U from machine where ".$_POST['type']."=1;";

			$result = $conn->query($sql);

			echo "<table><tr><td>ID machine<td/><td>Baie<td/><td>Modele<td/><td>Serveur<td/><td>Baie<td/><td>Réseaux<td/><td>Cluster<td/><td>Puissance théorique<td/><td>Numéro de série<td/><td>Nombre de U<td/><tr/>";

			if ($result->num_rows > 0) {
				while($row = $result->fetch_assoc()) {
					echo "<tr><td><a href=\"machine.php?id_machine=".$row['id_machine']."\">".$row['id_machine']."</a><td/><td>".$row['nom_baie']."<td/><td>".$row['nom_modele']."<td/><td>".$row['serveur']."<td/><td>".$row['baie']."<td/><td>".$row['reseaux']."<td/><td>".$row['cluster']."<td/><td>".$row['puissance_theorique']."<td/><td>".$row['num_serie']."<td/><td>".$row['nbr_U']."<td/><tr/>";
				}
				echo "<table/>";
			} else {
				echo "0 results";
			}

			?>
		</p>
	</div>
	<div>
		<h3>Puissance moyenne horaire par U de chaque type</h3>

		<?php

		$hour = date('H');
		echo "<table><tr><td>ID machine</td>";

		for ($i=0; $i < 24; $i++) { 

					// echo premiere ligne
			$tmp = strval((intval($hour)-$i)%24);
			if (intval($tmp)<0) {
				$tmp = strval(intval($tmp+24));
			} 
			echo "<td>$tmp h </td>";

					// requete sur la bd puis stock les valeurs
			$sql = "select distinct id_machine, (value".$i."/nbr_U) as val from machine natural join conso_daily where ".$type."=1;";
			$result_daily = $conn->query($sql);
			while ($row=$result_daily->fetch_assoc()) {
				$valeur[$row['id_machine']][$i] = $row["val"]; 					
			}
		}
		echo "</tr>";


				// echo tableau de valeur 
		foreach ($valeur as $id => $v) {
			echo "<tr><td>".$id."</td>";
			for ($i=0; $i < 24; $i++) { 
				echo "<td>".$v[$i]."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";

		echo "<h2>Moyenne et écart type</h2>";

		echo "<table><tr><td>ID machine</td><td>Moyenne</td><td>Ecart type</td></tr>";

		foreach ($valeur as $key => $t) {
			$moyenne[$key] = array_sum($t)/count($t);

			$et[$key] = 0;
			foreach ($t as $i){
				$et[$key] += pow(($i - $moyenne[$key]), 2);
			}

			$et[$key] = $et[$key] / (count($t) - 1);
			$et[$key] = pow($et[$key], 1/2);
			echo "<tr><td>".$key."</td><td>".$moyenne[$key]."</td><td>".$et[$key]."</td></tr>";
		}

		echo "</table>";

		?>
	</div>

	<div>
		<h2>Puissance moyenne hebdomadaire par U</h2>

		<?php

		echo "<table><tr><td>ID machine</td>";

		for ($i=0; $i < 11; $i++) { 

					// echo premiere ligne
			echo "<td>".strval($i+1)."semaine</td>";

					// requete sur la bd puis stock les valeurs
			$sql = "select distinct id_machine, (value".$i."/nbr_U) as val from machine natural join conso_weekly where ".$type."=1;";
			$result_weekly = $conn->query($sql);
			while ($row=$result_weekly->fetch_assoc()) {
				$valeur_weekly[$row['id_machine']][$i] = $row["val"]; 					
			}
		}
		echo "</tr>";


				// echo tableau de valeur 
		foreach ($valeur_weekly as $id => $v) {
			echo "<tr><td>".$id."</td>";
			for ($i=0; $i < 11; $i++) { 
				echo "<td>".$v[$i]."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";

		echo "<h2>Moyenne et écart type</h2>";

		echo "<table><tr><td>ID machine</td><td>Moyenne</td><td>Ecart type</td></tr>";

		foreach ($valeur_weekly as $key => $t) {
			$moyenne[$key] = array_sum($t)/count($t);

			$et[$key] = 0;
			foreach ($t as $i){
				$et[$key] += pow(($i - $moyenne[$key]), 2);
			}

			$et[$key] = $et[$key] / (count($t) - 1);
			$et[$key] = pow($et[$key], 1/2);
			echo "<tr><td>".$key."</td><td>".$moyenne[$key]."</td><td>".$et[$key]."</td></tr>";
		}

		echo "</table>";

		?>
	</div>
</body>
</html>