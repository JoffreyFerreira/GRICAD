<html>
 	<head>
  		<title>Profil équipe</title>
  		<link rel="stylesheet" type="text/css" href="style/style.css">
 	</head>
 	
 	<body>
 		<div>
 			<?php
 				$servername = "localhost";
				$username = "admin";
				$password = "admin";
				$dbname = "imag";

				// Create connection
				$conn = new mysqli($servername, $username, $password, $dbname);
					
				// Check connection
				if ($conn->connect_error) {
				    die("Connection failed: " . $conn->connect_error);
				}


				$sql = "select distinct id_machine, nom_baie, nom_modele, serveur, baie, reseaux, cluster, puissance_theorique, num_serie, nbr_U from machine where nom_baie='".$_POST['rack']."';";

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
 		</div>
 	</body>
</html>