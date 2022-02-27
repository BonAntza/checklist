<?php
$system_is_online = TRUE;

if ($system_is_online) {
	// Temp-arvo demoa varten.
	$checklist_id = 1;
	session_start();

	// Sisällytetään tiedostot bisneslogiikkaa ja käyttöliittymäelementtejä varten.
	require_once 'backend.php';
	require_once 'ui.php';

	// Palautetaan postin kutsumat tiedot selaimelle.
	if (!empty($_POST['data_id'])) {
		switch ($_POST['data_id']) {
			// Käyttäjä poistaa rivin tehtävälistaltaan.
			case 'delete_task':
				// Tarkistetaan, että käyttäjä ei yritä syöttää virheellistä dataa.
				if (isValidPositiveInteger($_POST['task_id'])) {
					dbQuery("DELETE FROM task WHERE id = {$_POST['task_id']}");
					echo true;
				}
				break;
			// Käyttäjä tallentaa uuden tehtävän listalleen.
			case 'new_task':
				// Parsitaan annettu teksti.
				$text = parseText($_POST['text']);
				// Tehtävä voidaan tallentaa vain mikäli sillä on yhtään pituutta.
				if (strlen($text) > 0) {
					addNewTask($checklist_id, $text);
					echo true;
				}
				break;
			// Käyttäjä muokkaa olemassa olevaa tehtävää.
			case 'edit_task':
				// Parsitaan annettu teksti.
				$text = parseText($_POST['text']);
				// Validoidaan annettu teksti ja tehtävän id.
				if (strlen($text) > 0 && isValidPositiveInteger($_POST['task_id'])) {
					editTask($text, $_POST['task_id']);
					echo true;
				}
				break;
			// Merkataan tehtävä valmiiksi/keskeneräiseksi.
			case 'mark_task':
				if (isValidPositiveInteger($_POST['task_id'])) {
					markTask($_POST['task_id'], $_POST['is_complete']);
					// Tarkistetaan vielä uusi järjestys ja jos se on ok, tehdään tietokantapäivitys.
					if (tasksOrderIsValid($_POST['tasks_order'])) {
						resortChecklist($_POST['tasks_order']);
					}
					echo true;
				}
				break;
			// Palautetaan päivän tehtävälistaus sivulle.
			case 'days_list':
			default:
				$checklist = getChecklistTasks($checklist_id);
				echo json_encode(array(
					'html' => uiChecklist($checklist),
					'completed' => getCompletedTasksCount($checklist),
					'total' => count($checklist)
				));
				break;
		}
	// Lataa tehtävlistan oletusnäkymä.
	} else {
		uiPage();
	}
} else {
	echo "system offline";
}