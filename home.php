<html>
 	<head>
  		<title>Consommation DC IMAG</title>
  		<link rel="stylesheet" type="text/css" href="style/style.css">

 	</head>
 	
 	<body>

 		<div id="choix">
 		<form method="post" action="home.php">
	   		<p>
		   		<ul>
		   		<?php 
		   		$lettre = array('a', 'b', 'c', 'd', 'e', 'f');
		   		for ($i=0; $i < 6; $i++) { 
		   			echo "<li><input type=\"radio\" name=\"baie\" value=\"".$lettre[$i]."\" \>".$lettre[$i]."<ul>";
		   			for ($j=1; $j < 7; $j++) { 
		   				echo "<li><input type=\"radio\" name=\"rack\" value=\"".$lettre[$i].$j."\" />".$lettre[$i].$j."</li>";
		   			}
		   			if ($i==0) {
		   				echo "<li><input type=\"radio\" name=\"rack\" value=\"a7\" />a7</li>";
		   			}
		   			echo "</ul></li>";
		   		}
		   		?>

				<li><input type="radio" name="rack" value="annexe">Annexe</li>
				
				</ul>

			   	<label for="time">Saisir durée:</label> 
			   	<input type="text" name="time" id="time" /> Ex : 1m = 1 minute, 2h, 3d...
				<input type="submit" value="Envoyer" />
			</p>
		</form>

		<!-- MENU DEROULANT CHOIX D EQUIPE -->
		<form method="post" action="profil_equipe.php">
			<p>
				<label for="equipe">Sélectionnez équipe</label>
				<?php
				
				$servername = "localhost";
				$username = "admin";
				$password = "admin";
				$dbname = "hebergement";

				// Create connection
				$conn = new mysqli($servername, $username, $password, $dbname);
				
				// Check connection
				if ($conn->connect_error) {
				    die("Connection failed: " . $conn->connect_error);
				}

				$sql = "SELECT nom_projet FROM projet_equipe";
				$result = $conn->query($sql);

				if ($result->num_rows > 0) {
				    // output data of each row
				    echo "<select name=\"equipe\" id=\"equipe\">";
				    while($row = $result->fetch_assoc()) {
				    	$valeur=$row["nom_projet"];
						echo "<option value=\"$valeur\">".$row["nom_projet"]."</option>";
				    }
				    echo("</select>");
				} else {
				    echo "0 results";
				}
				
				$conn->close();
				?>
				<input type="submit" value="Envoyer équipe"/>
			</p>
		</form>	
		<form method="post" action=profil_type.php>
			<p>
				<label for=type>Selectionnez type de machine</label>
				<select name="type">
					<option value="serveur">serveur</option>
					<option value="baie">baie</option>
					<option value="reseaux">reseaux</option>
					<option value="cluster">cluster</option>
				</select>
				<input type="submit" value="Envoyer type">
			</p>
		</form>

		<p>
 			<?php
 				
 				if (isset($_POST['rack'])) {

	 				//URL construction
	 				if ($_POST['time'] == '') { $time='1m'; } else { $time=$_POST['time']; }
	 				$url = 'https://gricad-dc-monitor.u-ga.fr/api/v1/query?query=';
	 				$query = urlencode('rPDU2DeviceStatusEnergy{rack="'.$_POST['rack'].'"}['.$time.']');


	 				//API call
	 				$res = shell_exec('curl -k '.$url.$query);
	 				$result = json_decode($res);


	 				// If error
	 				if ($result->{'status'}=='error') {
	 					echo "QUERY : ".urldecode($query)."<br>ERROR ON QUERY : ".$result->{'error'};
	 				}


	 				
	 				//If success
	 				else{
	 					$valeur=array();
	 					$nbPDU = count($result->{'data'}->{'result'});
	 					$nbMesure = count($result->{'data'}->{'result'}['0']->{'values'});
		 				echo "<table><tr>";
		 				
		 				for ($i=0 ; $i<$nbPDU ; $i++) { 
		 					echo "<td>".$result->{'data'}->{'result'}[$i]->{'metric'}->{'instance'}."</td>";
		 				}
		 				echo "</tr>";
		 				
		 				for ($j=0; $j < $nbMesure; $j++) { 
		 					echo "<tr>";
		 					for ($i=0; $i < $nbPDU; $i++) { 
		 						echo "<td>".$result->{'data'}->{'result'}[$i]->{'values'}[$j]['1']."</td>";
		 						$valeur[$i][$j] = $result->{'data'}->{'result'}[$i]->{'values'}[$j]['1'];
		 					}
		 					echo "</tr>";
		 				}
		 				echo "</table>";	
		 			}
	 			}
 			?>
 		</p>
 		</div><div id="graph">
 			<!-- <p>
 			<iframe src="https://gricad-grafana.u-ga.fr/d/GhFfsLViz/comparatif-consommations-prometheus?panelId=2&orgId=1&from=1527465677762&to=1527508877763" width="1200" height="700" frameborder="0"></iframe>
 			</p> -->
 		</div>
	</body>
</html>