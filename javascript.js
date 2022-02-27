// Koska tehtävien merkitsemisessä on aikasensitiivisiä animaatioita, lukitaan merkinnän tekeminen aina yhden merkinnän ajaksi, jotta edellinen (animaatioineen) ehtii valmistua.
toggleTaskLock(false);

// jQuery-eventit.
$(function() {
	// Sivunlatauksen yhteydessä tehtävä perustoimenpide; ladataan aktiivinen tehtävälista.
	getData();
	// Tehtävä merkitään suorituksi tai suoritus perutaan.
	$('#content').on('mouseup', '.checkbox', function() {
		// Merkkaus voidaan tehdä vain jos merkkauksen lukko on auki.
		if (lock_marking == false) {
			let task_is_complete = false;
			// Suljetaan lukko merkitsemisen ajaksi.
			toggleTaskLock(true);
			// Merkitäänkö tehtävä valmiiksi vai keskeneräiseksi?
			if (!$(this).find('input[type="checkbox"]').is(':checked')) {
				task_is_complete = true;
			}	
			markTask(task_is_complete, $(this).parent().data('task-id'));
		}
	});

	// Tehtävä poistetaan listalta.
	$('#content').on('click', '.deleter', function() {
		removeTask($(this).parent().data('task-id'));
		showStatusMessage('Tehtävä poistettu listalta.');
	});

	// Uuden tehtävän lisääminen.
	$('#add-task').click(function() {
		showOrHideAddNewTask(true);
	});

	// Uuden tehtävän tallentaminen.
	$('form#add-new-task').submit(function() {
		// Tehtävän voi tallentaa vain, jos jotain tekstiä on kirjoitettu.
		if ($('.new-task-description').val().length) {
			$.ajax({
				url: "",
				type: "POST",
				dataType: "json",
				data: {
					data_id: 'new_task',
					text: $('input.new-task-description').val()
				}
			}).done(function() {
				showOrHideAddNewTask(false);
				getData();
				showStatusMessage('Uusi tehtävä lisätty.');
			});
		}
		return false;
	});

	// Muutetaan jo tallennetun tehtävän tekstiä.
	$('#checklist').on('change', '.added-task', function() {
		// Tehtävän tekstiä voi muuttaa vain, jos jotain tekstiä on kirjoitettu.
		if ($(this).val().length) {
			$.ajax({
				url: "",
				type: "POST",
				dataType: "json",
				data: {
					data_id: 'edit_task',
					text: $(this).val(),
					task_id: $(this).parent().parent().data('task-id')
				}
			}).done(function() {
				showOrHideAddNewTask(false);
				getData();
				showStatusMessage('Tehtävää muokattu.');
			});
		}
		return false;
	});

	// Tehtävän lisäämisestä poistuminen
	$('.new-task-overlay').click(function(event) {
		// Varmistetaan, että klikki kohdistui tummennettuun taustaan.
		if ($(event.target).hasClass('new-task-overlay')) {
			showOrHideAddNewTask(false);
		}
	});
});


/**
 * Hakee sivunlatauksen oletusdatan (= pävän merkinnät).
 */

function getData() {
	$.ajax({
		url: "",
		type: "POST",
		dataType: "json",
		data: {
			data_id: 'days_list'
		}
	}).done(function(response) {
		console.log(response.total);
		if (response.total > 0) {
			$('.no-tasks-notification').hide();
		} else {
			$('.no-tasks-notification').show();
		}
		markCompletedAndTotalTasksNumbers(response.completed, response.total);
		$('#checklist').html(response.html);
	});
}

/**
 * Tehtävän merkitsemislukko asetetaan auki tai kiinni.
 * @param {boolean} ToggleOn True = lukko suljetaan, false = lukko avataan.
 */

function toggleTaskLock(ToggleOn) {
	if (ToggleOn == true) {
		lock_marking = true;
		$('.task').addClass('locked');
	} else {
		lock_marking = false;
		$('.task').removeClass('locked');
	}
}

/**
 * Merkitsee otsikkoon valmistuneiden ja kaikkien merkintöjen lukumäärät.
 * @param {int} Completed
 * @param {int} Total
 */

function markCompletedAndTotalTasksNumbers(Completed, Total) {
	$('.completed-tasks').html(Completed);
	$('.total-tasks').html(Total);
}

/**
 * Poistaa merkinnän tietokannasta (ja uudelleensorttaa jäljelle jääneen listan).
 * @param {int} TaskID Poistettavan merkinnän id.
 */

function removeTask(TaskID) {
	$.ajax({
		url: "",
		type: "POST",
		dataType: "json",
		data: {
			data_id: 'delete_task',
			task_id: TaskID
		}
	}).done(function() {
		let task = $('.task[data-task-id="' + TaskID + '"]');
		// Poistettava rivi animoidaan syrjään ja feidataan näkyvistä.
		task.animate(
			{left: '5em', opacity: '0%'},
			{duration: 300}
		);
		// Kaikki poistettavan rivin jälkiset rivit animoidaan ylöspäin poistetun paikalle.
		task.nextAll().each(function() {
			$(this).animate(
				{bottom: '6em'},
				{duration: 200}
			);
		});
		// Siirron jälkeen sivun sisältö haetaan uudestaan. Ei järkevää, mutta laiska ratkaisu.
		setTimeout(function() {
			getData();
		}, 300);
	});
}

/**
 * Näytetäänkö vai piilotetaanko uuden tehtävän lisäämisdialogi.
 * @param {boolean} Show True = näytetään, false = piilotetaan.
 */

function showOrHideAddNewTask(Show) {
	var opacity = 100;
	var duration = 500;
	// Piilotetaan dialogi. Tällöin piilotus tapahtuu välittömästi, ilman animaatiota.
	if (!Show) {
		$('.new-task-overlay').addClass('no-click');
		$('.new-task-description').val('');
		opacity = duration = 0;
	// Näytetään dialogi.
	} else {
		jQuery('.new-task-overlay').removeClass('no-click');
		$('.new-task-description').focus();
	}
	jQuery('.new-task-overlay').animate(
		{opacity: opacity + '%'},
		{duration: duration}
	);
}

/**
 * Näytetään statusviesti toivotulla tekstillä.
 * @param {string} Message Näytettävä teksti.
 */

function showStatusMessage(Message) {
	$('#status-message').text(Message);
	$('#status-message-wrapper').removeClass('hidden').hide().fadeIn();
	setTimeout(function() {
		$('#status-message-wrapper').fadeOut(500);
		setTimeout(function() {
			$('#status-message-wrapper').addClass('hidden');
			$('#status-message').text('');
		}, 5000);
	}, 3000);
}

/**
 * Merkkaa tehtävän joko valmiiksi tai keskeneräiseksi
 * @param {boolean}	TaskIsComplete	True = tehtävä merkitään valmiiksi, false = tehtävä merkitään keskeneräiseksi.
 * @param {int}		TaskID			Tehtävä-id.
 */

function markTask(TaskIsComplete, TaskID) {
	// Oletusarvot, kun tehtävä Merkataan valmiiksi.
	let task = $('.task[data-task-id="' + TaskID + '"]');
	task.addClass('completed');
	// Tehtävä merkataan keskeneräiseksi.
	if (!TaskIsComplete) {
		task.animate(
			{bottom: task.prevAll().length * 6 + 'em'},
			{duration: 300}
		);
		// Kaikki merkattua tehtävää edeltävät tehtävät tuodaan alemmas.
		task.prevAll().each(function() {
			$(this).animate(
				{top: '6em'},
				{duration: 200}
			);
		});
		task.removeClass('completed');
	// Tehtävä merkataan valmiiksi.
	} else {
		// Merkattu tehtävä animoidaan ylös/alas nykysijainnistaan.
		task.animate(
			{top: task.nextAll().length * 6 + 'em'},
			{duration: 300}
		);
		// Merkatun tehtävän kumppanit animoidaan päinvastaiseen suuntaan.
		task.nextAll().each(function() {
			$(this).animate(
				{bottom: '6em'},
				{duration: 200}
			);
		});
	}
	setTimeout(function() {
		// Keskeneräiseksi merkatut siirretään listan alkuun.
		if (!TaskIsComplete) {
			task.prependTo('#checklist');
		// Valmistuneet siirretään listan pohjalle.
		} else {
			task.appendTo('#checklist');
		}
		$('.task').removeAttr('style');
		markCompletedAndTotalTasksNumbers($('.task.completed').length, $('.task').length);
		// Kerätään kaikkien tehtävirien id:t järjestyksessä taulukkoon.
		let tasks_order = jQuery('.task').map(function() {
			return jQuery(this).data('task-id');
		}).get();

		$.ajax({
			url: "",
			type: "POST",
			dataType: "json",
			data: {
				data_id: 'mark_task',
				task_id: TaskID,
				is_complete: TaskIsComplete,
				tasks_order: tasks_order
			}
		}).done(function() {
			// Avataan lukko, jotta muita tehtäviä voi merkitä valmistuneeksi.
			toggleTaskLock(false);
		});
	}, 500);
}