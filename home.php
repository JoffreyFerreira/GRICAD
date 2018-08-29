<?php
$dc = "imag";
include 'scripts/ressources.php';
$conn = init($dc);
?>

<html>
<head>
	<title>Consommation DC IMAG</title>
	<link rel="stylesheet" type="text/css" href="style/style.css">

</head>

<body>

	<h2>Plateforme de monitoring du DC IMAG - Accueil</h2>

	<div id="choix">


		<h3>Profil par baie</h3>


		<form method="post" action="profil_baie.php">
			<?php menuDeroulant("nom_baie", "baie", $conn, "rack");?>
			<br>
			<input type="submit" value="Envoyer baie"/>
		</form>


		<h3>Profil par équipe</h3>


		<form method="post" action="profil_equipe.php">
			<?php menuDeroulant("nom_projet", "projet_equipe", $conn, "equipe");?>
			<br>
			<input type="submit" value="Envoyer équipe"/>
		</form>


		<h3>Profil par type de machine</h3>


		<form method="post" action=profil_type.php>
			<select name="type">
				<?php
				foreach ($type as $t) {
					echo "<option value=\"". $t ."\">". $t ."</option>";
				}
				?>
			</select>
			<br>
			<input type="submit" value="Envoyer type">
		</form>


		<h3>Page de comparaison des types</h3>


		<form>
			<input type="button" value="Comparaison des types" onclick="window.location.href='comparaison_type.php'" />
		</form> 

		<h3>Modification email de contact pour les équipes</h3>

		<form method="post" action="home.php">
			<?php menuDeroulant("nom_projet", "projet_equipe", $conn, "equipe");?>
			<input type="text" name="mail" />
			<?php
			if (isset($_POST['mail'])) {
				$sql = "UPDATE mail_equipe SET mail=\"".$_POST['mail']."\" where nom_projet=\"".$_POST['equipe']."\"";

				if ($conn->query($sql) === TRUE) {
					// echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql . " " . $conn->error . "\n";
				}

			}
			?>
			<input type="submit" value="Modifier mail de contact de l'équipe"/>
		</form>

		<h3>Assignation d'une baie à une équipe</h3>

		<form method="post" action="home.php">
			<?php menuDeroulant("nom_projet", "projet_equipe", $conn, "equipe");
			menuDeroulant("nom_baie", "baie_hd_equipe", $conn, "baie_hd");

			if (isset($_POST['baie_hd'])) {
				$sql = "UPDATE baie_hd_equipe SET nom_projet=\"".$_POST['equipe']."\" where nom_baie=\"".$_POST['baie_hd']."\"";

				if ($conn->query($sql) === TRUE) {
					// echo "New record created successfully\n";
				} else {
					echo "Error: " . $sql . " " . $conn->error . "\n";
				}

			}
			?>
			<input type="submit" value="Modifier équipe de la baie"/>
		</form>

	</div>
</body>
</html>