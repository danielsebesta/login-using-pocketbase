<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
	$email = $_POST['email'];
	$password = $_POST['password'];

	$postData = json_encode([
		'identity' => $email,
		'password' => $password,
	]);



	$ch = curl_init('https://pb.smirkhat.org/api/collections/users/auth-with-password');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Content-Length: ' . strlen($postData)
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

	$response = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($httpcode === 200) {
		$data = json_decode($response, true);
		$_SESSION['user'] = $data['record'];
		header('Location: index.php');
		exit();
	} else {
		$loginError = 'Neplatné přihlašovací údaje, zkuste to znovu.';
	}
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pocketbase PHP</title>
	<link rel="stylesheet" href="https://smirkhat.org/assets/css/halfmoon.min.css??">
</head>

<body>
	<center>
		<h1>Pocketbase PHP</h1>
		<p>4/8 - connecting Pocketbase login with PHP, tried to handle logging in with existing user in the database</p>
	</center>
	<br>

	<div class="container">
		<h2 class="text-center">Přihlášení</h2>
		<?php if ($loginError) {
			echo '<div class="alert alert-danger">' . $loginError . '</div>';
		} ?>
		<form action="" method="POST">
			<div class="mb-3">
				<label class="form-label" for="email">E-mailová adresa</label>
				<input type="email" class="form-control" id="email" name="email" required>
			</div>
			<div class="mb-3">
				<label class="form-label" for="password">Heslo</label>
				<input type="password" class="form-control" id="password" name="password" required>
			</div>
			<div class="d-flex align-items-center">
				<button type="submit" class="btn btn-primary ms-auto" name="login">Přihlásit se</button>
			</div>
		</form>
		<?php
		// URL API
		$url = "https://pb.smirkhat.org/api/collections/posts/records";

		// Inicializace cURL
		$ch = curl_init();

		// Nastavení cURL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Vykonání požadavku a uložení odpovědi do proměnné
		$response = curl_exec($ch);

		// Uzavření cURL
		curl_close($ch);

		// Dekódování JSON odpovědi na pole
		$data = json_decode($response, true);

		// Iterace přes články
		foreach ($data['items'] as $item) {
			// Datum ve formátu "d.m.Y"
			$datum = date("d.m.Y", strtotime($item['created']));

			// Základní URL pro obrázky
			$image_url = "https://pb.smirkhat.org/api/files/" . $item['collectionId'] . "/" . $item['id'] . "/" . $item['Obrazek_clanku'][0];

			echo '
                <div class="col-md-6">
                    <div class="row g-0 rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
                        <div class="col p-4 d-flex flex-column position-static">
                            <strong class="d-inline-block mb-2 text-primary-emphasis">Autor: ' . $item['Autor_clanku'] . '</strong>
                            <div class="col-auto d-flex d-sm-none justify-content-center">
                                <img class="rounded" src="' . $image_url . '" width="100%" height="200px" style="margin: 5px; aspect-ratio:4 / 3; object-fit: cover;">
                            </div>
                            <h3 class="mb-0">' . htmlspecialchars($item['Nazev_clanku']) . '</h3>
                            <div class="mb-1 text-body-secondary">' . $datum . '</div>
                            <p class="card-text mb-auto">' . strip_tags($item['Obsah_clanku']) . '</p>
                            <a href="#" class="icon-link gap-1 icon-link-hover stretched-link">
                                Pokračovat ve čtení
                            </a>
                        </div>
                        <div class="col-auto d-none d-sm-block">
                            <img src="' . $image_url . '" width="200" height="250" style="aspect-ratio:200 / 250; object-fit: cover;">
                        </div>
                    </div>
                </div>';
		}
		?>
	</div>
</body>

</html>