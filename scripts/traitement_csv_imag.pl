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
my $identifiant = 'root';
my $motdepasse  = '';
my $port  = '';
my $db = 'hebergement';


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
$dbh->do('use hebergement;');

#Supprime les tables déja existante
$dbh->do('DROP TABLE IF EXISTS machine;') or die "Impossible de supprimer la table machine\n\n";
$dbh->do('DROP TABLE IF EXISTS baie;') or die "Impossible de supprimer la table baie\n\n";
$dbh->do('DROP TABLE IF EXISTS projet_equipe;') or die "Impossible de supprimer la table projet_equipe\n\n";
$dbh->do('DROP TABLE IF EXISTS rattachement_admin;') or die "Impossible de supprimer la table rattachement_admin\n\n";


#Création des tables
my $sql_creation_table_rattachement_admin = <<"SQL";
create table rattachement_admin(
    nom_admin               VARCHAR(15)         not null,
    primary key (nom_admin)
);
SQL
$dbh->do($sql_creation_table_rattachement_admin) or die "Impossible de créer la table rattachement_admin\n\n";

my $sql_creation_table_projet_equipe = <<"SQL";
create table projet_equipe(
    nom_projet              VARCHAR(30)         not null,
    nom_admin               VARCHAR(15)         not null,
    primary key (nom_projet),
    foreign key (nom_admin) references rattachement_admin(nom_admin)
);
SQL
$dbh->do($sql_creation_table_projet_equipe) or die "Impossible de créer la table projet_equipe\n\n";


my $sql_creation_table_baie = <<"SQL";
CREATE TABLE baie (
    nom_baie                    VARCHAR(10)     not null,
    primary key (nom_baie)
);
SQL

$dbh->do($sql_creation_table_baie) or die "Impossible de créer la table baie\n\n";


my $sql_creation_table_machine = <<"SQL";
create table machine(
    id                          INTEGER         not null,
    nom_projet                  VARCHAR(30)     not null,
    id_machine                  INTEGER         not null,
    nom_baie                    VARCHAR(10)     not null,
    id_outlet                   INTEGER,
    id_PDU                      INTEGER,
    nom_modele                  VARCHAR(40)     not null,
    serveur                     INT             not null,
    baie                        INT             not null,
    reseaux                     INT             not null,
    cluster                     INT             not null,
    puissance_theorique         VARCHAR(10),
    num_serie                   VARCHAR(40),
    nbr_U                       INT,
    id_U                        INT,
    primary key (id),
    foreign key (nom_baie) references baie(nom_baie),
    foreign key (nom_projet) references projet_equipe(nom_projet)
);
SQL

$dbh->do($sql_creation_table_machine) or die "Impossible de créer la table machine\n\n";


# insert_table_baie();


# Ouverture fichier d'entrée / sortie
open(my $file, '<', $filename) || die ("Erreur lors de l'ouverture du fichier $filename");
open(my $output, '>', $outputName) || die ("Erreur lors de l'ouverture du fichier $outputName");



# Preparation de les requete d insertion

my $requete_insertion_baie = <<"SQL";
    INSERT IGNORE INTO baie(nom_baie)
    VALUES(?);
SQL
my $sth_hebergement_baie = $dbh->prepare($requete_insertion_baie);

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
    INSERT INTO machine(id, nom_projet, id_machine, nom_baie, id_outlet, id_PDU, nom_modele, serveur, baie, reseaux, cluster, nbr_U, puissance_theorique, num_serie)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
SQL
my $sth_hebergement = $dbh->prepare($requete_insertion);


#Récupération des lignes qui contiennent DC=IMAG et insertion dans la bdd
my $id = 1;
my $compt = 1;
while ( my $ligne = <$file> ) {
  	chomp $ligne;
    $ligne = lc($ligne);
    # $ligne =~ s/"//g;
  	my @l = split /\t/, $ligne;
    if ($l[10] eq "\"imag\""){
	    print( $output "$ligne\n");
        my @outlet = get_outlet(@l);
        my @type = get_type(@l);
        foreach (@l){
            $_ =~ s/"//g;
        }      
        foreach $v (@outlet){ #Parcour prises
            @prise = split /\t/, $v;
            # print "$compt, $l[1], $l[11], $prise[1], $prise[0], $l[6], $type[2], $type[3], $type[4], $type[5], $l[9], $l[7]\n";
            $sth_hebergement_baie->execute($l[11]);
            $sth_hebergement_admin->execute($l[1]);
            $sth_hebergement_equipe->execute($l[0], $l[1]);
            $sth_hebergement->execute($id, $l[0], $compt, $l[11], $prise[1], $prise[0], $l[6], $type[2], $type[3], $type[4], $type[5], $l[8], $l[9], $l[7]) or die "Echec requete d insertion : $DBI::errstr";      
            $id++;
        }
        $compt++;
  	}
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

    for (my $i = 12; $i < 4*24; $i++) {
        if (@l[$i] eq "\"x\"") {
            my $outlet_id = ($i-12)%24+1;
            my $pdu_id = int(($i-12)/24)+1;
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
    for (my $i = 2; $i < 6; $i++) {
        if ($l[$i] eq "\"x\"") {
            $output[$i] = 1  
        } else {
            $output[$i] = 0;
        }
    }
    return @output;
}