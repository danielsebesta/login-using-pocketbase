<?php
// Zahájení session
session_start();

// Pokud je nastavena akce
if (isset($_GET['action'])) {
	$action = $_GET['action'];

	// Ověření emailu
	if ($action == 'verify' && isset($_SESSION['user']['email'])) {
		$url = 'https://pb.smirkhat.org/api/collections/users/request-verification';

		// Použijeme email přihlášeného uživatele
		$data = [
			'email' => $_SESSION['user']['email']
		];

		echo $_SESSION['user']['email'];

		// Inicializace cURL session
		$ch = curl_init($url);

		// Nastavení cURL možností
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Odeslání dat jako query string

		// Provedení POST requestu
		$response = curl_exec($ch);

		// Kontrola chyb
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		} else {
			// Výstup odpovědi ze serveru
			echo '<script>alert("Byl ti odeslán ověřovací kód na email!")</script>';
		}

		// Uzavření cURL session
		curl_close($ch);
		exit(); // Ukončení skriptu po zobrazení alertu, aby se předešlo dalšímu vykonávání kódu
	}

	// Odhlášení uživatele
	if ($action == 'logout') {
		$_SESSION = array(); // Zničení všech session dat
		setcookie("PHPSESSID", "", time() - 3600, "/"); // Vypršení session cookie
		session_destroy(); // Zničení session
		exit(); // Zajistí, že se nevykoná žádný další kód po přesměrování
	}
}

// Inicializace proměnných
$isLoggedIn = isset($_SESSION['user']);
$userData = $isLoggedIn ? $_SESSION['user'] : null;
$registerSuccess = null;
$loginError = null;
$registerError = null;

// Zpracování přihlášení
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
	$email = $_POST['email'];
	$password = $_POST['password'];

	$postData = json_encode([
		'identity' => $email,
		'password' => $password,
	]);

	// Inicializace cURL session pro přihlášení
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
		$_SESSION['user'] = $data['record']; // Uložení uživatelských dat do session
		header("Refresh:0"); // Obnovení stránky
		exit();
	} else {
		$loginError = 'Neplatné přihlašovací údaje, zkuste to znovu.';
	}
}

// Zpracování registrace
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
	$username = $_POST['username'];
	$email = $_POST['email'];
	$password = $_POST['password'];
	$passwordConfirm = $_POST['passwordConfirm'];

	if ($password !== $passwordConfirm) {
		$registerError = 'Hesla se neshodují.';
	} else {
		$postData = json_encode([
			'username' => $username,
			'email' => $email,
			'password' => $password,
			'passwordConfirm' => $passwordConfirm,
		]);

		// Inicializace cURL session pro registraci
		$ch = curl_init('https://pb.smirkhat.org/api/collections/users/records');
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

		if ($httpcode === 200 || $httpcode === 201) {
			$registerSuccess = 'Účet byl úspěšně vytvořen. Můžete se nyní přihlásit.';
		} else {
			$registerError = 'Registrace se nezdařila. Zkuste to prosím znovu.';
		}
	}
}

?>
<!DOCTYPE html>
<html lang="cs" data-bs-core="smirkhat">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SmirkHat.org</title>
	<link rel="stylesheet" href="https://smirkhat.org/assets/css/halfmoon.min.css??">
	<link rel="stylesheet" href="https://smirkhat.org/assets/css/halfmoon.elegant.css?<?php echo filemtime('/var/www/smirkhat/assets/css/halfmoon.elegant.css') ?>">
	<link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
	<link rel="preload" href="https://smirkhat.org/assets/fonts/Mona-Sans.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
</head>

<body>
	<div class="specific-w-300 mw-100 mx-auto">
		<br><br>
		<?php if ($isLoggedIn) : ?>
			<h2 class="text-center">Vítejte, <?php echo htmlspecialchars($userData['id']); ?>!</h2>
			<div class="card">
				<div class="card-body">
					<?php
					// ID uživatele z $userData
					$userID = htmlspecialchars($userData['id']);

					// API URL
					$apiUrl = "https://pb.smirkhat.org/api/collections/users/records/" . $userID;

					// Inicializace cURL session
					$ch2 = curl_init();

					// Nastavení URL
					curl_setopt($ch2, CURLOPT_URL, $apiUrl);

					// Nastavení návratu přenosu jako řetězce
					curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

					// Provedení cURL requestu
					$response = curl_exec($ch2);

					// Uzavření cURL session
					curl_close($ch2);

					// Dekódování JSON odpovědi
					$responseData2 = json_decode($response, true);

					// Kontrola, zda existuje klíč 'verified' a zda je true
					$isVerified = isset($responseData2['verified']) ? $responseData2['verified'] : false; ?>
					<?php if ($isVerified) : ?>
						<?php if (!empty($message)) : ?>
							<p><strong><?php echo htmlspecialchars($message); ?></strong></p>
						<?php endif; ?>
						<span style="color: green;">✅ Verified</span>
					<?php else : ?>
						<div class="alert alert-danger" role="alert">
							<h5 class="alert-heading">Ověř si email!</h5>
							Nemáš ověřený email, abys mohl dělat cokoliv dalšího, <a href="?action=verify" class="alert-link">ověř si ho</a>.
						</div>
					<?php endif; ?>
					
					<?php
					// Funkce pro generování náhodného tajného klíče v Base32
					function generateSecretKey($length = 16)
					{
						$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 znaky
						$secret = '';
						for ($i = 0; $i < $length; $i++) {
							$secret .= $chars[rand(0, strlen($chars) - 1)];
						}
						return $secret;
					}

					// Funkce pro dekódování Base32 (pro Google Authenticator)
					function base32Decode($b32)
					{
						$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
						$binaryString = '';
						for ($i = 0; $i < strlen($b32); $i++) {
							$binaryString .= str_pad(base_convert(strpos($alphabet, $b32[$i]), 10, 2), 5, '0', STR_PAD_LEFT);
						}
						$binaryString = str_split($binaryString, 8);
						$decoded = '';
						for ($i = 0; $i < count($binaryString); $i++) {
							$decoded .= chr(base_convert($binaryString[$i], 2, 10));
						}
						return $decoded;
					}

					// Funkce pro generování TOTP kódu
					function generateTOTP($secretKey, $interval = 30)
					{
						$key = base32Decode($secretKey);
						$time = floor(time() / $interval);
						$time = pack('N*', 0) . pack('N*', $time);
						$hash = hash_hmac('sha1', $time, $key, true);
						$offset = ord($hash[19]) & 0xf;
						$otp = (
							((ord($hash[$offset + 0]) & 0x7f) << 24) |
							((ord($hash[$offset + 1]) & 0xff) << 16) |
							((ord($hash[$offset + 2]) & 0xff) << 8) |
							(ord($hash[$offset + 3]) & 0xff)
						) % 1000000;
						return str_pad($otp, 6, '0', STR_PAD_LEFT);
					}

					$secretKey = generateSecretKey(); // Generování náhodného tajného klíče

					$totpCode = generateTOTP($secretKey); // Generování TOTP kódu

					echo "Váš TOTP kód je: $totpCode";
					?>
				</div>
				<a href="?action=logout" class="btn btn-primary">Odhlásit se</a>
			</div>
		<?php else : ?>
			<div class="card">
				<div class="card-header">
					<h2>Přihlášení</h2>
				</div>
				<div class="card-body">
					<?php if ($loginError) : ?>
						<div class="alert alert-danger"><?php echo htmlspecialchars($loginError); ?></div>
					<?php endif; ?>
					<form method="post">
						<div class="form-group">
							<label for="email">Email:</label>
							<input type="email" name="email" class="form-control" required>
						</div>
						<div class="form-group">
							<label for="password">Heslo:</label>
							<input type="password" name="password" class="form-control" required>
						</div>
						<button type="submit" name="login" class="btn btn-primary">Přihlásit se</button>
					</form>
				</div>
			</div>

			<hr>

			<div class="card">
				<div class="card-header">
					<h2>Registrace</h2>
				</div>
				<div class="card-body">
					<?php if ($registerSuccess) : ?>
						<div class="alert alert-success"><?php echo htmlspecialchars($registerSuccess); ?></div>
					<?php elseif ($registerError) : ?>
						<div class="alert alert-danger"><?php echo htmlspecialchars($registerError); ?></div>
					<?php endif; ?>
					<form method="post">
						<div class="form-group">
							<label for="username">Uživatelské jméno:</label>
							<input type="text" name="username" class="form-control" required>
						</div>
						<div class="form-group">
							<label for="email">Email:</label>
							<input type="email" name="email" class="form-control" required>
						</div>
						<div class="form-group">
							<label for="password">Heslo:</label>
							<input type="password" name="password" class="form-control" required>
						</div>
						<div class="form-group">
							<label for="passwordConfirm">Potvrďte heslo:</label>
							<input type="password" name="passwordConfirm" class="form-control" required>
						</div>
						<button type="submit" name="register" class="btn btn-primary">Registrovat se</button>
					</form>
				</div>
			</div>
		<?php endif; ?>
	</div>
</body>

</html>
