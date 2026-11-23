<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['rola'] ?? '') !== 'trener') {
	header('Location: index.php');
	exit;
}
$komunikat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_zawodnika'])) {
	$imie = $conn->real_escape_string(trim($_POST['imie_nazwisko'] ?? ''));
	$klasa = $conn->real_escape_string(trim($_POST['klasa_urazu'] ?? ''));
	$login = $conn->real_escape_string(trim($_POST['login'] ?? ''));
	$haslo = $conn->real_escape_string($_POST['haslo'] ?? '');
	if ($conn->query("INSERT INTO zawodnicy (imie_nazwisko, klasa_urazu, login, haslo, rola) VALUES ('$imie', '$klasa', '$login', '$haslo', 'zawodnik')")) {
		$komunikat = 'Dodano zawodnika: ' . $imie;
	} else {
		$komunikat = 'Błąd: Taki login już istnieje!';
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_turniej_trener'])) {
	$zid = (int)($_POST['zawodnik_id'] ?? 0);
	$nazwa = $conn->real_escape_string(trim($_POST['nazwa_turnieju'] ?? ''));
	$miejsce = $conn->real_escape_string(trim($_POST['miejsce'] ?? ''));
	$od = $conn->real_escape_string($_POST['data_od'] ?? '');
	$dataDo = !empty($_POST['data_do']) ? "'" . $conn->real_escape_string($_POST['data_do']) . "'" : 'NULL';
	$naw = $conn->real_escape_string(trim($_POST['nawierzchnia'] ?? ''));
	$cel = $conn->real_escape_string(trim($_POST['cel_startowy'] ?? ''));
	if ($conn->query("INSERT INTO turnieje (zawodnik_id, nazwa_turnieju, miejsce, data_od, data_do, nawierzchnia, cel_startowy) VALUES ($zid, '$nazwa', '$miejsce', '$od', $dataDo, '$naw', '$cel')")) {
		$komunikat = 'Przypisano turniej zawodnikowi!';
	} else {
		$komunikat = 'Nie udało się przypisać turnieju.';
	}
}

$filtr_id = isset($_GET['zawodnik']) ? (int)$_GET['zawodnik'] : 0;
$where_sql = $filtr_id > 0 ? "WHERE d.zawodnik_id = $filtr_id" : '';
$zawodnicy = $conn->query("SELECT * FROM zawodnicy WHERE rola = 'zawodnik' ORDER BY imie_nazwisko");
$zawodnicy_lista = $conn->query("SELECT * FROM zawodnicy WHERE rola = 'zawodnik' ORDER BY imie_nazwisko");
$raporty = $conn->query("SELECT d.*, z.imie_nazwisko, z.klasa_urazu FROM dziennik_treningowy d JOIN zawodnicy z ON d.zawodnik_id = z.id $where_sql ORDER BY d.data_treningu DESC, d.id DESC LIMIT 50");
$turnieje_all = $conn->query("SELECT t.*, z.imie_nazwisko FROM turnieje t JOIN zawodnicy z ON t.zawodnik_id = z.id ORDER BY t.data_od DESC");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Panel Trenera - Tenis Bez Barier</title>
	<link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar">
	<div class="navbar-brand">
		<img src="logo.png" alt="Tenis Bez Barier" class="navbar-logo">
		<strong style="border-left:2px solid #cbd5e1;padding-left:12px;color:#136194">KADRA TENISA NA WÓZKACH | Panel Trenera</strong>
	</div>
	<a href="logout.php" class="btn btn-outline-light">Wyloguj</a>
</nav>

<main class="container">
	<?php if ($komunikat !== ''): ?>
		<div class="alert alert-info"><?= htmlspecialchars($komunikat, ENT_QUOTES, 'UTF-8') ?></div>
	<?php endif; ?>

	<div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
		<strong>Filtruj zawodnika:</strong>
		<a href="trener.php" class="btn <?= $filtr_id === 0 ? 'btn-primary' : 'btn-outline-primary' ?>">Wszyscy</a>
		<?php while ($zl = $zawodnicy_lista->fetch_assoc()): ?>
			<a href="trener.php?zawodnik=<?= (int)$zl['id'] ?>" class="btn <?= $filtr_id === (int)$zl['id'] ? 'btn-primary' : 'btn-outline-primary' ?>"><?= htmlspecialchars($zl['imie_nazwisko'], ENT_QUOTES, 'UTF-8') ?></a>
		<?php endwhile; ?>
	</div>

	<section class="card">
		<div class="card-header">📊 Kompletny Dziennik Obciążeń, Barków i Regeneracji</div>
		<div class="table-responsive"><table class="table table-hover">
			<thead><tr><th>Data / Cykl</th><th>Zawodnik</th><th>Trening i Objętość</th><th>Obciążenie / RPE / sRPE</th><th>HR spocz.</th><th>Barki (P / L) &amp; Sen</th><th>Prewencja Barków</th><th>Uwagi / Strapping</th></tr></thead>
			<tbody>
			<?php while ($r = $raporty->fetch_assoc()):
				$alert = ($r['bark_prawy'] >= 4 || $r['bark_lewy'] >= 4) ? 'table-danger' : '';
				$srpe = (int)$r['czas_min'] * (int)$r['rpe'];
			?>
				<tr class="<?= $alert ?>">
					<td><strong><?= htmlspecialchars($r['data_treningu'], ENT_QUOTES, 'UTF-8') ?></strong><br><span class="badge badge-info"><?= $r['tydzien_cyklu'] ? 'Tydz. ' . (int)$r['tydzien_cyklu'] : 'Brak nr' ?> | <?= htmlspecialchars($r['cel_tygodnia'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
					<td><strong><?= htmlspecialchars($r['imie_nazwisko'], ENT_QUOTES, 'UTF-8') ?></strong><br><small style="color:#64748b">Klasa: <?= htmlspecialchars($r['klasa_urazu'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small></td>
					<td><strong><?= htmlspecialchars($r['typ_treningu'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= (int)$r['czas_min'] ?> min)<br><small>Serie: <?= htmlspecialchars($r['obwody_serie'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small></td>
					<td><small>Ciężar/Guma: <?= htmlspecialchars($r['obciazenie'] ?: '-', ENT_QUOTES, 'UTF-8') ?></small><br>RPE: <strong><?= htmlspecialchars((string)$r['rpe'], ENT_QUOTES, 'UTF-8') ?>/10</strong> | sRPE: <strong><?= $srpe ?></strong></td>
					<td><strong><?= $r['hr_spoczynkowe'] ? (int)$r['hr_spoczynkowe'] . ' ud.' : '-' ?></strong></td>
					<td>Prawy: <strong style="color:<?= $r['bark_prawy'] >= 4 ? '#dc2626' : 'inherit' ?>"><?= htmlspecialchars((string)$r['bark_prawy'], ENT_QUOTES, 'UTF-8') ?>/10</strong><br>Lewy: <strong style="color:<?= $r['bark_lewy'] >= 4 ? '#dc2626' : 'inherit' ?>"><?= htmlspecialchars((string)$r['bark_lewy'], ENT_QUOTES, 'UTF-8') ?>/10</strong><br>Sen: <strong><?= htmlspecialchars((string)$r['jakosc_snu'], ENT_QUOTES, 'UTF-8') ?>/5</strong></td>
					<td><span class="badge <?= $r['pull_push_ok'] ? 'badge-ok' : 'badge-warn' ?>">Pull:Push <?= $r['pull_push_ok'] ? 'OK' : 'BRAK' ?></span><br><span class="badge <?= $r['regeneracja_48h_ok'] ? 'badge-ok' : 'badge-warn' ?>">48h <?= $r['regeneracja_48h_ok'] ? 'OK' : 'BRAK' ?></span><br><span class="badge <?= $r['rotatory_ok'] ? 'badge-ok' : 'badge-warn' ?>">Rotatory <?= $r['rotatory_ok'] ? 'OK' : 'BRAK' ?></span></td>
					<td style="max-width:220px"><?= nl2br(htmlspecialchars($r['uwagi_strapping'] ?: '-', ENT_QUOTES, 'UTF-8')) ?></td>
				</tr>
			<?php endwhile; ?>
			</tbody>
		</table></div>
	</section>

	<div class="row">
		<div class="col-md-6"><section class="card">
			<div class="card-header bg-secondary">➕ Dodaj nowego zawodnika</div>
			<div class="card-body"><form method="POST">
				<label class="form-label">Imię i nazwisko</label><input type="text" name="imie_nazwisko" class="form-control" required>
				<label class="form-label">Poziom urazu / Klasa startowa</label><input type="text" name="klasa_urazu" class="form-control">
				<div class="row"><div class="col-md-6"><label class="form-label">Login</label><input type="text" name="login" class="form-control" required></div>
				<div class="col-md-6"><label class="form-label">Hasło</label><input type="password" name="haslo" class="form-control" required></div></div>
				<button type="submit" name="dodaj_zawodnika" class="btn btn-success w-100">Utwórz konto zawodnika</button>
			</form></div>
		</section></div>

		<div class="col-md-6"><section class="card">
			<div class="card-header bg-secondary">🏆 Zaplanuj turniej zawodnikowi</div>
			<div class="card-body"><form method="POST">
				<label class="form-label">Zawodnik</label><select name="zawodnik_id" class="form-control" required>
					<?php while ($z = $zawodnicy->fetch_assoc()): ?><option value="<?= (int)$z['id'] ?>"><?= htmlspecialchars($z['imie_nazwisko'], ENT_QUOTES, 'UTF-8') ?></option><?php endwhile; ?>
				</select>
				<label class="form-label">Nazwa turnieju</label><input type="text" name="nazwa_turnieju" class="form-control" required>
				<label class="form-label">Miejsce</label><input type="text" name="miejsce" class="form-control" required>
				<div class="row"><div class="col-md-6"><label class="form-label">Data od</label><input type="date" name="data_od" class="form-control" required></div>
				<div class="col-md-6"><label class="form-label">Data do</label><input type="date" name="data_do" class="form-control"></div></div>
				<label class="form-label">Nawierzchnia</label><input type="text" name="nawierzchnia" class="form-control">
				<label class="form-label">Cel startowy</label><textarea name="cel_startowy" class="form-control" rows="2"></textarea>
				<button type="submit" name="dodaj_turniej_trener" class="btn btn-success w-100">Przypisz turniej</button>
			</form></div>
		</section></div>
	</div>

	<section class="card">
		<div class="card-header">🏆 Zaplanowane turnieje</div>
		<div class="table-responsive"><table class="table table-hover">
			<thead><tr><th>Zawodnik</th><th>Turniej</th><th>Miejsce</th><th>Termin</th><th>Nawierzchnia</th><th>Cel startowy</th></tr></thead>
			<tbody><?php while ($t = $turnieje_all->fetch_assoc()): ?><tr>
				<td><?= htmlspecialchars($t['imie_nazwisko'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($t['nazwa_turnieju'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($t['miejsce'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($t['data_od'], ENT_QUOTES, 'UTF-8') ?><?= !empty($t['data_do']) ? ' – ' . htmlspecialchars($t['data_do'], ENT_QUOTES, 'UTF-8') : '' ?></td>
				<td><?= htmlspecialchars($t['nawierzchnia'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= nl2br(htmlspecialchars($t['cel_startowy'] ?: '-', ENT_QUOTES, 'UTF-8')) ?></td>
			</tr><?php endwhile; ?></tbody>
		</table></div>
	</section>
</main>
</body>
</html>
