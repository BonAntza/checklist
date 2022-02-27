<?php

/**
 * Luo sivun perusrakenteen.
 */

function uiPage() {
	$weekdays = array(
		1 => 'Maanantai',
		2 => 'Tiistai',
		3 => 'Keskiviikko',
		4 => 'Torstai',
		5 => 'Perjantai',
		6 => 'Lauantai',
		7 => 'Sunnuntai'
	);
	$selected_day = date('N', strtotime(date('d.m.Y')));

    echo "
<!DOCTYPE html>
<html lang='fi'>
    <head>
		<meta charset='UTF-8'>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
        <script src='js/jquery-3.6.0.min.js'></script>
        <link rel='icon' type='image/ico' href='favicon.ico'/>
        <link rel='stylesheet' href='css/checklist.css?" . filemtime($_SERVER["DOCUMENT_ROOT"] . "/css/bonantza.css") . "'>
		<link rel='preconnect' href='https://fonts.googleapis.com'>
		<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
		<link href='https://fonts.googleapis.com/css2?family=Open+Sans&display=swap' rel='stylesheet'>
        <script src='js/javascript.js?" . filemtime($_SERVER["DOCUMENT_ROOT"] . "/js/javascript.js"). "'></script>
        <title>Checklist-sovellus by Antti Oinonen</title>
    </head>
    <body>
		<div id='content-wrapper'>
			<div id='content'>
				<div id='status-message-wrapper' class='hidden'>
					<div id='status-message'></div>
				</div>
				<div id='checklist-date'>{$weekdays[$selected_day]} " . date('d.m.Y') . "</div>
				<div id='header-wrapper'>
					<h1>Päivän tehtävälista (<span class='completed-tasks'></span><span class='tasks-divider'>/</span><span class='total-tasks'></span>)</h1>
					<div id='add-task'>+</div>
				</div>
				<div class='no-tasks-notification'>Ei tehtäviä tälle päivälle.</div>
				<div id='checklist'></div>
			</div>
			<div class='new-task-overlay no-click'>
				<div class='new-task'>
					<form id='add-new-task'>
						<input name='new-task' class='new-task-description' value=''>
						<button type='submit'>Lisää</button>
					</form>
				</div>
			</div>
		</div>
		<div id='footer'>
			&copy; Antti Oinonen
		</div>
	</body>
</html>";
}

/**
 * Luo listan, joka koostuu päivän merkinnöistä.
 * @param array $ListData	Päivän merkinnät taulukossa esitettävässä järjestyksessä.
 * @return string			Listan html.
 */

function uiChecklist($ListData) {
	$html = "";
	foreach ($ListData as $task) {
		$html .= uiTask($task);
	}
	return $html;
}

/**
 * Luo yhden tehtävärivin listalle.
 * @param array $Task	Tehtävärivin tiedot, kuten id, teksti sekä tieto, onko ko. rivi merkitty valmiiksi.
 * @return string		Tehtävärivin html.
 */

function uiTask($Task) {
	$completed = $Task['is_checked'] ? ' completed' : '';
	return "
<div class='task{$completed}' data-task-id='{$Task['id']}'>
	" . uiCheckbox($Task['is_checked']) . "
	<div class='task-text'>
		<input class='added-task' value='{$Task['text']}'>
	</div>
	<div class='deleter'>&Cross;</div>
</div>";
}

/**
 * Piirtää custom-checkboxin selaimen perinteisen asemesta.
 * @param boolean $CheckboxIsChecked	TRUE = checkbox on ruksattu (tämä rivi on merkitty valmiiksi), FALSE = checkboxia ei ole ruksattu.
 * @return string						Checkbox html.
 */

function uiCheckbox($CheckboxIsChecked) {
	$checked = "";
// Jos tämä tehtävä on merkitty suoritetuksi, checkbox ruksataan.
	if ($CheckboxIsChecked) {
		$checked = " checked";
	}
	return "
<label class='checkbox'>
	<input type='checkbox'{$checked}>
	<span class='checkmark'></span>
</label>";
}