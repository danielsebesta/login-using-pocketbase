<?php
session_start();

// Handle logout if action is set to 'logout'
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
	$_SESSION = array(); // destroy all session data
	setcookie("PHPSESSID", "", time() - 3600, "/");
	session_destroy();
	header('Location: index.php');
	exit();
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
		header('Location: index.php');
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
	<link rel="stylesheet" href="./assets/css/halfmoon.min.css??">
	<link rel="stylesheet" href="./assets/css/halfmoon.elegant.css?<?php echo filemtime('/var/www/smirkhat/assets/css/halfmoon.elegant.css') ?>">
	<link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
	<link rel="preload" href="./assets/fonts/Mona-Sans.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
</head>

<body>
	<div class="specific-w-300 mw-100 mx-auto">
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
						<span style="color: green;">✅ Verified</span>
					<?php else : ?>

						<div class="alert alert-danger" role="alert">

							<h5 class="alert-heading">Ověř si email!</h5>

							Nemáš ověřený email, abys mohl dělat cokoliv dalšího, <a href="?action=verify" class="alert-link">ověř si ho</a>.

						</div>
					<?php endif; ?>
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
			<?php
			if (isset($_GET['action']) && $_GET['action'] == 'verify') {
				$url = 'https://pb.smirkhat.org/api/collections/users/request-verification';

				// The data to send in the POST request
				$data = [
					'email' => 'dastplast@smirkhat.org'
				];

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
					echo 'Response:' . $response;
				}

				// Close cURL session
				curl_close($ch);
			} ?>
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