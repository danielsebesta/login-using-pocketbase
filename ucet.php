<?php
session_start();

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Ověření emailu
    if ($action == 'verify' && isset($_SESSION['user']['email'])) {
        $url = 'https://pb.smirkhat.org/api/collections/users/request-verification';
        $data = ['email' => $_SESSION['user']['email']];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        } else {
            echo '<script>alert("Byl ti odeslán ověřovací kód na email!")</script>';
        }

        curl_close($ch);
        exit();
    }

    // Odhlášení uživatele
    if ($action == 'logout') {
        session_destroy();
        setcookie("PHPSESSID", "", time() - 3600, "/");
        exit();
    }
}

$isLoggedIn = isset($_SESSION['user']);
$userData = $isLoggedIn ? $_SESSION['user'] : null;
$loginError = $registerSuccess = $registerError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        // Zpracování přihlášení
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
        curl_close($ch);

        if (json_decode($response, true)) {
            $_SESSION['user'] = json_decode($response, true)['record'];
            header("Refresh:0");
            exit();
        } else {
            $loginError = 'Neplatné přihlašovací údaje, zkuste to znovu.';
        }
    } elseif (isset($_POST['register'])) {
        // Zpracování registrace
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
                    // Získání stavu ověření uživatele
                    $apiUrl = "https://pb.smirkhat.org/api/collections/users/records/" . htmlspecialchars($userData['id']);
                    $ch = curl_init($apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $isVerified = json_decode($response, true)['verified'] ?? false;
                    ?>
                    <?php if ($isVerified) : ?>
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
                        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
                        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
                    }

                    function base32Decode($b32)
                    {
                        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
                        $binaryString = '';
                        foreach (str_split($b32) as $char) {
                            $binaryString .= str_pad(base_convert(strpos($alphabet, $char), 10, 2), 5, '0', STR_PAD_LEFT);
                        }
                        return implode('', array_map('chr', array_map('bindec', str_split($binaryString, 8))));
                    }

                    function generateTOTP($secretKey, $interval = 30)
                    {
                        $key = base32Decode($secretKey);
                        $time = pack('N*', 0) . pack('N*', floor(time() / $interval));
                        $hash = hash_hmac('sha1', $time, $key, true);
                        $offset = ord($hash[19]) & 0xf;

                        return str_pad(
                            (
                                ((ord($hash[$offset + 0]) & 0x7f) << 24) |
                                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                                (ord($hash[$offset + 3]) & 0xff)
                            ) % 1000000,
                            6,
                            '0',
                            STR_PAD_LEFT
                        );
                    }

                    $secretKey = generateSecretKey();
                    $totpCode = generateTOTP($secretKey);

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
            <br>
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
                            <label for="passwordConfirm">Potvrzení hesla:</label>
                            <input type="password" name="passwordConfirm" class="form-control" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary">Zaregistrovat se</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://smirkhat.org/assets/js/halfmoon.js"></script>
</body>

</html>
