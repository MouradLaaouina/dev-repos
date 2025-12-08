<?php
/* Copyright (C) 2024 Utopios
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    exportpaie/lib/exportpaie.lib.php
 * \ingroup exportpaie
 * \brief   Library files with common functions for ExportPaie
 */

/**
 * Prepare admin pages header
 *
 * @return array Array of tabs
 */
function exportpaie_admin_prepare_head()
{
	global $langs, $conf;

	$langs->load("exportpaie@exportpaie");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/exportpaie/admin/exportpaie_setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/exportpaie/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'exportpaie');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'exportpaie', 'remove');

	return $head;
}

/**
 * Format a date from YYYY-MM-DD to JJMMAAAA
 *
 * @param string $date Date in YYYY-MM-DD format
 * @return string Date in JJMMAAAA format
 */
function formatDateJJMMAAAA($date)
{
	if (empty($date) || $date == '0000-00-00') {
		return str_repeat('0', 8);
	}

	$timestamp = strtotime($date);
	if ($timestamp === false) {
		return str_repeat('0', 8);
	}

	return date('dmY', $timestamp);
}

/**
 * Format a date from YYYY-MM-DD to AAAAMMJJ
 *
 * @param string $date Date in YYYY-MM-DD format
 * @return string Date in AAAAMMJJ format
 */
function formatDateAAAAMMJJ($date)
{
	if (empty($date) || $date == '0000-00-00') {
		return str_repeat('0', 8);
	}

	$timestamp = strtotime($date);
	if ($timestamp === false) {
		return str_repeat('0', 8);
	}

	return date('Ymd', $timestamp);
}

/**
 * Format a phone number to international format
 *
 * @param string $phone Phone number
 * @return string Formatted phone number (00212...)
 */
function formatPhoneNumber($phone)
{
	if (empty($phone)) {
		return str_repeat('0', 13);
	}

	// Remove all non-numeric characters
	$phone = preg_replace('/[^0-9]/', '', $phone);

	// If starts with 0, replace with 00212
	if (substr($phone, 0, 1) == '0') {
		$phone = '00212' . substr($phone, 1);
	}

	// If doesn't start with 00212, add it
	if (substr($phone, 0, 5) != '00212') {
		$phone = '00212' . $phone;
	}

	// Pad or truncate to 13 characters
	$phone = str_pad(substr($phone, 0, 13), 13, '0', STR_PAD_RIGHT);

	return $phone;
}

/**
 * Format a string to fixed width with padding
 *
 * @param string $str String to format
 * @param int $width Desired width
 * @param string $pad_string Padding character
 * @param int $pad_type Padding type (STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH)
 * @return string Formatted string
 */
function formatFixedWidth($str, $width, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
{
	// Convert to string if not already
	$str = (string)$str;

	// Truncate if too long
	if (mb_strlen($str) > $width) {
		$str = mb_substr($str, 0, $width);
	}

	// Pad if too short
	return str_pad($str, $width, $pad_string, $pad_type);
}

/**
 * Format a number with leading zeros
 *
 * @param mixed $number Number to format
 * @param int $width Desired width
 * @return string Formatted number
 */
function formatNumberFixedWidth($number, $width)
{
	return str_pad((string)$number, $width, '0', STR_PAD_LEFT);
}

/**
 * Calculate CNSS plafond (ceiling)
 *
 * @param float $salaire Salary amount
 * @param float $plafond Ceiling amount (default 6000 DH)
 * @return float Plafond amount
 */
function calculateCNSSPlafond($salaire, $plafond = 6000)
{
	return min($salaire, $plafond);
}

/**
 * Get CIMR regime code
 *
 * @param string $regime Regime name
 * @return string Regime code (1, 2, or 3)
 */
function getCIMRRegimeCode($regime)
{
	switch (strtoupper($regime)) {
		case 'AL_KAMIL':
			return '1';
		case 'AL_MOUNASSIB':
			return '2';
		case 'TCNSS':
			return '3';
		default:
			return '1';
	}
}

/**
 * Get CIMR taux value from percentage
 *
 * @param string $taux Taux code (e.g., '0600' for 6%)
 * @return float Taux as decimal (e.g., 0.06)
 */
function getCIMRTauxDecimal($taux)
{
	$taux_int = intval($taux);
	return $taux_int / 10000;
}

/**
 * Validate CNSS matricule format
 *
 * @param string $matricule CNSS matricule
 * @return bool True if valid
 */
function validateCNSSMatricule($matricule)
{
	// CNSS matricule should be numeric and 10 characters
	return preg_match('/^[0-9]{10}$/', $matricule);
}

/**
 * Validate CNIE format
 *
 * @param string $cnie CNIE number
 * @return bool True if valid
 */
function validateCNIE($cnie)
{
	// CNIE should be alphanumeric and 8 characters
	return preg_match('/^[A-Z0-9]{1,8}$/i', $cnie);
}

/**
 * Convert Dolibarr date format to array
 *
 * @param string $date Date in YYYY-MM-DD format
 * @return array Array with 'year', 'month', 'day' keys
 */
function convertDateToArray($date)
{
	if (empty($date) || $date == '0000-00-00') {
		return array('year' => 0, 'month' => 0, 'day' => 0);
	}

	$parts = explode('-', $date);
	return array(
		'year' => isset($parts[0]) ? intval($parts[0]) : 0,
		'month' => isset($parts[1]) ? intval($parts[1]) : 0,
		'day' => isset($parts[2]) ? intval($parts[2]) : 0
	);
}

/**
 * Get month name in French
 *
 * @param int $month Month number (1-12)
 * @return string Month name
 */
function getMonthNameFR($month)
{
	$months = array(
		1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
		5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
		9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
	);

	return isset($months[$month]) ? $months[$month] : '';
}

/**
 * Get quarter from month
 *
 * @param int $month Month number (1-12)
 * @return int Quarter (1-4)
 */
function getQuarterFromMonth($month)
{
	return ceil($month / 3);
}

/**
 * Get months in quarter
 *
 * @param int $quarter Quarter (1-4)
 * @return array Array of month numbers
 */
function getMonthsInQuarter($quarter)
{
	$quarters = array(
		1 => array(1, 2, 3),
		2 => array(4, 5, 6),
		3 => array(7, 8, 9),
		4 => array(10, 11, 12)
	);

	return isset($quarters[$quarter]) ? $quarters[$quarter] : array();
}

/**
 * Sanitize string for export (remove special characters)
 *
 * @param string $str String to sanitize
 * @return string Sanitized string
 */
function sanitizeForExport($str)
{
	// Remove accents
	$str = strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'),
		'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');

	// Remove special characters except spaces, alphanumeric
	$str = preg_replace('/[^a-zA-Z0-9\s]/', '', $str);

	// Convert to uppercase
	$str = strtoupper($str);

	return $str;
}
