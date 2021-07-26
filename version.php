<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information
 *
 * @package   local_helpdesk
 * @copyright 2021, Vitaliy Shapovalov <vshapovalov@muiv.ru>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin -> component = 'local_helpdesk';   // Declare the type and name of this plugin.
$plugin -> version = 20210726000;  // Plugin released on 20th Mar 2021.
$plugin -> requires = 2019052000; // Moodle 3.7.0 is required.
$plugin -> supported = [37, 39]; // Moodle 3.7.x, 3.8.x and 3.9.x are supported.
$plugin -> maturity = MATURITY_STABLE; // This is considered as ready for production sites.
$plugin -> release = '0.0.1 (Build 2021031000)'; // This is our first revision for Moodle 2.7.x branch.