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

<!DOCTYPE html>
<html lang="cs">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>2FA testování</title>
	<style>
		html {
			font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
		}
	</style>
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
</head>

<body>

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
		<input type="submit" value="Ověřit">
	</form>

	<?php if (!empty($message)) : ?>
		<p><strong><?php echo htmlspecialchars($message); ?></strong></p>
	<?php endif; ?>

</body>

</html>