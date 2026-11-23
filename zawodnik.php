<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rola'] !== 'zawodnik') {
	header('Location: index.php');
	exit;
}
$uid = (int)$_SESSION['user_id'];
$komunikat = '';

if (isset($_POST['zapisz_trening'])) {
	$data = $conn->real_escape_string($_POST['data_treningu']);
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
		VALUES ($uid, '$data', " . ($tydzien ?? 'NULL') . ", '$cel', '$typ', $czas, '$obwody', '$obciazenie', $rpe, " . ($hr ?? 'NULL') . ", $bark_p, $bark_l, $sen, $pull, $reg48, $rot, '$uwagi')";
	if ($conn->query($sql)) {
		$komunikat = 'Wpis treningowy został zapisany!';
	}
}

if (isset($_POST['dodaj_turniej'])) {
	$nazwa = $conn->real_escape_string($_POST['nazwa_turnieju']);
	$miejsce = $conn->real_escape_string($_POST['miejsce']);
	$od = $conn->real_escape_string($_POST['data_od']);
	$do = !empty($_POST['data_do']) ? "'" . $conn->real_escape_string($_POST['data_do']) . "'" : 'NULL';
	$naw = $conn->real_escape_string($_POST['nawierzchnia']);
	$cel_s = $conn->real_escape_string($_POST['cel_startowy']);
	if ($conn->query("INSERT INTO turnieje (zawodnik_id, nazwa_turnieju, miejsce, data_od, data_do, nawierzchnia, cel_startowy) VALUES ($uid, '$nazwa', '$miejsce', '$od', $do, '$naw', '$cel_s')")) {
		$komunikat = 'Dodano turniej do kalendarza!';
	}
}

if (isset($_POST['zapisz_wrazenia'])) {
	$tid = (int)$_POST['turniej_id'];
	$wrazenia = $conn->real_escape_string($_POST['wrazenia_po']);
	if ($conn->query("UPDATE turnieje SET wrazenia_po = '$wrazenia' WHERE id = $tid AND zawodnik_id = $uid")) {
		$komunikat = 'Zapisano wrażenia po turnieju!';
	}
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
	<link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar">
	<div class="navbar-brand">
		<img src="logo.png" alt="Tenis Bez Barier" class="navbar-logo">
		<span style="display:inline-flex;align-items:center;gap:6px;border-left:2px solid #cbd5e1;padding-left:12px;">
			<svg width="22" height="24" viewBox="0 0 64 70" fill="none" xmlns="http://www.w3.org/2000/svg">
				<ellipse cx="34" cy="11" rx="5" ry="8" transform="rotate(-15 34 11)" stroke="#f27d16" stroke-width="3.5"/><line x1="35" y1="19" x2="36" y2="30" stroke="#f27d16" stroke-width="3.5" stroke-linecap="round"/><circle cx="28" cy="24" r="4.5" fill="#f27d16"/><path d="M23 30 L34 29 L38 43 L47 45 L45 56" stroke="#f27d16" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="27" cy="52" r="11" stroke="#f27d16" stroke-width="4.5"/><circle cx="47" cy="60" r="3.5" fill="#f27d16"/>
			</svg>
			<?= htmlspecialchars($_SESSION['imie'], ENT_QUOTES, 'UTF-8') ?>
		</span>
	</div>
	<a href="logout.php" class="btn btn-outline-light">Wyloguj</a>
</nav>

<div class="container">
	<?php if ($komunikat): ?><div class="alert alert-success"><?= htmlspecialchars($komunikat, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
	<ul class="nav-pills">
		<li><button type="button" class="active" data-target="#ankieta">📝 Karta Treningowa</button></li>
		<li><button type="button" data-target="#turnieje">🏆 Turnieje i Wrażenia</button></li>
		<li><button type="button" data-target="#historia">📊 Ostatnie wpisy</button></li>
	</ul>

	<div id="ankieta" class="tab-pane active">
		<form method="POST" class="card">
			<h3 style="color:#1b81c3;margin-top:0;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">1. Dziennik Obciążeń i Reakcji Fizjologicznych</h3>
			<div class="row">
				<div class="col-md-3"><label class="form-label">Data</label><input type="date" name="data_treningu" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
				<div class="col-md-3"><label class="form-label">Tydzień cyklu (nr)</label><input type="number" name="tydzien_cyklu" class="form-control" min="1" placeholder="np. 1, 5, 12"></div>
				<div class="col-md-3"><label class="form-label">Cel tygodnia</label><select name="cel_tygodnia" class="form-select"><option value="Adapt">Adaptacja</option><option value="Akum">Akumulacja</option><option value="Intens">Intensyfikacja</option><option value="Deload">Deload (Regeneracja)</option><option value="Turniej">Start / Turniej</option></select></div>
				<div class="col-md-3"><label class="form-label">Typ treningu</label><select name="typ_treningu" class="form-select"><option value="Tenis">Tenis (Kort)</option><option value="Siła">Siła</option><option value="Cardio">Cardio / Wydolność</option><option value="Odnowa">Odnowa / Fizjo</option><option value="Wolne">Wolne</option></select></div>
			</div>
			<div class="row">
				<div class="col-md-3"><label class="form-label">Czas (min)</label><input type="number" name="czas_min" class="form-control" value="60" min="0"></div>
				<div class="col-md-3"><label class="form-label">HR spocz. (ud./min)</label><input type="number" name="hr_spoczynkowe" class="form-control" placeholder="np. 58"></div>
				<div class="col-md-3"><label class="form-label">Obwody / Serie x Powt.</label><input type="text" name="obwody_serie" class="form-control" placeholder="np. 3x12"></div>
				<div class="col-md-3"><label class="form-label">Obciążenie (kg / guma)</label><input type="text" name="obciazenie" class="form-control" placeholder="np. guma czerwona"></div>
			</div>
			<div style="margin-bottom:16px;">
				<label class="form-label">Skala RPE (1-10): <span id="rpeVal" class="badge badge-info">5</span></label>
				<input type="range" name="rpe" class="form-range" min="1" max="10" value="5" oninput="document.getElementById('rpeVal').innerText=this.value">
				<small style="color:#64748b;">1-3 Bardzo lekki | 4-5 Umiarkowany | 6-7 Ciężki | 8-9 Bardzo ciężki | 10 Max</small>
			</div>
			<h3 style="color:#1b81c3;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">2. Monitoring Barków i Regeneracji</h3>
			<div class="row">
				<div class="col-md-4"><label class="form-label">Ból PRAWY bark (0-10): <strong id="bpVal">0</strong></label><input type="range" name="bark_prawy" class="form-range" min="0" max="10" value="0" oninput="document.getElementById('bpVal').innerText=this.value"></div>
				<div class="col-md-4"><label class="form-label">Ból LEWY bark (0-10): <strong id="blVal">0</strong></label><input type="range" name="bark_lewy" class="form-range" min="0" max="10" value="0" oninput="document.getElementById('blVal').innerText=this.value"></div>
				<div class="col-md-4"><label class="form-label">Jakość Snu (1-5): <strong id="senVal">4</strong></label><input type="range" name="jakosc_snu" class="form-range" min="1" max="5" value="4" oninput="document.getElementById('senVal').innerText=this.value"></div>
			</div>
			<div class="row" style="margin:12px 0;">
				<div class="col-md-4"><label style="cursor:pointer;"><input type="checkbox" name="pull_push_ok" checked> Balans Pull : Push (2-3x więcej ciągnięcia)</label></div>
				<div class="col-md-4"><label style="cursor:pointer;"><input type="checkbox" name="regeneracja_48h_ok" checked> Zachowano 48h przerwy między siłą</label></div>
				<div class="col-md-4"><label style="cursor:pointer;"><input type="checkbox" name="rotatory_ok" checked> Wykonano ćwiczenia rotatorów</label></div>
			</div>
			<div style="margin-bottom:16px;"><label class="form-label">Uwagi / strapping</label><textarea name="uwagi_strapping" class="form-control" rows="3" placeholder="Dodatkowe uwagi"></textarea></div>
			<button type="submit" name="zapisz_trening" class="btn btn-primary">Zapisz trening</button>
		</form>
	</div>

	<div id="turnieje" class="tab-pane">
		<form method="POST" class="card">
			<h3>Dodaj turniej do kalendarza</h3>
			<div class="row">
				<div class="col-md-4"><label class="form-label">Nazwa turnieju</label><input type="text" name="nazwa_turnieju" class="form-control" required></div>
				<div class="col-md-4"><label class="form-label">Miejsce</label><input type="text" name="miejsce" class="form-control" required></div>
				<div class="col-md-4"><label class="form-label">Nawierzchnia</label><input type="text" name="nawierzchnia" class="form-control"></div>
				<div class="col-md-4"><label class="form-label">Data od</label><input type="date" name="data_od" class="form-control" required></div>
				<div class="col-md-4"><label class="form-label">Data do</label><input type="date" name="data_do" class="form-control"></div>
				<div class="col-md-4"><label class="form-label">Cel startowy</label><input type="text" name="cel_startowy" class="form-control"></div>
			</div>
			<button type="submit" name="dodaj_turniej" class="btn btn-primary">Dodaj turniej</button>
		</form>
		<div class="card">
			<h3>Turnieje i wrażenia</h3>
			<?php if ($turnieje && $turnieje->num_rows > 0): ?>
				<?php while ($turniej = $turnieje->fetch_assoc()): ?>
					<div class="card">
						<h4><?= htmlspecialchars($turniej['nazwa_turnieju'], ENT_QUOTES, 'UTF-8') ?></h4>
						<p><?= htmlspecialchars($turniej['miejsce'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($turniej['data_od'], ENT_QUOTES, 'UTF-8') ?><?php if (!empty($turniej['data_do'])): ?> – <?= htmlspecialchars($turniej['data_do'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></p>
						<p>Nawierzchnia: <?= htmlspecialchars($turniej['nawierzchnia'] ?? '', ENT_QUOTES, 'UTF-8') ?> | Cel: <?= htmlspecialchars($turniej['cel_startowy'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
						<form method="POST">
							<input type="hidden" name="turniej_id" value="<?= (int)$turniej['id'] ?>">
							<label class="form-label">Wrażenia po turnieju</label>
							<textarea name="wrazenia_po" class="form-control" rows="3"><?= htmlspecialchars($turniej['wrazenia_po'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
							<button type="submit" name="zapisz_wrazenia" class="btn btn-primary">Zapisz wrażenia</button>
						</form>
					</div>
				<?php endwhile; ?>
			<?php else: ?><p>Brak turniejów w kalendarzu.</p><?php endif; ?>
		</div>
	</div>

	<div id="historia" class="tab-pane">
		<div class="card">
			<h3>Ostatnie wpisy treningowe</h3>
			<?php if ($wpisy && $wpisy->num_rows > 0): ?>
				<div style="overflow-x:auto;"><table>
					<thead><tr><th>Data</th><th>Typ</th><th>Czas</th><th>RPE</th><th>Ból barku (P/L)</th><th>Sen</th><th>Uwagi</th></tr></thead>
					<tbody>
					<?php while ($wpis = $wpisy->fetch_assoc()): ?>
						<tr><td><?= htmlspecialchars($wpis['data_treningu'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($wpis['typ_treningu'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$wpis['czas_min'] ?> min</td><td><?= (int)$wpis['rpe'] ?></td><td><?= (int)$wpis['bark_prawy'] ?> / <?= (int)$wpis['bark_lewy'] ?></td><td><?= (int)$wpis['jakosc_snu'] ?>/5</td><td><?= htmlspecialchars($wpis['uwagi_strapping'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
					<?php endwhile; ?>
					</tbody>
				</table></div>
			<?php else: ?><p>Brak wpisów treningowych.</p><?php endif; ?>
		</div>
	</div>
</div>
<script>
document.querySelectorAll('.nav-pills button[data-target]').forEach(function (button) {
	button.addEventListener('click', function () {
		document.querySelectorAll('.nav-pills button').forEach(function (item) { item.classList.remove('active'); });
		document.querySelectorAll('.tab-pane').forEach(function (pane) { pane.classList.remove('active'); });
		button.classList.add('active');
		document.querySelector(button.dataset.target).classList.add('active');
	});
});
</script>
</body>
</html>
