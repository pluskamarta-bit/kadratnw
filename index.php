<?php
require 'db.php';
$blad = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$login = $conn->real_escape_string($_POST['login']);
	$haslo = $_POST['haslo'];

	$res = $conn->query("SELECT * FROM zawodnicy WHERE login = '$login'");
	if ($res && $res->num_rows === 1) {
		$user = $res->fetch_assoc();
		if ($haslo === $user['haslo']) {
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['imie'] = $user['imie_nazwisko'];
			$_SESSION['rola'] = $user['rola'];

			if ($user['rola'] === 'trener') {
				header("Location: trener.php");
			} else {
				header("Location: zawodnik.php");
			}
			exit;
		}
	}
	$blad = "Nieprawidłowy login lub hasło.";
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kadra TnW - Logowanie</title>
	<link href="style.css" rel="stylesheet">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 16px;">
<div style="width: 100%; max-width: 420px;">
	<div class="card">
		<div style="background: #ffffff; padding: 24px 20px 16px; text-align: center; border-bottom: 4px solid #f27d16;">
			<img src="logo.png" alt="Tenis Bez Barier" style="max-width: 240px; height: auto; margin-bottom: 12px;">
			<div style="display: flex; align-items: center; justify-content: center; gap: 8px; color: #1b81c3; font-weight: 800; font-size: 0.95rem; letter-spacing: 0.4px; text-transform: uppercase;">
				<svg width="26" height="28" viewBox="0 0 64 70" fill="none" xmlns="http://www.w3.org">
					<ellipse cx="34" cy="11" rx="5" ry="8" transform="rotate(-15 34 11)" stroke="#f27d16" stroke-width="3"/>
					<line x1="35" y1="19" x2="36" y2="30" stroke="#f27d16" stroke-width="3" stroke-linecap="round"/>
					<circle cx="28" cy="24" r="4.5" fill="#f27d16"/>
					<path d="M23 30 L34 29 L38 43 L47 45 L45 56" stroke="#f27d16" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
					<circle cx="27" cy="52" r="11" stroke="#f27d16" stroke-width="4.5"/>
					<circle cx="27" cy="52" r="3" fill="#f27d16"/>
					<circle cx="47" cy="60" r="3.5" fill="#f27d16"/>
				</svg>
				<span>Kadra Tenisa na Wózkach</span>
			</div>
			<div style="color: #3ab575; font-weight: 600; font-size: 0.82rem; margin-top: 4px;">Dziennik Obciążeń i Startów</div>
		</div>
		<div class="card-body">
			<?php if ($blad): ?>
				<div class="alert alert-danger"><?= $blad ?></div>
			<?php endif; ?>
			<form method="POST">
				<label class="form-label">Login</label>
				<input type="text" name="login" class="form-control" required placeholder="np. janek lub trener">
				<label class="form-label">Hasło</label>
				<input type="password" name="haslo" class="form-control" required placeholder="••••••••">
				<button type="submit" class="btn btn-primary w-100" style="margin-top: 8px;">Zaloguj się</button>
			</form>
		</div>
	</div>
</div>
</body>
</html>
