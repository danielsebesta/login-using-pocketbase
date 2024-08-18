<?php
session_start();

if (isset($_GET['action'])) {
	$action = $_GET['action'];

	if ($action == 'verify' && isset($_SESSION['user']['email'])) {
		$url = 'https://pb.smirkhat.org/api/collections/users/request-verification';

		// Použijeme email přihlášeného uživatele
		$data = [
			'email' => $_SESSION['user']['email']
		];

		echo $_SESSION['user']['email'];

		// Initialize cURL session
		$ch = curl_init($url);

		// Set cURL options
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Send data as query string

		// Execute the POST request
		$response = curl_exec($ch);

		// Check for errors
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		} else {
			// Output the response from the server
			echo '<script>alert("Byl ti odeslán ověřovací kód na email!")</script>';
		}

		// Close cURL session
		curl_close($ch);
		exit(); // Exit after displaying alert to prevent further script execution
	}

	if ($action == 'logout') {
		$_SESSION = array(); // Destroy all session data
		setcookie("PHPSESSID", "", time() - 3600, "/"); // Expire the cookie
		session_destroy(); // Destroy the session
		exit(); // Ensure no further code is executed after redirect
	}
}



// Initialize variables
$isLoggedIn = isset($_SESSION['user']);
$userData = $isLoggedIn ? $_SESSION['user'] : null;
$registerSuccess = null;
$loginError = null;
$registerError = null;

// Handle login form submission
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
		header("Refresh:0");
		exit();
	} else {
		$loginError = 'Neplatné přihlašovací údaje, zkuste to znovu.';
	}
}


// Handle registration form submission
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
					// User ID from $userData
					$userID = htmlspecialchars($userData['id']);

					// API URL
					$apiUrl = "https://pb.smirkhat.org/api/collections/users/records/" . $userID;

					// Initialize cURL session
					$ch2 = curl_init();

					// Set the URL
					curl_setopt($ch2, CURLOPT_URL, $apiUrl);

					// Set to return the transfer as a string
					curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

					// Execute the cURL request
					$response = curl_exec($ch2);

					// Close the cURL session
					curl_close($ch2);

					// Decode the JSON response
					$responseData2 = json_decode($response, true);

					// Check if 'verified' key exists and is true
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

					// Function to generate a random secret key in Base32
					function generateSecretKey($length = 16)
					{
						$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
						$secret = '';
						for ($i = 0; $i < $length; $i++) {
							$secret .= $chars[rand(0, strlen($chars) - 1)];
						}
						return $secret;
					}

					// Function to decode Base32 (for Google Authenticator)
					function base32Decode($b32)
					{
						$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
						$b32 = strtoupper($b32);
						$binary = '';
						foreach (str_split($b32) as $char) {
							$binary .= str_pad(base_convert(strpos($alphabet, $char), 10, 2), 5, '0', STR_PAD_LEFT);
						}
						$binary = str_split($binary, 8);
						$output = '';
						foreach ($binary as $bin) {
							$output .= chr(bindec($bin));
						}
						return $output;
					}

					// Function to generate TOTP code
					function generateTotp($secret, $timeStep = 30, $digits = 6)
					{
						$time = floor(time() / $timeStep);
						$time = str_pad(pack('N*', $time), 8, chr(0), STR_PAD_LEFT);
						$secret = base32Decode($secret);

						$hash = hash_hmac('sha1', $time, $secret, true);
						$offset = ord(substr($hash, -1)) & 0xF;

						$otp = (ord($hash[$offset + 0]) & 0x7F) << 24 |
							(ord($hash[$offset + 1]) & 0xFF) << 16 |
							(ord($hash[$offset + 2]) & 0xFF) << 8 |
							(ord($hash[$offset + 3]) & 0xFF);

						$otp = $otp % pow(10, $digits);

						return str_pad($otp, $digits, '0', STR_PAD_LEFT);
					}

					// Function to verify the TOTP code
					function verifyTotp($secret, $code, $timeStep = 30, $tolerance = 1, $digits = 6)
					{
						$time = floor(time() / $timeStep);

						for ($i = -$tolerance; $i <= $tolerance; ++$i) {
							$generatedCode = generateTotp($secret, $timeStep, $digits);
							if ($generatedCode === $code) {
								return true;
							}
						}

						return false;
					}

					// Handle form submission
					$message = '';
					if ($_SERVER['REQUEST_METHOD'] === 'POST') {
						$codeFromUser = $_POST['auth_code'] ?? '';
						$secret = $_POST['secret'] ?? '';

						if (verifyTotp($secret, $codeFromUser)) {
							echo "<script>alert('Byl jsi ověřen!'); window.location.href = 'https://smirkhat.org';</script>";
							exit; // Stop execution
						} else {
							$message = "Neplatný kód, zkus to znovu!";
						}
					}
					?>

					<script>
						document.addEventListener("DOMContentLoaded", function() {
							let secret = localStorage.getItem('auth_secret');
							if (!secret) {
								secret = "<?php echo generateSecretKey(); ?>";
								localStorage.setItem('auth_secret', secret);
								const user = "test@smirkhat.org"; // You can replace this with dynamic username
								const issuer = "SmirkHat.org"; // Replace with your app's name
								const otpUri = `otpauth://totp/${issuer}:${user}?secret=${secret}&issuer=${issuer}`;
								const qrCodeUrl = `https://smirkhat.org/qrcode.php?s=qr&w=512&h=512&p=0&wq=0&d=${encodeURIComponent(otpUri)}`;
								document.getElementById('qrcode').src = qrCodeUrl;
								document.getElementById('display_secret').innerText = secret;
								document.getElementById('secret').value = secret;
								document.getElementById('setup').style.display = 'block';
								document.getElementById('verification').style.display = 'none';
							} else {
								document.getElementById('setup').style.display = 'none';
								document.getElementById('verification').style.display = 'block';
							}
						});
					</script>

					<h1>2FA testování</h1>

					<div id="setup">
						<p>Naskenuj tento QR kód s jakoukoliv Authenticator aplikací:</p>
						<img id="qrcode" alt="QR Code" />

						<p>Nebo zadej tento kód manuálně: <strong id="display_secret"></strong></p>
					</div>

					<div id="verification" style="display:none;">
						<p>Máte již nastavenou 2FA. Zadejte kód vygenerovaný z aplikace:</p>
					</div>

					<form method="post" action="">
						<input type="hidden" id="secret" name="secret">
						<label for="auth_code">Sem zadej kód vygenerovaný z aplikace:</label><br>
						<input type="text" id="auth_code" name="auth_code" required><br><br>
						<a type="submit" class="btn btn-secondary" value="Ověřit">Ověřit</a>					</form><br>
					<h5 class="card-title">Údaje o uživateli</h5>

					<p><strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?></p>
					<p><strong>ID:</strong> <?php echo htmlspecialchars($userData['id']); ?></p>
					<p><strong>Username:</strong> <?php echo htmlspecialchars($userData['username']); ?></p>

					<!-- Přidejte další údaje, které chcete zobrazit -->
				</div>
			</div>
			<a href="?action=logout" class="btn btn-danger mt-3">Odhlásit se</a>
		<?php else : ?>
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

			<hr>

			<h2 class="text-center">Registrace</h2>
			<?php if ($registerSuccess) {
				echo '<div class="alert alert-success">' . $registerSuccess . '</div>';
			} ?>
			<?php if ($registerError) {
				echo '<div class="alert alert-danger">' . $registerError . '</div>';
			} ?>
			<form action="" method="POST">
				<div class="mb-3">
					<label class="form-label" for="username">Uživatelské jméno</label>
					<input type="text" class="form-control" id="username" name="username" required>
				</div>
				<div class="mb-3">
					<label class="form-label" for="email">E-mailová adresa</label>
					<input type="email" class="form-control" id="email" name="email" required>
				</div>
				<div class="mb-3">
					<label class="form-label" for="password">Heslo</label>
					<input type="password" class="form-control" id="password" name="password" required>
				</div>
				<div class="mb-3">
					<label class="form-label" for="passwordConfirm">Potvrzení hesla</label>
					<input type="password" class="form-control" id="passwordConfirm" name="passwordConfirm" required>
				</div>
				<div class="d-flex align-items-center">
					<button type="submit" class="btn btn-secondary ms-auto" name="register">Registrovat se</button>
				</div>
			</form>
		<?php endif; ?>
	</div>
</body>

</html>