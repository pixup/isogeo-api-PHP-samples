<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

function format_millier($valeur){
	return number_format($valeur, 0, ',', ' ');
}

// INFORMATIONS TRANSMISES PAR ISOGEO POUR ACCÉDER À L'API
$consumer_key="demo-ask-for-your-app-client";
$consumer_secret="demo-ask-for-your-app-secret";
$url_api="https://id.api.isogeo.com/oauth/token";

// RÉCUPÉRATION DU TOKEN
spl_autoload_register(function ($class) {
	require str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
});

$client = new OAuth2\Client($consumer_key,$consumer_secret,'');
$configuration = new OAuth2\Service\Configuration('AUTHORIZE_ENDPOINT',$url_api);
$dataStore = new OAuth2\DataStore\Session();
$scope = null;

$service = new OAuth2\Service($client, $configuration, $dataStore, $scope);
$service->getAccessToken();
$token = $dataStore->retrieveAccessToken();

$accessToken=$token->getAccessToken();
$lifeTime=$token->getLifeTime();

$options = array(
	'http' => array(
	'header'  => "Authorization: Bearer " . $accessToken . " \r\n"
  ),
);
$context  = stream_context_create($options);

/*
CHAINE DE REQUÊTE
paramètres utilisés :

_lang : langue des résultats

_offset : position dans la liste des résultats (ici 0=début de liste)

_limit : nombre maximum de résultats retournés (ici 100=maximum)

od : sens du tri (ici asc=croissant)

ob : critère de tri (ici title=titre de la fiche de métadonnées)

q : texte + tags de la recherche ; plusieurs critères possibles si concaténés avec des espaces
(ici recherche sur la chaîne 'paris' + sur le tag 'donnees-ouvertes')

box : emprise spatiale (ici 2.243,48.821,2.406,48.897 = paris)

type : type de la fiche de métadonnées (ici vector-dataset=donnée vecteur)
*/

$url="https://v1.api.isogeo.com/resources/search?_lang=fr&_offset=0&ob=title&_include=links&q=paris%20keyword:isogeo:donnees-ouvertes&box=2.243,48.821,2.406,48.897%20type:vector-dataset&od=asc";

// RÉCUPÉRATION DES RÉSULTATS
$contenu=@file_get_contents($url, false, $context);
$resultat=json_decode($contenu); 
$nbResultat=count($resultat->{"results"});

$tags=get_object_vars($resultat->{"tags"});
$tags_array=array_keys($tags);

$liste_inspire=array();
$liste_mc=array();
$liste_format=array();
$liste_coord=array();
$liste_action=array();
$liste_proprio=array();

// LISTE DES FICHES DE MÉTA-DONNÉES
echo "FICHES DE MÉTA-DONNÉES<br>--------------------------------------------------------<br>";
echo "Il y a " . $nbResultat . " fiche(s) de métadonnées<br>";
for($i=0;$i<$nbResultat;$i++){
	echo "<br><strong>" . $resultat->{"results"}[$i]->{"title"} . "</strong><br>";
	$tmp_prop=get_object_vars($resultat->{"results"}[$i]->{"_creator"});
	$tmp_prop=get_object_vars($tmp_prop["contact"]);

	echo "- ID de la fiche : " . $resultat->{"results"}[$i]->{"_id"} . "<br>";

	if(trim($tmp_prop["name"])!=""){
		echo "- Propriétaire de la donnée : " . $tmp_prop["name"] . "<br>";
	}
		
	if(trim($resultat->{"results"}[$i]->{"scale"})!=""){
		echo "- Échelle : " . format_millier($resultat->{"results"}[$i]->{"scale"}) . "<br>";
	}

	if(trim($resultat->{"results"}[$i]->{"type"})!=""){
		switch($resultat->{"results"}[$i]->{"type"}){
			case "":
			default:
			$typ="non renseigné";
			break;
			
			case "vectorDataset":
			$typ="Données vecteur";
			break;
			
			case "rasterDataset":
			$typ="Données raster";
			break;
			
			case "service":
			$typ="Service";
			break;
			
			case "ressource":
			$typ="Ressource";
			break;
		}
		echo "- Type : " . $typ . "<br>";
	}

	if(trim($resultat->{"results"}[$i]->{"abstract"})!=""){
		echo "- Résumé : " . $resultat->{"results"}[$i]->{"abstract"} . "<br>";
	}
	
	echo "- THÈMES INSPIRE associés à la fiche : ";
	for($j=0;$j<count($tags);$j++){
		if(strstr($tags_array[$j],"keyword:inspire-theme:")){
			echo $tags[$tags_array[$j]] . " ";
		}
	}

	echo "<br>";

	echo "- MOTS-CLÉS associés à la fiche : ";
	for($j=0;$j<count($tags);$j++){
		if(strstr($tags_array[$j],"keyword:isogeo:")){
			echo $tags[$tags_array[$j]] . " ";
		}
	}
	
	echo "<br>";
}

echo "<br>";

// AFFICHAGE DES THÈMES INSPIRE
echo "THÈMES INSPIRE POUR L'ENSEMBLE DES RÉSULTATS<br>--------------------------------------------------------<br>";
for($i=0;$i<count($tags);$i++){
	if(strstr($tags_array[$i],"keyword:inspire-theme:")){
		if (!in_array($tags[$tags_array[$i]], $liste_inspire)) {
			$liste_inspire[$tags_array[$i]]=$tags[$tags_array[$i]];
			echo $tags_array[$i] . "<br>";
		}
	}
}

echo "<br>";

/* AFFICHAGE DES MOTS MOTS-CLÉS */
echo "MOTS CLÉS POUR L'ENSEMBLE DES RÉSULTATS<br>--------------------------------------------------------<br>";
for($i=0;$i<count($tags);$i++){
	if(strstr($tags_array[$i],"isogeo:")){
		if (!in_array($tags[$tags_array[$i]], $liste_mc)) {
			$liste_mc[$tags_array[$i]]=$tags[$tags_array[$i]];
			echo $tags_array[$i] . "<br>";
		}
	}
}

echo "<br>";

// AFFICHAGE DES FORMATS
echo "FORMATS POUR L'ENSEMBLE DES RÉSULTATS<br>--------------------------------------------------------<br>";
for($i=0;$i<count($tags);$i++){
	if(strstr($tags_array[$i],"format:")){
		if (!in_array($tags[$tags_array[$i]], $liste_format)) {
			$liste_format[$tags_array[$i]]=$tags[$tags_array[$i]];
			echo $tags_array[$i] . "<br>";
		}
	}
}

echo "<br>";

// AFFICHAGE DES COORDONNÉES
echo "COORDONNÉES POUR L'ENSEMBLE DES RÉSULTATS<br>--------------------------------------------------------<br>";
for($i=0;$i<count($tags);$i++){
	if(strstr($tags_array[$i],"coordinate-system:")){
		if (!in_array($tags[$tags_array[$i]], $liste_coord)) {
			$liste_coord[$tags_array[$i]]=$tags[$tags_array[$i]];
			echo $tags_array[$i] . "<br>";
		}
	}
}

echo "<br>";

// AFFICHAGE DES PROPRIÉTAIRES
echo "PROPRIÉTAIRES POUR L'ENSEMBLE DES RÉSULTATS<br>--------------------------------------------------------<br>";
for($i=0;$i<count($tags);$i++){
	if(strstr($tags_array[$i],"owner:")){
		if (!in_array($tags[$tags_array[$i]], $liste_proprio)) {
			$liste_proprio[$tags_array[$i]]=$tags[$tags_array[$i]];
			echo $tags_array[$i] . "<br>";
		}
	}
}

echo "<br>";

?>