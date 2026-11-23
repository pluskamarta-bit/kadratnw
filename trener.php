<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rola'] !== 'trener') {
	header("Location: index.php");
	exit;
}
$komunikat = '';

// Dodawanie nowego zawodnika
if (isset($_POST['dodaj_zawodnika'])) {
	$imie = $conn->real_escape_string($_POST['imie_nazwisko']);
	$klasa = $conn->real_escape_string($_POST['klasa_urazu']);
	$login = $conn->real_escape_string($_POST['login']);
	$haslo = $conn->real_escape_string($_POST['haslo']);

	if ($conn->query("INSERT INTO zawodnicy (imie_nazwisko, klasa_urazu, login, haslo, rola) VALUES ('$imie', '$klasa', '$login', '$haslo', 'zawodnik')")) {
		$komunikat = "Dodano zawodnika: $imie";
	} else {
		$komunikat = "Błąd: Taki login już istnieje!";
	}
}

// Dodawanie turnieju dla zawodnika przez trenera
if (isset($_POST['dodaj_turniej_trener'])) {
	$zid = (int)$_POST['zawodnik_id'];
	$nazwa = $conn->real_escape_string($_POST['nazwa_turnieju']);
	$miejsce = $conn->real_escape_string($_POST['miejsce']);
	$od = $_POST['data_od'];
	$naw = $conn->real_escape_string($_POST['nawierzchnia']);
	$conn->query("INSERT INTO turnieje (zawodnik_id, nazwa_turnieju, miejsce, data_od, nawierzchnia) VALUES ($zid, '$nazwa', '$miejsce', '$od', '$naw')");
	$komunikat = "Przypisano turniej zawodnikowi!";
}

$zawodnicy = $conn->query("SELECT * FROM zawodnicy WHERE rola = 'zawodnik' ORDER BY imie_nazwisko");
$raporty = $conn->query("SELECT d.*, z.imie_nazwisko, z.klasa_urazu FROM dziennik_treningowy d JOIN zawodnicy z ON d.zawodnik_id = z.id ORDER BY d.data_treningu DESC LIMIT 30");
$turnieje_all = $conn->query("SELECT t.*, z.imie_nazwisko FROM turnieje t JOIN zawodnicy z ON t.zawodnik_id = z.id ORDER BY t.data_od DESC");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Panel Trenera - Kadra TnW</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-3">
	<div class="container">
		<span class="navbar-brand">🎾 Panel Trenera Kadry TnW</span>
		<a href="logout.php" class="btn btn-outline-light btn-sm">Wyloguj</a>
	</div>
</nav>

<div class="container mb-5">
	<?php if ($komunikat): ?>
		<div class="alert alert-info"><?= $komunikat ?></div>
	<?php endif; ?>

	<!-- MONITORING TRENINGÓW -->
	<div class="card shadow-sm mb-4">
		<div class="card-header bg-primary text-white fw-bold">📊 Ostatnie raporty treningowe i zdrowotne</div>
		<div class="table-responsive">
			<table class="table table-hover table-sm mb-0">
				<thead class="table-light">
					<tr>
						<th>Data</th><th>Zawodnik</th><th>Tydz/Cel</th><th>Typ</th><th>Czas / RPE</th><th>HR sp.</th><th>Barki (P/L)</th><th>Sen</th><th>Uwagi / Strapping</th>
					</tr>
				</thead>
				<tbody>
				<?php while($r = $raporty->fetch_assoc()):
					$alert = ($r['bark_prawy'] >= 4 || $r['bark_lewy'] >= 4) ? 'table-danger' : '';
				?>
					<tr class="<?= $alert ?>">
						<td><?= $r['data_treningu'] ?></td>
						<td><strong><?= htmlspecialchars($r['imie_nazwisko']) ?></strong> <small class="text-muted">(<?= $r['klasa_urazu'] ?>)</small></td>
						<td><?= $r['tydzien_cyklu'] ? 'T'.$r['tydzien_cyklu'] : '' ?> <?= $r['cel_tygodnia'] ?></td>
						<td><?= $r['typ_treningu'] ?></td>
						<td><?= $r['czas_min'] ?>m / RPE <?= $r['rpe'] ?></td>
						<td><?= $r['hr_spoczynkowe'] ?: '-' ?></td>
						<td><strong><?= $r['bark_prawy'] ?> / <?= $r['bark_lewy'] ?></strong></td>
						<td><?= $r['jakosc_snu'] ?>/5</td>
						<td class="small"><?= htmlspecialchars($r['uwagi_strapping']) ?></td>
					</tr>
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<!-- DODAJ ZAWODNIKA -->
		<div class="col-md-6">
			<div class="card shadow-sm p-3 h-100">
				<h5>➕ Dodaj nowego zawodnika</h5>
				<form method="POST" class="row g-2">
					<div class="col-md-8"><input type="text" name="imie_nazwisko" class="form-control" placeholder="Imię i nazwisko" required></div>
					<div class="col-md-4"><input type="text" name="klasa_urazu" class="form-control" placeholder="Klasa (np. Open)"></div>
					<div class="col-md-5"><input type="text" name="login" class="form-control" placeholder="Login" required></div>
					<div class="col-md-5"><input type="text" name="haslo" class="form-control" placeholder="Hasło" required></div>
					<div class="col-md-2"><button type="submit" name="dodaj_zawodnika" class="btn btn-success w-100">Dodaj</button></div>
				</form>
			</div>
		</div>

		<!-- PRZYPISZ TURNIEJ -->
		<div class="col-md-6">
			<div class="card shadow-sm p-3 h-100">
				<h5>🏆 Zaplanuj turniej zawodnikowi</h5>
				<form method="POST" class="row g-2">
					<div class="col-md-6">
						<select name="zawodnik_id" class="form-select" required>
							<option value="">-- Wybierz zawodnika --</option>
							<?php while($z = $zawodnicy->fetch_assoc()): ?>
								<option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['imie_nazwisko']) ?></option>
							<?php endwhile; ?>
						</select>
					</div>
					<div class="col-md-6"><input type="text" name="nazwa_turnieju" class="form-control" placeholder="Nazwa turnieju" required></div>
					<div class="col-md-4"><input type="text" name="miejsce" class="form-control" placeholder="Miasto"></div>
					<div class="col-md-4"><input type="date" name="data_od" class="form-control" required></div>
					<div class="col-md-4"><button type="submit" name="dodaj_turniej_trener" class="btn btn-primary w-100">Zapisz turniej</button></div>
				</form>
			</div>
		</div>
	</div>

	<!-- KALENDARZ TURNIEJÓW I WRAŻENIA -->
	<div class="card shadow-sm">
		<div class="card-header bg-secondary text-white fw-bold">🏆 Turnieje kadry i wrażenia zawodników</div>
		<div class="table-responsive">
			<table class="table table-sm mb-0">
				<thead><tr><th>Data</th><th>Zawodnik</th><th>Turniej</th><th>Miejsce</th><th>Wrażenia po turnieju</th></tr></thead>
				<tbody>
				<?php while($ta = $turnieje_all->fetch_assoc()): ?>
					<tr>
						<td><?= $ta['data_od'] ?></td>
						<td><strong><?= htmlspecialchars($ta['imie_nazwisko']) ?></strong></td>
						<td><?= htmlspecialchars($ta['nazwa_turnieju']) ?></td>
						<td><?= htmlspecialchars($ta['miejsce']) ?></td>
						<td class="small"><?= nl2br(htmlspecialchars($ta['wrazenia_po'] ?: 'Oczekuje na relację...')) ?></td>
					</tr>
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
</body>
</html>
