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
 * Strings for component "assignsubmission_mojec", language "en"
 *
 * @package   assignsubmission_mojec
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string["pluginname"] = "JUnit Exercise Corrector";
$string["mojec"] = "JUnit Exercise Corrector";
$string["mojec_submissions_fa"] = "JUnit Exercise Corrector Submissions";
$string["mojec_tests_fa"] = "JUnit Exercise Corrector Tests";
$string['enabled'] = "JUnit Exercise Corrector";
$string['enabled_help'] = "If enabled, students are able to upload one ZIP file containing their Java exercise which will then be tested against a teacher provided set of JUnit tests.";
$string["setting_unittests"] = "JUnit tests";
$string["setting_unittests_help"] = "A single ZIP file containg the JUnit tests, the students' submissions should be tested against.";
$string["wsbase_not_set"] = "The JUnit Exercise Corrector web service base URL is not configured.";
$string["unexpectederror"] = "An unexpected error occured.";
$string["mojec_submission"] = "Excercise ZIP";
$string["mojec_submission_help"] = "A single ZIP file containing all the relevant java files and resources for this exercise.";


// Admin Settings
$string["default"] = "Enabled by default";
$string["default_help"] = "If set, this submission method will be enabled by default for all new assignments.";
$string["wsbase"] = "Base URL to the web service";
$string["wsbase_help"] = "The base URL to the web service, where all the tests and submissions will be sent and evaluated.";