<html>
 	<head>
  		<title>Détail machine</title>
  		<link rel="stylesheet" type="text/css" href="style/style.css">
 	</head>
 	
 	<body>
 		<div id="liste machineines">
 			<p>
 				<h2>Liste des machines</h2>

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


					$sql = "select distinct id_machine, nom_baie, nom_modele, serveur, baie, reseaux, cluster, puissance_theorique, num_serie, nbr_U, id_U from machine where nom_baie='".$_POST['rack']."';";

					$result = $conn->query($sql);
					$tab = array();


					if ($result->num_rows > 0) {
						
						echo "<table><tr><td>U<td/><td>ID machine<td/><td>Modele<td/><td>Serveur<td/><td>Baie<td/><td>Réseaux<td/><td>Cluster<td/><td>Puissance théorique<td/><td>Numéro de série<td/><td>Nombre de U<td/><tr/>";
					    
					    while($row = $result->fetch_assoc()) {
					        $id_U = string_to_id_U($row['id_U']);
					        $tab[$id_U]="<tr><td>".$id_U."<td/><td><a href=\"machine.php?id_machine=".$row['id_machine']."\">".$row['id_machine']."</a><td/><td>".$row['nom_modele']."<td/><td>".$row['serveur']."<td/><td>".$row['baie']."<td/><td>".$row['reseaux']."<td/><td>".$row['cluster']."<td/><td>".$row['puissance_theorique']."<td/><td>".$row['num_serie']."<td/><td>".$row['nbr_U']."<td/><tr/>";
					    }
					    
					    for ($i=43; $i > 0; $i--) { 
					    	if (isset($tab[$i])) {
					    		echo $tab[$i];
					    	}
					    	// else{echo "<tr><td>".$i."<td/><td><td/><td><td/><td><td/><td><td/><td><td/><td><td/><td><td/><td><td/><td><td/><tr/>";}
					    }
						
						echo "<table/>";
					
					} else {
					    echo "0 results";
					}

					function string_to_id_U($id_U){
						$len=strlen($id_U);
						if($len==3 || $len==7){
							return substr($id_U, 1, 2);
						}
						else{
							return substr($id_U, 1, 1);
						}
					}
 				?>
 			</p>
 		</div>
 		<div>
 			<h2>Représentation de la baie</h2>
 			
 		</div>
 	</body>
</html>