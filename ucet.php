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
		<p>6/8 - added register and made it so you can create new accounts</p>
	</center>
	<br>

	<div class="container">
		<div class="specific-w-300 mw-100 mx-auto">
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

			<a href="?action=logout" class="btn btn-danger mt-3">Odhlásit se</a>
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
		</div>
	</div>
</body>

</html>