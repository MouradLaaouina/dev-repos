/* Copyright (C) 2024 Utopios
 *
 * This file contains JavaScript functions for the ExportPaie module
 */

/**
 * Initialize ExportPaie module
 */
$(document).ready(function() {
	// Initialize tooltips
	initTooltips();

	// Initialize file upload handlers
	initFileUpload();

	// Initialize form validation
	initFormValidation();
});

/**
 * Initialize tooltips
 */
function initTooltips() {
	$('.exportpaie-tooltip').hover(
		function() {
			$(this).find('.exportpaie-tooltiptext').fadeIn(200);
		},
		function() {
			$(this).find('.exportpaie-tooltiptext').fadeOut(200);
		}
	);
}

/**
 * Initialize file upload handlers
 */
function initFileUpload() {
	// Drag and drop support
	$('.exportpaie-upload-area').on('dragover', function(e) {
		e.preventDefault();
		$(this).addClass('dragover');
	});

	$('.exportpaie-upload-area').on('dragleave', function(e) {
		e.preventDefault();
		$(this).removeClass('dragover');
	});

	$('.exportpaie-upload-area').on('drop', function(e) {
		e.preventDefault();
		$(this).removeClass('dragover');

		var files = e.originalEvent.dataTransfer.files;
		if (files.length > 0) {
			var input = $(this).find('input[type="file"]')[0];
			input.files = files;
			updateFileLabel(input);
		}
	});

	// File input change
	$('input[type="file"]').on('change', function() {
		updateFileLabel(this);
	});
}

/**
 * Update file upload label
 */
function updateFileLabel(input) {
	if (input.files && input.files[0]) {
		var fileName = input.files[0].name;
		var label = $(input).closest('.exportpaie-upload-area').find('.exportpaie-upload-label');
		label.text(fileName);
	}
}

/**
 * Initialize form validation
 */
function initFormValidation() {
	$('form').on('submit', function(e) {
		var form = $(this);
		var errors = [];

		// Check required fields
		form.find('[required]').each(function() {
			if (!$(this).val()) {
				var label = $('label[for="' + $(this).attr('id') + '"]').text();
				errors.push('Le champ "' + label + '" est obligatoire');
			}
		});

		// Check date formats
		form.find('input[type="date"]').each(function() {
			if ($(this).val() && !isValidDate($(this).val())) {
				errors.push('Format de date invalide');
			}
		});

		// Check numeric fields
		form.find('input[type="number"]').each(function() {
			if ($(this).val() && !isValidNumber($(this).val())) {
				errors.push('Format numérique invalide');
			}
		});

		if (errors.length > 0) {
			e.preventDefault();
			showValidationErrors(errors);
			return false;
		}
	});
}

/**
 * Validate date format
 */
function isValidDate(dateString) {
	var regex = /^\d{4}-\d{2}-\d{2}$/;
	if (!regex.test(dateString)) return false;

	var date = new Date(dateString);
	return date instanceof Date && !isNaN(date);
}

/**
 * Validate number format
 */
function isValidNumber(num) {
	return !isNaN(parseFloat(num)) && isFinite(num);
}

/**
 * Show validation errors
 */
function showValidationErrors(errors) {
	var html = '<div class="exportpaie-status error exportpaie-validation-errors">';
	html += '<h4>Erreurs de validation :</h4>';
	html += '<ul>';
	errors.forEach(function(error) {
		html += '<li>' + error + '</li>';
	});
	html += '</ul>';
	html += '</div>';

	// Remove existing error messages
	$('.exportpaie-validation-errors').remove();

	// Insert new error message
	$('form').before(html);

	// Scroll to error message
	$('html, body').animate({
		scrollTop: $('.exportpaie-validation-errors').offset().top - 100
	}, 500);
}

/**
 * Show loading spinner
 */
function showLoading(message) {
	message = message || 'Chargement...';

	var html = '<div class="exportpaie-loading-overlay">';
	html += '<div class="exportpaie-loading-content">';
	html += '<div class="exportpaie-loading"></div>';
	html += '<p>' + message + '</p>';
	html += '</div>';
	html += '</div>';

	$('body').append(html);
}

/**
 * Hide loading spinner
 */
function hideLoading() {
	$('.exportpaie-loading-overlay').remove();
}

/**
 * Show status message
 */
function showStatus(message, type) {
	type = type || 'info';

	var html = '<div class="exportpaie-status ' + type + '">';
	html += message;
	html += '</div>';

	// Remove existing status messages
	$('.exportpaie-status').remove();

	// Insert new status message
	$('h1, .fiche').first().after(html);

	// Auto-hide after 5 seconds
	setTimeout(function() {
		$('.exportpaie-status').fadeOut(500, function() {
			$(this).remove();
		});
	}, 5000);

	// Scroll to status message
	$('html, body').animate({
		scrollTop: $('.exportpaie-status').offset().top - 100
	}, 500);
}

/**
 * Update progress bar
 */
function updateProgress(percent, message) {
	message = message || '';

	if ($('.exportpaie-progress').length === 0) {
		var html = '<div class="exportpaie-progress">';
		html += '<div class="exportpaie-progress-bar" style="width: 0%"></div>';
		html += '</div>';
		$('form').after(html);
	}

	$('.exportpaie-progress-bar').css('width', percent + '%');
	$('.exportpaie-progress-bar').text(message);

	if (percent >= 100) {
		setTimeout(function() {
			$('.exportpaie-progress').fadeOut(500, function() {
				$(this).remove();
			});
		}, 2000);
	}
}

/**
 * Confirm action
 */
function confirmAction(message, callback) {
	if (confirm(message)) {
		if (typeof callback === 'function') {
			callback();
		}
		return true;
	}
	return false;
}

/**
 * Export to CSV
 */
function exportTableToCSV(tableId, filename) {
	var csv = [];
	var table = document.getElementById(tableId);

	if (!table) return;

	var rows = table.querySelectorAll('tr');

	for (var i = 0; i < rows.length; i++) {
		var row = [], cols = rows[i].querySelectorAll('td, th');

		for (var j = 0; j < cols.length; j++) {
			var text = cols[j].innerText.replace(/"/g, '""');
			row.push('"' + text + '"');
		}

		csv.push(row.join(','));
	}

	// Download CSV
	var csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
	var downloadLink = document.createElement('a');
	downloadLink.download = filename;
	downloadLink.href = window.URL.createObjectURL(csvFile);
	downloadLink.style.display = 'none';
	document.body.appendChild(downloadLink);
	downloadLink.click();
	document.body.removeChild(downloadLink);
}

/**
 * Format number with spaces
 */
function formatNumber(num) {
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/**
 * Format currency
 */
function formatCurrency(amount) {
	return formatNumber(amount.toFixed(2)) + ' DH';
}

/**
 * Preview export data
 */
function previewExportData(url, params) {
	showLoading('Chargement des données...');

	$.ajax({
		url: url,
		type: 'POST',
		data: params,
		dataType: 'json',
		success: function(response) {
			hideLoading();

			if (response.success) {
				displayPreviewTable(response.data);
			} else {
				showStatus(response.error || 'Erreur lors du chargement des données', 'error');
			}
		},
		error: function() {
			hideLoading();
			showStatus('Erreur de communication avec le serveur', 'error');
		}
	});
}

/**
 * Display preview table
 */
function displayPreviewTable(data) {
	// This function should be customized based on the data structure
	// For now, just show a success message
	showStatus('Données chargées avec succès', 'success');
}

/**
 * Generate export file
 */
function generateExportFile(url, params, filename) {
	showLoading('Génération du fichier en cours...');

	$.ajax({
		url: url,
		type: 'POST',
		data: params,
		xhrFields: {
			responseType: 'blob'
		},
		success: function(blob) {
			hideLoading();

			// Create download link
			var link = document.createElement('a');
			link.href = window.URL.createObjectURL(blob);
			link.download = filename;
			link.click();

			showStatus('Fichier généré avec succès', 'success');
		},
		error: function() {
			hideLoading();
			showStatus('Erreur lors de la génération du fichier', 'error');
		}
	});
}

/**
 * Import file
 */
function importFile(url, formData, callback) {
	showLoading('Import en cours...');

	$.ajax({
		url: url,
		type: 'POST',
		data: formData,
		processData: false,
		contentType: false,
		success: function(response) {
			hideLoading();

			if (response.success) {
				showStatus(response.message || 'Import réussi', 'success');
				if (typeof callback === 'function') {
					callback(response);
				}
			} else {
				showStatus(response.error || 'Erreur lors de l\'import', 'error');
			}
		},
		error: function() {
			hideLoading();
			showStatus('Erreur de communication avec le serveur', 'error');
		}
	});
}

/**
 * Validate CNSS matricule
 */
function validateCNSSMatricule(matricule) {
	var regex = /^[0-9]{10}$/;
	return regex.test(matricule);
}

/**
 * Validate CNIE
 */
function validateCNIE(cnie) {
	var regex = /^[A-Z0-9]{1,8}$/i;
	return regex.test(cnie);
}

/**
 * Calculate CNSS plafond
 */
function calculateCNSSPlafond(salaire, plafond) {
	plafond = plafond || 6000;
	return Math.min(parseFloat(salaire), parseFloat(plafond));
}

/**
 * Get quarter from month
 */
function getQuarterFromMonth(month) {
	return Math.ceil(parseInt(month) / 3);
}

/**
 * Get months in quarter
 */
function getMonthsInQuarter(quarter) {
	var quarters = {
		1: [1, 2, 3],
		2: [4, 5, 6],
		3: [7, 8, 9],
		4: [10, 11, 12]
	};
	return quarters[quarter] || [];
}

/**
 * Initialize period selector
 */
function initPeriodSelector() {
	$('#annee').on('change', function() {
		updatePeriodDisplay();
	});

	$('#mois, #trimestre').on('change', function() {
		updatePeriodDisplay();
	});
}

/**
 * Update period display
 */
function updatePeriodDisplay() {
	var annee = $('#annee').val();
	var mois = $('#mois').val();
	var trimestre = $('#trimestre').val();

	if (mois) {
		var monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
						  'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
		$('.period-display').text(monthNames[parseInt(mois)] + ' ' + annee);
	} else if (trimestre) {
		$('.period-display').text('T' + trimestre + ' ' + annee);
	}
}
