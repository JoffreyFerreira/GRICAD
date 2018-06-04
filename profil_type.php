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
					$dbname = "hebergement";
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
					        echo "<tr><td>".$row['id_machine']."<td/><td>".$row['nom_baie']."<td/><td>".$row['nom_modele']."<td/><td>".$row['serveur']."<td/><td>".$row['baie']."<td/><td>".$row['reseaux']."<td/><td>".$row['cluster']."<td/><td>".$row['puissance_theorique']."<td/><td>".$row['num_serie']."<td/><td>".$row['nbr_U']."<td/><tr/>";
					    }
					echo "<table/>";
					} else {
					    echo "0 results";
					}

 				?>
 			</p>
 		</div>
 		<div id="tableau">
 			<p>
 				<?php


 					echo "<h2>Profil de consommation<h2/>";

 					echo "<h4>Consommation sur l'année<h4/>";
 					
 					$query = array();
 					//URL construction
	 				$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';
	 				for ($i=1; $i < 52; $i++) { 
	 					# code...
	 				}
	 				$query = urlencode('avg_over_time(rPDU2OutletMeteredStatusPower{instance="imag-dc-pdu-a1-1.u-ga.fr", rPDU2OutletMeteredStatusIndex="15"}[1h]  offset 8h)');


	 				//API call
	 				$res = shell_exec('curl -k '.$url.$query);
	 				$result = json_decode($res);


	 				// If error
	 				if ($result->{'status'}=='error') {
	 					echo "QUERY : ".urldecode($query)."<br>ERROR ON QUERY : ".$result->{'error'};
	 				}


	 				
	 				//If success
	 				// else{
	 				// 	$valeur=array();
	 				// 	$nbPDU = count($result->{'data'}->{'result'});
	 				// 	$nbMesure = count($result->{'data'}->{'result'}['0']->{'values'});
		 			// 	echo "<table><tr>";
		 				
		 			// 	for ($i=0 ; $i<$nbPDU ; $i++) { 
		 			// 		echo "<td>".$result->{'data'}->{'result'}[$i]->{'metric'}->{'instance'}."</td>";
		 			// 	}
		 			// 	echo "</tr>";
		 				
		 			// 	for ($j=0; $j < $nbMesure; $j++) { 
		 			// 		echo "<tr>";
		 			// 		for ($i=0; $i < $nbPDU; $i++) { 
		 			// 			echo "<td>".$result->{'data'}->{'result'}[$i]->{'values'}[$j]['1']."</td>";
		 			// 			$valeur[$i][$j] = $result->{'data'}->{'result'}[$i]->{'values'}[$j]['1'];
		 			// 		}
		 			// 		echo "</tr>";
		 			// 	}
		 			// 	echo "</table>";	
		 			// }

 					echo "<h4>Consommation sur la semaine<h4/>";
 				?>
 			</p>
 		</div>
 	</body>
</html>