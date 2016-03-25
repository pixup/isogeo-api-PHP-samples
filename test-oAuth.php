<?php
session_start();
?>
<html>
	<head>
		<style>
			label{
				display:block;
				margin-bottom:5px;
			}

			label .titre{
				display:inline-block;
				width:150px;
			}

			label input[type='text']{
				border:1px solid #000000;
				padding:8px;
				min-width:500px;
			}

			label input[type='submit']{
				border:1px solid #000000;
				padding:8px;
				cursor:pointer;
				background-color:#888888;
				color:#fff;
			}

			.ro{
				background-color:#dddddd;
			}
		</style>	
	</head>
<?php

spl_autoload_register(function ($class) {
    require str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
});

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'access_token':
			
			$client = new OAuth2\Client($_POST['consumer-key'],$_POST['consumer-secret'],'');
			$configuration = new OAuth2\Service\Configuration('AUTHORIZE_ENDPOINT',$_POST['urlAPI']);
			$dataStore = new OAuth2\DataStore\Session();
			$scope = null;
			$service = new OAuth2\Service($client, $configuration, $dataStore, $scope);
			$service->getAccessToken();
			
			$token = $dataStore->retrieveAccessToken();
			$accessToken=$token->getAccessToken();
			$lifeTime=$token->getLifeTime();
		break;
    }
}
?>
    <body>
		<ul>
				<li>Exemple de Consumer Key : portail-de-donnees-demo-da9858b3efe14f3da31e8d4188fe5bbb</li>
				<li>Exemple de Consumer Secret : Bf0uIuFdFMI6jsqrpY52vNkyu3g4yCdqDgqvepSvap4pJc6pSe6dV1JmnmC7y0TR</li>
		</ul>
		
		<form action="test-oAuth.php" method="post">
			<input type="hidden" name="action" value="access_token">
			<label><span class="titre">URL API : </span><input type="text" name="urlAPI" value="https://id.api.isogeo.com/oauth/token" /></label>
			<label><span class="titre">Consumer Key: </span><input type="text" name="consumer-key" value="<?php echo $_POST["consumer-key"];?>" /></label>
			<label><span class="titre">Consumer Secret: </span><input type="text" name="consumer-secret" value="<?php echo $_POST["consumer-secret"];?>" /></label>
			<label><span class="titre">Access Token: </span><input type="text" readonly class="ro" value="<?php echo  $accessToken;?>" /></label>
			<label><span class="titre">LifeTime: </span><input type="text" readonly class="ro" value="<?php echo $lifeTime;?>" /></label>
			<label><span class="titre"></span><input type="submit" value="Get Token"></label>
        </form>		
    </body>
</html>
