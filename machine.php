<html>
<head>
	<title>Détail machine</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">
</head>

<body>
	<div id="liste machine">
		<p>
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


			$sql = "select distinct id_machine, nom_baie, nom_modele, serveur, baie, reseaux, cluster, puissance_theorique, num_serie, nbr_U, id_U from machine where id_machine='".$_GET['id_machine']."';";

			$result = $conn->query($sql);

			if ($result->num_rows > 0) {

				echo "<table><tr><td>ID machine<td/><td>Baie<td/><td>Modele<td/><td>Serveur<td/><td>Baie<td/><td>Réseaux<td/><td>Cluster<td/><td>Puissance théorique<td/><td>Numéro de série<td/><td>Nombre de U<td/><td>U<td/><tr/>";

				while($row = $result->fetch_assoc()) {
					echo "<tr><td>".$row['id_machine']."<td/><td>".$row['nom_baie']."<td/><td>".$row['nom_modele']."<td/><td>".$row['serveur']."<td/><td>".$row['baie']."<td/><td>".$row['reseaux']."<td/><td>".$row['cluster']."<td/><td>".$row['puissance_theorique']."<td/><td>".$row['num_serie']."<td/><td>".$row['nbr_U']."<td/><td>".$row['id_U']."<td/><tr/>";
				}
				echo "<table/>";

			} else {
				echo "0 results";
			}

			?>
		</p>
	</div>
</body>
</html>