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
		<p>5/8 - made the website responsive and made it only for login/register, no other stuff</p>
	</center>
	<br>

	<div class="container">
		<div class="specific-w-300 mw-100 mx-auto">
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