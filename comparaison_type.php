<html>
<head>
	<title>Profil équipe</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
</head>

<body>
	<div>
		<h2>Puissance moyenne horaire par U de chaque type</h2>

		<?php
		$servername = "localhost";
		$username = "admin";
		$password = "admin";
		$dbname = "imag";
		$type = array('baie', 'reseaux', 'serveur', 'cluster');

				// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);

				// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}


		$hour = date('H');
		echo "<table><tr><td>Type</td>";


		for ($i=0; $i < 23; $i++) { 

			$tmp = strval((intval($hour)-$i)%24);
			if (intval($tmp)<0) {
				$tmp = strval(intval($tmp+24));
			} 
			echo "<td>$tmp h </td>";

			foreach ($type as $t) {
				$sql[$i][$t] = "SELECT avg(val) from (select distinct id_machine, (value".$i."/nbr_U) as val from machine natural join conso_daily where ".$t."=1) as tbl;";
				$result[$i][$t] = $conn->query($sql[$i][$t]);
			}
		}

		echo "</tr>";

		foreach ($type as $t) {
			echo "<tr><td>$t</td>";
			for ($i=1; $i < 23; $i++) {
				$row = $result[$i][$t]->fetch_assoc();
				$valeur[$t][$i] = $row["avg(val)"]; 
				echo "<td>".$row["avg(val)"]."</td>";	
			}
			echo "</tr>";
		}

		echo "<tr><td>Tous types</td>";
		for ($i=0; $i < 23; $i++) { 
			$sql_total[$i] = "SELECT avg(val) from (select distinct id_machine, (value".$i."/nbr_U) as val from machine natural join conso_daily) as tbl;";
			$result_total[$i] = $conn->query($sql_total[$i]);
			$row = $result_total[$i]->fetch_assoc();
			$valeur['Total'][$i] = $row["avg(val)"]; 
			echo "<td>".$row["avg(val)"]."</td>";
		}
		echo "</tr></table>";

		echo "<h2>Moyenne et écart type pour chaques types</h2>";

		echo "<table><tr><td>Type</td><td>Moyenne</td><td>Ecart type</td></tr>";

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
		<h2>Puissance moyenne hebdomadaire par U de chaque type</h2>

		<?php

		for ($i=0; $i < 11; $i++) {

			echo "<td>".strval($i+1)."semaine</td>";

			foreach ($type as $t) {
				$sql[$i][$t] = "SELECT avg(val) from (select distinct id_machine, (value".$i."/nbr_U) as val from machine natural join conso_weekly where ".$t."=1) as tbl;";
				$result[$i][$t] = $conn->query($sql[$i][$t]);
			}
		}

		echo "<table><tr><td>Type</td>";

		foreach ($type as $t) {
			echo "<tr><td>$t</td>";
			for ($i=0; $i < 11; $i++) {
				$row = $result[$i][$t]->fetch_assoc();
				$valeur[$t][$i] = $row["avg(val)"]; 
				echo "<td>".$row["avg(val)"]."</td>";	
			}
			echo "</tr>";
		}

		echo "<tr><td>Total</td>";
		
		for ($i=0; $i < 11; $i++) { 
			$sql_total[$i] = "SELECT avg(val) from (select distinct id_machine, (value".$i."/nbr_U) as val from machine natural join conso_weekly) as tbl;";
			$result_total[$i] = $conn->query($sql_total[$i]);
			$row = $result_total[$i]->fetch_assoc();
			$valeur['Total'][$i] = $row["avg(val)"]; 
			echo "<td>".$row["avg(val)"]."</td>";
		}
		
		echo "</tr></table>";


		echo "<h2>Moyenne et écart type pour chaques types</h2>";


		echo "<table><tr><td>Type</td><td>Moyenne</td><td>Ecart type</td></tr>";

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

		$conn->close(); 

		?>
	</div>
</body>
</html>