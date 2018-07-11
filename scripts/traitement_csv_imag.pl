#####################################################################################################
#####################################################################################################
#                               traitement_csv_imag                                                 
#                               
#                               Paramters : Two csv files : one input and one output                  
#                               
#                               Sortie : (re)initialise hebergement db                                                 
#####################################################################################################
#####################################################################################################

use DBI;

my $filename = $ARGV[0];
my $outputName = $ARGV[1];

my $serveur = 'localhost'; 
my $identifiant = 'admin';
my $motdepasse  = 'admin';
my $port  = '';
my $db = 'imag';


if(not defined $filename){
	die "Besoin du fichier d'entrée";
}
if(not defined $outputName){
	die "Besoin du fichier de sortie";
}

# Connection à la bdd
my $dbh = DBI->connect( "DBI:mysql:database=$bd;host=$serveur;port=$port", 
    $identifiant, $motdepasse, { 
        RaiseError => 1,
    }  
    ) or die "Connection impossible à la base de données $bd !\n $! \n $@\n$DBI::errstr";
$dbh->do('use imag;');


# Ouverture fichier d'entrée / sortie
open(my $file, '<', $filename) || die ("Erreur lors de l'ouverture du fichier $filename");
open(my $output, '>', $outputName) || die ("Erreur lors de l'ouverture du fichier $outputName");



# Preparation de les requete d insertion

my $requete_insertion_baie = <<"SQL";
INSERT IGNORE INTO baie(nom_baie)
VALUES(?);
SQL
my $sth_hebergement_baie = $dbh->prepare($requete_insertion_baie);

my $requete_insertion_capacite = <<"SQL";
INSERT IGNORE INTO capacite(id_machine)
VALUES(?);
SQL
my $sth_hebergement_capacite = $dbh->prepare($requete_insertion_capacite);

my $requete_insertion_ss_categorie = <<"SQL";
INSERT IGNORE INTO ss_categorie(nom_modele)
VALUES(?);
SQL
my $sth_hebergement_ss_categorie = $dbh->prepare($requete_insertion_ss_categorie);

my $requete_insertion_admin = <<"SQL";
INSERT IGNORE INTO rattachement_admin(nom_admin)
VALUES(?);
SQL
my $sth_hebergement_admin = $dbh->prepare($requete_insertion_admin);


my $requete_insertion_equipe = <<"SQL";
INSERT IGNORE INTO projet_equipe(nom_projet, nom_admin)
VALUES (?, ?);
SQL
my $sth_hebergement_equipe = $dbh->prepare($requete_insertion_equipe);


my $requete_insertion = <<"SQL";
INSERT IGNORE INTO machine(nom_projet, id_machine, nom_baie, nom_modele, serveur, stockage, aci, cluster, reseaux, nbr_U, puissance_theorique, num_serie, id_U)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
SQL
my $sth_hebergement = $dbh->prepare($requete_insertion);

my $requete_insertion_outlet = <<"SQL";
INSERT IGNORE INTO outlet(id, id_machine, id_outlet, id_PDU)
VALUES(?, ?, ?, ?);
SQL
my $sth_hebergement_outlet = $dbh->prepare($requete_insertion_outlet);


#Récupération des informations
my $id = 250;
my $id_machine = 200;
while ( my $ligne = <$file> ) {
    if ($id_machine>=201) {
        chomp $ligne;
        $ligne = lc($ligne);
        my @l = split /\t/, $ligne;
        my @outlet = get_outlet(@l);
        my @type = get_type(@l);
        $sth_hebergement_baie->execute($l[7]) or die "Echec requete d insertion : $DBI::errstr";
        $sth_hebergement_capacite->execute($id_machine) or die "Echec requete d insertion : $DBI::errstr";
        $sth_hebergement_ss_categorie->execute($l[8]) or die "Echec requete d insertion : $DBI::errstr";
        $sth_hebergement_admin->execute($l[0]) or die "Echec requete d insertion : $DBI::errstr";
        $sth_hebergement_equipe->execute($l[1], $l[0]) or die "Echec requete d insertion : $DBI::errstr";
        $sth_hebergement->execute($l[1], $id_machine, $l[7], $l[8], $type[2], $type[3], $type[4], $type[5], $type[6], $l[10], $l[12], $l[9], $l[11]) or die "Echec requete d insertion : $DBI::errstr";
        foreach $v (@outlet){ #Parcour prises
            @prise = split /\t/, $v;
            $sth_hebergement_outlet->execute($id, $id_machine, $prise[1], $prise[0]) or die "Echec requete d insertion : $DBI::errstr";
            $id++;  
        }
    }
    $id_machine++;
}


# Disconnection à la bdd et fermeture des fichiers
$dbh->disconnect();
close $file;
close $output;


#Renvoie un tableau de string de la forme "pdu-outlet"
sub get_outlet {
    my $compt = 0;
    my (@l) = @_;
    my @output;

    for (my $i = 13; $i < 4*30; $i++) {
        if (@l[$i] eq "x") {
            my $outlet_id = ($i-13)%30+1;
            my $pdu_id = int(($i-13)/30)+1;
            $output[$compt] = "$pdu_id\t$outlet_id";
            $compt++;
        }
    }
    return @output;
}

#Renvoie un tableau de booléen 
sub get_type {
    my (@l) = @_;
    my @output; 
    for (my $i = 2; $i < 7; $i++) {
        if ($l[$i] eq "x") {

            $output[$i] = 1;
            } else {
                $output[$i] = 0;
            }
        }
        return @output;
    }