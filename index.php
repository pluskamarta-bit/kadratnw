<?php
require 'db.php';
$blad = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$login = $conn->real_escape_string($_POST['login']);
	$haslo = $_POST['haslo'];

	$res = $conn->query("SELECT * FROM zawodnicy WHERE login = '$login'");
	if ($res->num_rows === 1) {
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
	<link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
<div class="container" style="max-width: 400px;">
	<div class="card shadow">
		<div class="card-header bg-primary text-white text-center py-3">
			<h5 class="mb-0">🎾 Kadra Tenisa na Wózkach</h5>
			<small>Dziennik Obciążeń i Startów</small>
		</div>
		<div class="card-body p-4">
			<?php if ($blad): ?>
				<div class="alert alert-danger"><?= $blad ?></div>
			<?php endif; ?>
			<form method="POST">
				<div class="mb-3">
					<label class="form-label">Login</label>
					<input type="text" name="login" class="form-control" required placeholder="np. janek lub trener">
				</div>
				<div class="mb-3">
					<label class="form-label">Hasło</label>
					<input type="password" name="haslo" class="form-control" required placeholder="••••••••">
				</div>
				<button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
			</form>
		</div>
	</div>
</div>
</body>
</html>
