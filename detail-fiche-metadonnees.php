<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

/*
Certains contenus peuvent être au format MarkDown.
La bibliothèque PHP ParseDown permet un affichage respecant ce balisage
Plus d'infos ici :
- https://fr.wikipedia.org/wiki/Markdown
- http://parsedown.org
*/

include("inc/parsedown.php");
$Parsedown = new Parsedown();

function format_millier($valeur){
	return number_format($valeur, 0, ',', ' ');
}

function convertirRole($quel){
	switch(strtolower($quel)){
		case "pointofcontact":
		$role="Point de contact";
		break;
		
		case "author":
		$role="Auteur";
		break;
		
		case "distributor":
		$role="Distributeur";
		break;
		
		case "owner":
		$role="Propriétaire";
		break;
		
		case "custodian":
		$role="Gestionnaire";
		break;
		
		case "originator":
		$role="Créateur";
		break;
		
		case "principalInvestigator":
		$role="Analyste principal";
		break;
		
		default:
		$role=$quel;
		break;
	}
	return $role;
}

function convertirPays($quel){
	switch($quel){
		default:
		case "FR":
		$pays="France";
		break;
		
		case "GB":
		case "UK":
		$pays="Royaume Uni";
		break;
		
		case "CH":
		$pays="Suisse";
		break;
		
		case "US":
		$pays="États-Unis";
		break;
		
		case "IT":
		$pays="";
		break;
		
		case "Italie":
		$pays="";
		break;
		
		case "BE":
		$pays="Belgique";
		break;
		
		case "PT":
		$pays="Portugal";
		break;
		
		case "RU":
		$pays="Russie";
		break;
		
		case "JP":
		$pays="Japon";
		break;
		
		case "CN":
		$pays="Chine";
		break;
	}
	return $pays;
}

// INFORMATIONS TRANSMISES PAR ISOGEO POUR ACCÉDER À L'API
$consumer_key="portail-de-donnees-demo-da9858b3efe14f3da31e8d4188fe5bbb";
$consumer_secret="Bf0uIuFdFMI6jsqrpY52vNkyu3g4yCdqDgqvepSvap4pJc6pSe6dV1JmnmC7y0TR";
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
ACCÈS À UNE FICHE PAR SON ID
*/

$url="https://v1.api.isogeo.com/resources/cc9d1de9f1164159bea1465ef9826eb0?_lang=fr";

$v=@file_get_contents($url, false, $context);
if( trim($v)=="" ){
	echo "Cette fiche de métadonnée n'existe pas ou n'est plus accessible.";
	die;
}
else{
	$contenu=@file_get_contents($url, false, $context);
	$resultat=json_decode($contenu);
	$datas_fiche=get_object_vars($resultat);
	$tags=get_object_vars($datas_fiche["tags"]);
	$tags_array=array_keys($tags);
}



// 1. TITRE DE LA MÉTADONNÉE
echo "<h2>Titre de la fiche de méta-donnée :</h2>" . $datas_fiche["title"] . "<br>";



// 2. RÉSUMÉ
if(trim($datas_fiche["abstract"])!="") echo "<h2>Résumé</h2>" . $Parsedown->text($datas_fiche["abstract"]) . "<br>";



// 3. HISTORIQUE
// 3.1. DATES DE CRÉATION / MODIFICATION DE LA DONNÉE
echo "<h2>Dates :</h2>";
$url_dates="https://v1.api.isogeo.com/resources/cc9d1de9f1164159bea1465ef9826eb0/events/?_lang=fr";
$contenu_dates=@file_get_contents($url_dates, false, $context);
$resultat_dates=json_decode($contenu_dates);

$dateModifData="";
$dateCreaData="";
if(count($resultat_dates)>0){
	for($i=0;$i<count($resultat_dates);$i++){
		if( ($resultat_dates[$i]->{'kind'})=="update" && $dateModifData==""){
			$dateModifData=$resultat_dates[$i]->{'date'};
		}
		if( ($resultat_dates[$i]->{'kind'})=="creation" && $dateCreaData==""){
			$dateCreaData=$resultat_dates[$i]->{'date'};
		}
	}
}
if( trim($dateCreaData)!="" ) echo "- Création de la donnée : " . date('d/m/Y',strtotime($dateCreaData)) . "<br>";
if( trim($dateModifData)!="" ) echo "- Modification de la donnée : " . date('d/m/Y',strtotime($dateModifData)) . "<br>";

// 3.2 DATES DE CRÉATION / MODIFICATION DE LA MÉTA-DONNÉE
if( trim($datas_fiche["_created"])!="" ) echo "- Création de la méta-donnée : " . date('d/m/Y',strtotime($datas_fiche["_created"]))  . "<br>";
if( trim($datas_fiche["_modified"])!="" ) echo "- Modification de la méta-donnée : " . date('d/m/Y',strtotime($datas_fiche["_modified"]))  . "<br>";

// 3.3 DATES DE VALIDITÉ
if(trim($datas_fiche["validFrom"])!="") echo "- Début de validité : " . date('d/m/Y',strtotime($datas_fiche["validFrom"])) . "<br>";
if(trim($datas_fiche["validTo"])!="") echo "- Fin de validité : " . date('d/m/Y',strtotime($datas_fiche["validTo"])) . "<br>";

echo "<br>";



// 4. CONTEXTE
if(trim($datas_fiche["collectionContext"])!="") echo "<h2>Contexte de collecte :</h2> " . $Parsedown->text($datas_fiche["collectionContext"]) . "<br>";

if(trim($datas_fiche["collectionMethod"])!="") echo "<h2>Méthode de collecte :</h2> " . $Parsedown->text($datas_fiche["collectionMethod"]) . "<br>";

if(trim($datas_fiche["updateFrequency"])!=""){
	echo "<h2>Fréquence de mise à jour</h2>";
	switch($datas_fiche["updateFrequency"]){
		case "PT1M":
		echo "- Toutes les minutes";
		break;
		
		case "P1D":
		echo "- Quotidienne";
		break;
		
		case "P7D":
		echo "- Hebdomadaire";
		break;
		
		case "P1M":
		echo "- Mensuelle";
		break;
		
		case "P3M":
		echo "- Trimestrielle";
		break;
		
		case "P6M":
		echo "- Semi-annuelle";
		break;
		
		case "P1Y":
		echo "- Annuelle";
		break;
	}
	echo "<br>";
}

echo "<br>";



// 5. INFORMATIONS TECHNIQUES
echo "<h2>Informations techniques :</h2>";
switch($datas_fiche["type"]){
	default:
	case "":
	echo "- Type : non renseigné<br>";
	break;
	
	case "vectorDataset":
	echo "- Type : donnée vecteur<br>";
	break;
	
	case "rasterDataset":
	echo "- Type : donnée image<br>";
	break;
	
	case "service":
	echo "- Type : service<br>";
	break;
	
	case "ressource":
	echo "- Type : ressource<br>";
	break;
}

echo "- Nom de la couche : " . $datas_fiche["name"] . "<br>";

echo "- Nombre d'entités : ";
if(trim($datas_fiche["features"])==""){ echo " non renseigné<br>";} else{ echo $datas_fiche["features"] . "<br>";}

echo "- Type de géométrie : ";
if(trim($datas_fiche["geometry"])==""){ echo " non renseigné<br>";} else{ echo $datas_fiche["geometry"] . "<br>";}

echo "- Résolution : ";
if(trim($datas_fiche["resolution"])==""){ echo " non renseigné<br>";} else{ echo $datas_fiche["resolution"] . "<br>";}

echo "- Échelle : ";
if(trim($datas_fiche["scale"])==""){ echo " non renseignée<br>";} else{ echo "1/" . format_millier($datas_fiche["scale"]) . "<br>";}

echo "- Format de référence : ";
if(trim($datas_fiche["format"])==""){ echo " non renseigné<br>";} else{ echo $datas_fiche["format"] . " " . $datas_fiche["formatVersion"] . "<br>";}

echo "-Système de coordonnées : ";
$str=0;
for($i=0;$i<count($tags);$i++){
	if(strstr($tags_array[$i],"coordinate-system:")){
		$str=1;
		echo $tags[$tags_array[$i]];
	}
}
if($str==0) echo " non renseigné<br>";

echo "- Encodage des caractères : ";
if(trim($datas_fiche["encoding"])==""){ echo " non renseigné<br>";} else{ echo $datas_fiche["encoding"] . "<br>";}

echo "<br>";



// 6. CONDITIONS
// 6.1 Conditions d'utilisation
$url_conditions="https://v1.api.isogeo.com/resources/cc9d1de9f1164159bea1465ef9826eb0/conditions?_lang=fr";
if(trim(@file_get_contents($url_conditions, false, $context)!="")){

	$contenu_conditions=@file_get_contents($url_conditions, false, $context);
	$resultat_conditions=json_decode($contenu_conditions);
	if(count($resultat_conditions)>0){
		echo "<h2>Conditions :</h2>";
		for($i=0;$i<count($resultat_conditions);$i++){
			$str=0;
			$datas_conditions=$resultat_conditions[$i];
			$datas_conditions_array=get_object_vars($datas_conditions);
			if( trim($datas_conditions_array["_id"])!="" ){
				if(trim($datas_conditions_array["license"]->{'link'})==""){
					if(trim($datas_conditions_array["license"]->{'name'})!=""){
						echo "- Licence : " . $datas_conditions_array["license"]->{'name'} . "<br>";
					}
				}
				else{
					echo "- Licence : <a href=\"" . $datas_conditions_array["license"]->{'link'} . "\" target=\"_blank\">";
					echo $datas_conditions_array["license"]->{'name'} . "<br>";
					echo "</a>";
				}
				if(trim($datas_conditions_array["license"]->{'content'})!=""){
					echo "- Contenu : " . $Parsedown->text($datas_conditions_array["license"]->{'content'}) . "<br>";
				}
				if(trim($datas_conditions_array["description"])!=""){
					echo "- Description : " . $Parsedown->text($datas_conditions_array["description"]);
				}
			}
			echo "</p>";
		}
	}
}

echo "<br>";

// 6.2 Limitations
$url_limitations="https://v1.api.isogeo.com//resources/cc9d1de9f1164159bea1465ef9826eb0/limitations";
if(trim(@file_get_contents($url_limitations, false, $context)!="")){

	$contenu_limitations=@file_get_contents($url_limitations, false, $context);
	$resultat_limitations=json_decode($contenu_limitations);
	if(count($resultat_limitations)>0){
		for($i=0;$i<count($resultat_limitations);$i++){
			$str=0;
			$datas_limitations=$resultat_limitations[$i];
			$datas_limitations_array=get_object_vars($datas_limitations);
			if( trim($datas_limitations_array["_id"])!="" ){
				if($i==0){
					echo "<h2>Limitations :</h2>";

					if(trim($datas_limitations_array["directive"]->{'link'})==""){
						if(trim($datas_limitations_array["directive"]->{'name'})!=""){
							echo "- Directive : " . $datas_limitations_array["directive"]->{'name'} . "<br>";
						}
					}
					else{
						echo "- Directive : <a href=\"" . $datas_limitations_array["directive"]->{'link'} . "\" target=\"_blank\">";
						echo $datas_limitations_array["directive"]->{'name'} . "<br>";
						echo "</a>";
					}
					if(trim($datas_limitations_array["directive"]->{'content'})!=""){
						echo "- Contenu : " . $datas_limitations_array["directive"]->{'content'} . "<br>";
					}
					if(trim($datas_limitations_array["restriction"])!=""){
						echo "- Restriction : " . $datas_limitations_array["restriction"] . "<br>";
					}
					if(trim($datas_limitations_array["description"])!=""){
						echo "- Description : " . $Parsedown->text($datas_limitations_array["description"]);
					}
				}
			}
		}
	}
}

echo "<br>";



// 7. Contacts
$url_contacts="https://v1.api.isogeo.com/resources/cc9d1de9f1164159bea1465ef9826eb0/contacts";
if(trim(@file_get_contents($url_contacts, false, $context)!="")){
	$contenu_contacts=@file_get_contents($url_contacts, false, $context);
	$resultat_contacts=json_decode($contenu_contacts);
	if(count($resultat_contacts)>0){
		echo "<h2>Contacts :</h2>";
		$poc=array();
		for($i=0;$i<count($resultat_contacts);$i++){
			$datas_contact=$resultat_contacts[$i];
			$datas_contact_array=get_object_vars($datas_contact);
			if($datas_contact_array["role"]=="pointOfContact"){
				$affichePoc=1;
				$tmp=array();
				$tmp["name"]=$datas_contact_array["contact"]->{'name'};
				$tmp["role"]=$datas_contact_array["role"];
				$tmp["addressLine1"]=$datas_contact_array["contact"]->{'addressLine1'};
				$tmp["addressLine2"]=$datas_contact_array["contact"]->{'addressLine2'};
				$tmp["zipCode"]=$datas_contact_array["contact"]->{'zipCode'};
				$tmp["city"]=$datas_contact_array["contact"]->{'city'};
				$tmp["countryCode"]=convertirPays($datas_contact_array["contact"]->{'countryCode'});
				$tmp["phone"]=$datas_contact_array["contact"]->{'phone'};
				$tmp["fax"]=$datas_contact_array["contact"]->{'fax'};
				$tmp["email"]=$datas_contact_array["contact"]->{'email'};
				if( trim($datas_contact_array["contact"]->{'name'})!="" ){
					$name=$datas_contact_array["contact"]->{'name'};
				}
				$poc[$name]=$tmp;
			}
		}
		if($affichePoc==1){
			sort($poc);
			for($i=0;$i<count($poc);$i++){
				echo "<br>- " . convertirRole($poc[$i]["role"]) . " :<br>";
				if(trim($poc[$i]["name"])!=""){
					echo $poc[$i]["name"] . "<br />";
				}
				if(trim($poc[$i]["addressLine1"])!=""){
					echo $poc[$i]["addressLine1"] . "<br />";
				}
				if(trim($poc[$i]["addressLine2"])!=""){
					echo $poc[$i]["addressLine2"] . "<br />";
				}
				if(trim($poc[$i]["zipCode"])!=""){
					echo $poc[$i]["zipCode"] . " ";
				}
				if(trim($poc[$i]["city"])!=""){
					echo $poc[$i]["city"];
				}
				if(trim($poc[$i]["zipCode"])!="" || trim($poc[$i]["city"])!=""){
					echo "<br />";
				}
				if(trim($poc[$i]["countryCode"])!=""){
					echo convertirPays($poc[$i]["countryCode"]) . "<br />";
				}
				if($_SESSION["isogeo_identif"]==1){
					if(trim($poc[$i]["phone"])!=""){
						echo $poc[$i]["phone"] . "<br />";
					}
					if(trim($poc[$i]["fax"])!=""){
						echo $poc[$i]["fax"] . "<br />";
					}
				}
				if(trim($poc[$i]["email"])!=""){
					echo "<a href=mailto:" . $poc[$i]["email"] . " target=_blank>" . $poc[$i]["email"] . "</a><br>";
				}
			}
		}						
		
		$poc=array();
		for($i=0;$i<count($resultat_contacts);$i++){
			$datas_contact=$resultat_contacts[$i];
			$datas_contact_array=get_object_vars($datas_contact);
			if($datas_contact_array["role"]!="pointOfContact"){
				$afficheNoPoc=1;
				$tmp=array();
				$tmp["name"]=$datas_contact_array["contact"]->{'name'};
				$tmp["role"]=$datas_contact_array["role"];
				$tmp["addressLine1"]=$datas_contact_array["contact"]->{'addressLine1'};
				$tmp["addressLine2"]=$datas_contact_array["contact"]->{'addressLine2'};
				$tmp["zipCode"]=$datas_contact_array["contact"]->{'zipCode'};
				$tmp["city"]=$datas_contact_array["contact"]->{'city'};
				$tmp["countryCode"]=convertirPays($datas_contact_array["contact"]->{'countryCode'});
				$tmp["phone"]=$datas_contact_array["contact"]->{'phone'};
				$tmp["fax"]=$datas_contact_array["contact"]->{'fax'};
				$tmp["email"]=$datas_contact_array["contact"]->{'email'};
				if( trim($datas_contact_array["contact"]->{'name'})!="" ){
					$name=$datas_contact_array["contact"]->{'name'};
				}
				$poc[$name]=$tmp;
			}
		}
		if($afficheNoPoc==1){
			sort($poc);
			for($i=0;$i<count($poc);$i++){
				echo "<br>- " . convertirRole($poc[$i]["role"]) . " :<br>";
				if(trim($poc[$i]["name"])!=""){
					echo $poc[$i]["name"] . "<br />";
				}
				if(trim($poc[$i]["addressLine1"])!=""){
					echo $poc[$i]["addressLine1"] . "<br />";
				}
				if(trim($poc[$i]["addressLine2"])!=""){
					echo $poc[$i]["addressLine2"] . "<br />";
				}
				if(trim($poc[$i]["zipCode"])!=""){
					echo $poc[$i]["zipCode"] . " ";
				}
				if(trim($poc[$i]["city"])!=""){
					echo $poc[$i]["city"];
				}
				if(trim($poc[$i]["zipCode"])!="" || trim($poc[$i]["city"])!=""){
					echo "<br />";
				}
				if(trim($poc[$i]["countryCode"])!=""){
					echo convertirPays($poc[$i]["countryCode"]) . "<br />";
				}
				if($_SESSION["isogeo_identif"]==1){
					if(trim($poc[$i]["phone"])!=""){
						echo $poc[$i]["phone"] . "<br />";
					}
					if(trim($poc[$i]["fax"])!=""){
						echo $poc[$i]["fax"] . "<br />";
					}
				}
				if(trim($poc[$i]["email"])!=""){
					echo "<a href=mailto:" . $poc[$i]["email"] . " target=_blank>" . $poc[$i]["email"] . "</a><br>";
				}
			}
		}
	}
}
?>