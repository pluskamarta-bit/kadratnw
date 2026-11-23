<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rola'] !== 'zawodnik') {
	header("Location: index.php");
	exit;
}
$uid = (int)$_SESSION['user_id'];
$komunikat = '';

// Zapis nowego wpisu treningowego
if (isset($_POST['zapisz_trening'])) {
	$data = $_POST['data_treningu'];
	$tydzien = !empty($_POST['tydzien_cyklu']) ? (int)$_POST['tydzien_cyklu'] : NULL;
	$cel = $conn->real_escape_string($_POST['cel_tygodnia']);
	$typ = $conn->real_escape_string($_POST['typ_treningu']);
	$czas = (int)$_POST['czas_min'];
	$obwody = $conn->real_escape_string($_POST['obwody_serie']);
	$obciazenie = $conn->real_escape_string($_POST['obciazenie']);
	$rpe = (int)$_POST['rpe'];
	$hr = !empty($_POST['hr_spoczynkowe']) ? (int)$_POST['hr_spoczynkowe'] : NULL;
	$bark_p = (int)$_POST['bark_prawy'];
	$bark_l = (int)$_POST['bark_lewy'];
	$sen = (int)$_POST['jakosc_snu'];
	$pull = isset($_POST['pull_push_ok']) ? 1 : 0;
	$reg48 = isset($_POST['regeneracja_48h_ok']) ? 1 : 0;
	$rot = isset($_POST['rotatory_ok']) ? 1 : 0;
	$uwagi = $conn->real_escape_string($_POST['uwagi_strapping']);

	$sql = "INSERT INTO dziennik_treningowy
			(zawodnik_id, data_treningu, tydzien_cyklu, cel_tygodnia, typ_treningu, czas_min, obwody_serie, obciazenie, rpe, hr_spoczynkowe, bark_prawy, bark_lewy, jakosc_snu, pull_push_ok, regeneracja_48h_ok, rotatory_ok, uwagi_strapping)
			VALUES ($uid, '$data', " . ($tydzien ?? "NULL") . ", '$cel', '$typ', $czas, '$obwody', '$obciazenie', $rpe, " . ($hr ?? "NULL") . ", $bark_p, $bark_l, $sen, $pull, $reg48, $rot, '$uwagi')";
	if ($conn->query($sql)) {
		$komunikat = "Wpis treningowy został zapisany!";
	}
}

// Dodanie nadchodzącego turnieju
if (isset($_POST['dodaj_turniej'])) {
	$nazwa = $conn->real_escape_string($_POST['nazwa_turnieju']);
	$miejsce = $conn->real_escape_string($_POST['miejsce']);
	$od = $_POST['data_od'];
	$do = !empty($_POST['data_do']) ? "'" . $_POST['data_do'] . "'" : "NULL";
	$naw = $conn->real_escape_string($_POST['nawierzchnia']);
	$cel_s = $conn->real_escape_string($_POST['cel_startowy']);

	$conn->query("INSERT INTO turnieje (zawodnik_id, nazwa_turnieju, miejsce, data_od, data_do, nawierzchnia, cel_startowy)
				  VALUES ($uid, '$nazwa', '$miejsce', '$od', $do, '$naw', '$cel_s')");
	$komunikat = "Dodano turniej do kalendarza!";
}

// Zapis wrażeń po turnieju
if (isset($_POST['zapisz_wrazenia'])) {
	$tid = (int)$_POST['turniej_id'];
	$wrazenia = $conn->real_escape_string($_POST['wrazenia_po']);
	$conn->query("UPDATE turnieje SET wrazenia_po = '$wrazenia' WHERE id = $tid AND zawodnik_id = $uid");
	$komunikat = "Zapisano wrażenia po turnieju!";
}

$wpisy = $conn->query("SELECT * FROM dziennik_treningowy WHERE zawodnik_id = $uid ORDER BY data_treningu DESC LIMIT 14");
$turnieje = $conn->query("SELECT * FROM turnieje WHERE zawodnik_id = $uid ORDER BY data_od ASC");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Panel Zawodnika - Kadra TnW</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-3">
	<div class="container">
		<span class="navbar-brand">🎾 <?= htmlspecialchars($_SESSION['imie']) ?></span>
		<a href="logout.php" class="btn btn-outline-light btn-sm">Wyloguj</a>
	</div>
</nav>

<div class="container mb-5">
	<?php if ($komunikat): ?>
		<div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($komunikat) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
	<?php endif; ?>

	<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
		<li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#ankieta">📝 Karta Treningowa</button></li>
		<li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#turnieje">🏆 Turnieje i Wrażenia</button></li>
		<li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#historia">📊 Ostatnie wpisy</button></li>
	</ul>

	<div class="tab-content">
		<div class="tab-pane fade show active" id="ankieta">
			<form method="POST" class="card shadow-sm p-3 mb-3">
				<h5 class="text-primary border-bottom pb-2">1. Dziennik Obciążeń i Reakcji Fizjologicznych</h5>
				<div class="row g-2 mb-2">
					<div class="col-6 col-md-3"><label class="form-label">Data</label><input type="date" name="data_treningu" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
					<div class="col-6 col-md-3"><label class="form-label">Tydzień cyklu (nr)</label><input type="number" name="tydzien_cyklu" class="form-control" min="1" placeholder="np. 1, 5, 12"></div>
					<div class="col-6 col-md-3"><label class="form-label">Cel tygodnia</label><select name="cel_tygodnia" class="form-select"><option value="Adapt">Adaptacja</option><option value="Akum">Akumulacja</option><option value="Intens">Intensyfikacja</option><option value="Deload">Deload (Regeneracja)</option><option value="Turniej">Start / Turniej</option></select></div>
					<div class="col-6 col-md-3"><label class="form-label">Typ treningu</label><select name="typ_treningu" class="form-select"><option value="Tenis">Tenis (Kort)</option><option value="Siła">Siła</option><option value="Cardio">Cardio / Wydolność</option><option value="Odnowa">Odnowa / Fizjo</option><option value="Wolne">Wolne</option></select></div>
				</div>
				<div class="row g-2 mb-3">
					<div class="col-6 col-md-3"><label class="form-label">Czas (min)</label><input type="number" name="czas_min" class="form-control" value="60" min="0"></div>
					<div class="col-6 col-md-3"><label class="form-label">HR spocz. (ud./min)</label><input type="number" name="hr_spoczynkowe" class="form-control" placeholder="np. 58"></div>
					<div class="col-6 col-md-3"><label class="form-label">Obwody / Serie x Powt.</label><input type="text" name="obwody_serie" class="form-control" placeholder="np. 3x12"></div>
					<div class="col-6 col-md-3"><label class="form-label">Obciążenie (kg / guma)</label><input type="text" name="obciazenie" class="form-control" placeholder="np. guma czerwona"></div>
				</div>
				<div class="mb-3"><label class="form-label fw-bold">Skala RPE (1-10): <span id="rpeVal" class="badge bg-primary">5</span></label><input type="range" name="rpe" class="form-range" min="1" max="10" value="5" oninput="document.getElementById('rpeVal').innerText = this.value"><small class="text-muted">1-3 Bardzo lekki | 4-5 Umiarkowany | 6-7 Ciężki | 8-9 Bardzo ciężki | 10 Max</small></div>

				<h5 class="text-primary border-bottom pb-2 mt-2">2. Monitoring Barków i Regeneracji</h5>
				<div class="row g-3 mb-3">
					<div class="col-md-4"><label class="form-label">Ból PRAWY bark (0-10): <strong id="bpVal">0</strong></label><input type="range" name="bark_prawy" class="form-range" min="0" max="10" value="0" oninput="document.getElementById('bpVal').innerText = this.value"></div>
					<div class="col-md-4"><label class="form-label">Ból LEWY bark (0-10): <strong id="blVal">0</strong></label><input type="range" name="bark_lewy" class="form-range" min="0" max="10" value="0" oninput="document.getElementById('blVal').innerText = this.value"></div>
					<div class="col-md-4"><label class="form-label">Jakość Snu (1-5): <strong id="senVal">4</strong></label><input type="range" name="jakosc_snu" class="form-range" min="1" max="5" value="4" oninput="document.getElementById('senVal').innerText = this.value"></div>
				</div>
				<div class="row g-2 mb-3">
					<div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pull_push_ok" checked id="c1"><label class="form-check-label" for="c1">Balans Pull : Push (2-3x więcej ciągnięcia)</label></div></div>
					<div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="regeneracja_48h_ok" checked id="c2"><label class="form-check-label" for="c2">Regeneracja w ciągu 48h</label></div></div>
					<div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rotatory_ok" checked id="c3"><label class="form-check-label" for="c3">Ćwiczenia rotatorów</label></div></div>
				</div>
				<div class="mb-3"><label class="form-label">Uwagi / strapping</label><textarea name="uwagi_strapping" class="form-control" rows="2"></textarea></div>
				<button type="submit" name="zapisz_trening" class="btn btn-primary">Zapisz wpis treningowy</button>
			</form>
		</div>

		<div class="tab-pane fade" id="turnieje">
			<form method="POST" class="card shadow-sm p-3 mb-3">
				<h5 class="text-primary border-bottom pb-2">Dodaj turniej</h5>
				<div class="row g-2 mb-3">
					<div class="col-md-4"><label class="form-label">Nazwa turnieju</label><input type="text" name="nazwa_turnieju" class="form-control" required></div>
					<div class="col-md-4"><label class="form-label">Miejsce</label><input type="text" name="miejsce" class="form-control"></div>
					<div class="col-md-4"><label class="form-label">Nawierzchnia</label><input type="text" name="nawierzchnia" class="form-control"></div>
					<div class="col-md-4"><label class="form-label">Data od</label><input type="date" name="data_od" class="form-control" required></div>
					<div class="col-md-4"><label class="form-label">Data do</label><input type="date" name="data_do" class="form-control"></div>
					<div class="col-md-4"><label class="form-label">Cel startowy</label><input type="text" name="cel_startowy" class="form-control"></div>
				</div>
				<button type="submit" name="dodaj_turniej" class="btn btn-primary">Dodaj turniej</button>
			</form>
			<?php if ($turnieje && $turnieje->num_rows > 0): ?>
				<?php while ($turniej = $turnieje->fetch_assoc()): ?>
					<div class="card shadow-sm p-3 mb-3">
						<h5><?= htmlspecialchars($turniej['nazwa_turnieju']) ?></h5>
						<p class="mb-2">Miejsce: <?= htmlspecialchars($turniej['miejsce'] ?? '') ?> · <?= htmlspecialchars($turniej['data_od']) ?><?= !empty($turniej['data_do']) ? ' – ' . htmlspecialchars($turniej['data_do']) : '' ?> · <?= htmlspecialchars($turniej['nawierzchnia'] ?? '') ?></p>
						<p class="mb-2">Cel: <?= htmlspecialchars($turniej['cel_startowy'] ?? '') ?></p>
						<form method="POST"><input type="hidden" name="turniej_id" value="<?= (int)$turniej['id'] ?>"><label class="form-label">Wrażenia po turnieju</label><textarea name="wrazenia_po" class="form-control mb-2" rows="2"><?= htmlspecialchars($turniej['wrazenia_po'] ?? '') ?></textarea><button type="submit" name="zapisz_wrazenia" class="btn btn-outline-primary btn-sm">Zapisz wrażenia</button></form>
					</div>
				<?php endwhile; ?>
			<?php else: ?>
				<p class="text-muted">Brak turniejów w kalendarzu.</p>
			<?php endif; ?>
		</div>

		<div class="tab-pane fade" id="historia">
			<div class="card shadow-sm p-3">
				<h5 class="text-primary border-bottom pb-2">Ostatnie wpisy treningowe</h5>
				<div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Data</th><th>Trening</th><th>Czas</th><th>RPE</th><th>Ból barków (P/L)</th><th>Sen</th><th>Uwagi</th></tr></thead><tbody>
				<?php if ($wpisy && $wpisy->num_rows > 0): ?>
					<?php while ($wpis = $wpisy->fetch_assoc()): ?>
						<tr><td><?= htmlspecialchars($wpis['data_treningu']) ?></td><td><?= htmlspecialchars($wpis['typ_treningu']) ?></td><td><?= (int)$wpis['czas_min'] ?> min</td><td><?= (int)$wpis['rpe'] ?></td><td><?= (int)$wpis['bark_prawy'] ?> / <?= (int)$wpis['bark_lewy'] ?></td><td><?= (int)$wpis['jakosc_snu'] ?>/5</td><td><?= htmlspecialchars($wpis['uwagi_strapping'] ?? '') ?></td></tr>
					<?php endwhile; ?>
				<?php else: ?>
					<tr><td colspan="7" class="text-muted">Brak zapisanych wpisów.</td></tr>
				<?php endif; ?>
				</tbody></table></div>
			</div>
		</div>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
