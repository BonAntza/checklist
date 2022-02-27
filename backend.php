<?php

/**
 * Funkkari debug-tulostelun jeesaamiseksi.
 * @param mixed $Print Arvo, joka halutaan tulostaa selaimeen.
 */

function p($Print) {
	print "<pre>";
	print_r($Print);
	print "</pre>";
}

/**
 * Hakee tiedon rivit.
 * @param int $ChecklistID	Halutun listan id.
 * @return array			Listan rivien tiedot taulukossa.
 */

function getChecklistTasks($ChecklistID) {
	return dbFetchAll("SELECT * FROM task WHERE checklist_id = {$ChecklistID} ORDER BY order_number ASC");
}

/**
 * Hakee kaikki rivit annettua SQL-lausetta vastaan.
 * @param string $SQL	SQL-lause.
 * @return array		Kaikki löytyneet rivit.
 */

function dbFetchAll($SQL) {
	$con = dbConnect();
	$result = mysqli_query($con, $SQL);
	$tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
	mysqli_free_result($result);
	mysqli_close($con);

	return $tasks;
}

/**
 * Palauttaa valmistuneiden tehtävien lukumäärän.
 * @param array $Checklist	Tehtävälistan rivit taulukossa.
 * @return int				Valmistuneiden lukumäärä.
 */

function getCompletedTasksCount($Checklist) {
	$total = 0;
	foreach ($Checklist as $task) {
		if ($task['is_checked'] == 1) {
			$total++;
		}
	}
	return $total;
}

/**
 * Parsii annetun tekstin ja tekee trimmin, kautta-merkkien poiston sekä html-tägien korvauksen.
 * @param string $Text	Parsittava tekstinpätkä.
 * @return string		Palauttaa tekstin.
 */

function parseText($Text) {
	$text = trim($Text);
	$text = stripslashes($text);
	$text = htmlspecialchars($text);
	return $text;
}

/**
 * Lisää uuden tehtävän tietokantaan
 * @param int $ChecklistID	Tehtävälistan id.
 * @param string $Text		Uuden tehtävän teksti.
 */

function addNewTask($ChecklistID, $Text) {
	$con = dbConnect();
	// Escapetaan merkkijono ennen tietokantaan syöttämistä.
	$Text = mysqli_real_escape_string($con, $Text);
	// Uusi tehtävä saa listan kärkipaikan (order_number = 0).
	$task_id = dbQuery("INSERT INTO task (checklist_id, order_number, text) VALUES ($ChecklistID, 0, '{$Text}')");
	// Lisätään yksi kuhunkin aiemman tämän listan tehtävän järjestysnumeroon.
	dbQuery("UPDATE task SET order_number = order_number + 1 WHERE checklist_id = {$ChecklistID} AND id != {$task_id}");
}

/**
 * Muokkaa jo tallennetun tehtävän tekstiä.
 * @param string $Text	Muokattava teksti.
 * @param int $TaskID	Päivitettävän tehtävän id.
 */

function editTask($Text, $TaskID) {
	$con = dbConnect();
	// Escapetaan merkkijono ennen tietokantaan syöttämistä.
	$text = mysqli_real_escape_string($con, $Text);
	dbQuery("UPDATE task SET text = '{$text}' WHERE id = {$TaskID}");
}

/**
 * Merkkaa tehtävän joko valmiiksi tai keskeneräiseksi
 * @param int $TaskID		Tehtävän id.
 * @param string $MarkType	Jos "true", merkataan valmiiksi, muuten merkataan keskeneräiseksi.
 */

function markTask($TaskID, $MarkType) {
	$is_checked = $MarkType == 'true' ? 1 : 0;
	dbQuery("UPDATE task SET is_checked = {$is_checked} WHERE id = {$TaskID}");
}

/**
 * Wrapper-funktio kyselyiden suorittamiseen.
 * @param string $SQL	SQL-kyselylause.
 * @return int			Palauttaa viimeisen (insertoidun) rivin id:n.
 */

function dbQuery($SQL) {
	$con = dbConnect();
	mysqli_query($con, $SQL);
	return mysqli_insert_id($con);
}

/**
 * Wrapper-funktio, joka yhdistää tietokantaan kyselyitä varten.
 * @return object Palauttaa objektin onnistuneesta yhteydestä, tai FALSE:n muuten.
 */

function dbConnect() {
	return mysqli_connect("localhost", "checklist", "[SALASANA]", "checklist");
}

/**
 * Asettaa annetut tehtävät oikeaan (sort-)järjestykseen tietokantaan.
 * @param array $TasksOrder Taskit uudessa, oikeassa järjestyksessään.
 */

function resortChecklist($TasksOrder) {
	foreach ($TasksOrder as $order_number => $task_id) {
		dbQuery("UPDATE task SET order_number = {$order_number} WHERE id = {$task_id}");
	}
}

/**
 * Käy läpi uuden taskien järjestyksen ja validoi sekä järjestysnumeron, että tehtävä-id:n ennen tietokantaan syöttämistä.
 * @param array $TasksOrder	Taulukko, joka sisältää tehtävät uudessa järjestyksessä (0, 1, 2...).
 * @return boolean			TRUE = uusi järjestys ja id:t ok, FALSE = jotain vikaa annetuissa arvoissa taulukossa.
 */

function tasksOrderIsValid($TasksOrder) {
	// Jokainen annettu järjestysnumero ja tehtävä-id luupataan läpi.
	foreach ($TasksOrder as $order_number => $task_id) {
		// Tarkistetaan, että järjestysnumero on joko nolla tai pos. kokonaisluku. Tehtävä-id:n on oltava pos. kokonaisluku.
		if (($order_number === 0 || isValidPositiveInteger($order_number)) && isValidPositiveInteger($task_id)) {
			continue;
		} else {
			return FALSE;
		}
	}
	return TRUE;
}

/**
 * Testaa, että annettu numero on positiivinen kokonaisluku.
 * @param int $Int Testattava arvo.
 */

function isValidPositiveInteger($Int) {
	if (filter_var($Int, FILTER_VALIDATE_INT) && $Int > 0) {
		return TRUE;
	}
	return FALSE;
}