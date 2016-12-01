<?php

/**
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_mojec
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$settings->add(new admin_setting_configcheckbox("assignsubmission_mojec/default",
    new lang_string("default", "assignsubmission_mojec"),
    new lang_string("default_help", "assignsubmission_mojec"), 0));

