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
 * This file contains the definition for the library class for mojec submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_mojec
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// File area for mojec submission assignment.
define('ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION', 'submissions_mojec');
// File area for mojec tests to be uploaded by the teacher.
define('ASSIGNSUBMISSION_MOJEC_FILEAREA_TEST', 'tests_mojec');

/**
 * library class for mojec submission plugin extending submission plugin base class
 *
 * @package assignsubmission_mojec
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_mojec extends assign_submission_plugin {

    // Database table names.
    const TABLE_ASSIGNSUBMISSION_MOJEC = "assignsubmission_mojec";
    const TABLE_MOJEC_TESTRESULT = "mojec_testresult";
    const TABLE_MOJEC_TESTFAILURE = "mojec_testfailure";
    const TABLE_MOJEC_COMPILATIONERROR = "mojec_compilationerror";

    const COMPONENT_NAME = "assignsubmission_mojec";

    //const WS_BASE_ADDRESS = "http://10.40.10.5:8080";
    const WS_BASE_ADDRESS = "http://localhost:8080";

    /**
     * Get the name of the mojec submission plugin
     * @return string
     */
    public function get_name() {
        return get_string("mojec", self::COMPONENT_NAME);
    }

    /**
     * Get mojec submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_mojec_submission($submissionid) {
        global $DB;
        return $DB->get_record(self::TABLE_ASSIGNSUBMISSION_MOJEC, array('submission_id' => $submissionid));
    }

    /**
     * Get the default setting for mojec submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $name = get_string('setting_unittests', self::COMPONENT_NAME);
        $fileoptions = $this->get_file_options();

        $mform->addElement('filemanager', 'mojectests', $name, null, $fileoptions);
    }

    /**
     * Save the settings for mojec submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        if (isset($data->mojectests)) {
            file_save_draft_area_files($data->mojectests, $this->assignment->get_context()->id,
                self::COMPONENT_NAME, ASSIGNSUBMISSION_MOJEC_FILEAREA_TEST, 0);

            // TODO Only send file to backend if checkbox in settings is checked.
            $fs = get_file_storage();

            $files = $fs->get_area_files($this->assignment->get_context()->id,
                self::COMPONENT_NAME,
                ASSIGNSUBMISSION_MOJEC_FILEAREA_TEST,
                0,
                'id',
                false);

            $file = reset($files);
            $url = self::WS_BASE_ADDRESS . "/v1/unittest";
            $this->mojec_post_file($file, $url, "unitTestFile");
        }

        return true;
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $draftitemid = file_get_submitted_draft_itemid('mojectests');
        file_prepare_draft_area($draftitemid, $this->assignment->get_context()->id,
            self::COMPONENT_NAME, ASSIGNSUBMISSION_MOJEC_FILEAREA_TEST,
            0, array('subdirs' => 0));
        $defaultvalues['mojectests'] = $draftitemid;

        return;
    }

    /**
     * File format options
     *
     * @see https://docs.moodle.org/dev/Using_the_File_API_in_Moodle_forms#filemanager
     *
     * @return array
     */
    private function get_file_options() {
        $fileoptions = array('subdirs' => 1,
            "maxfiles" => 1,
            'accepted_types' => array(".zip"),
            'return_types' => FILE_INTERNAL);
        return $fileoptions;
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;

        $data = file_prepare_standard_filemanager($data,
            'tasks',
            $fileoptions,
            $this->assignment->get_context(),
            self::COMPONENT_NAME,
            ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION,
            $submissionid);
        $mform->addElement('filemanager', 'tasks_filemanager', $this->get_name(), null, $fileoptions);

        return true;
    }

    /**
     * Count the number of files
     *
     * @param int $submissionid
     * @param string $area
     * @return int
     */
    private function count_files($submissionid, $area) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id,
            self::COMPONENT_NAME,
            $area,
            $submissionid,
            'id',
            false);

        return count($files);
    }

    /**
     * Save data to the database
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB;

        $fileoptions = $this->get_file_options();

        $data = file_postupdate_standard_filemanager($data,
            'tasks',
            $fileoptions,
            $this->assignment->get_context(),
            self::COMPONENT_NAME,
            ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION,
            $submission->id);

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id,
            self::COMPONENT_NAME,
            ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION,
            $submission->id,
            'id',
            false);

        $mojecsubmission = $this->get_mojec_submission($submission->id);

        if ($mojecsubmission) {
            // If there are old results, delete them.
            $this->delete_test_data($mojecsubmission->id);
        } else {
            $mojecsubmission = new stdClass();
            $mojecsubmission->submission_id = $submission->id;
            $mojecsubmission->assignment_id = $this->assignment->get_instance()->id;
            $mojecsubmission->id = $DB->insert_record(self::TABLE_ASSIGNSUBMISSION_MOJEC, $mojecsubmission);
        }

        // Get the file and post it to our backend.
        $file = reset($files);
        $url = self::WS_BASE_ADDRESS . "/v1/task";
        $response = $this->mojec_post_file($file, $url, "taskFile");

        if (!isset($response)) {
            return false;
        }
        $results = json_decode($response);
        $testresults = $results->testResults;
        foreach ($testresults as $tr) {
            // Test result.
            $testresult = new stdClass();
            $testresult->testname = $tr->testName;
            $testresult->testcount = $tr->testCount;
            $testresult->succtests = implode(",", $tr->successfulTests);
            $testresult->mojec_id = $mojecsubmission->id;

            $testresult->id = $DB->insert_record(self::TABLE_MOJEC_TESTRESULT, $testresult);

            // Test failure.
            $testfailures = $tr->testFailures;
            foreach ($testfailures as $tf) {
                $testfailure = new stdClass();
                $testfailure->testheader = $tf->testHeader;
                $testfailure->message = $tf->message;
                $testfailure->trace = $tf->trace;
                $testfailure->testresult_id = $testresult->id;

                $testfailure->id = $DB->insert_record(self::TABLE_MOJEC_TESTFAILURE, $testfailure);
            }
        }

        $compilationerrors = $results->compilationErrors;
        foreach ($compilationerrors as $ce) {
            // Compilation error.
            $compilationerror = new stdClass();
            $compilationerror->columnnumber = $ce->columnNumber;
            $compilationerror->linenumber = $ce->lineNumber;
            $compilationerror->message = $ce->message;
            $compilationerror->position = $ce->position;
            $compilationerror->mojec_id = $mojecsubmission->id;

            $compilationerror->id = $DB->insert_record(self::TABLE_MOJEC_COMPILATIONERROR, $compilationerror);
        }

        return true;
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @param stdClass $user The user record - unused
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id,
            self::COMPONENT_NAME,
            ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION,
            $submission->id,
            'timemodified',
            false);

        foreach ($files as $file) {
            // Do we return the full folder path or just the file name?
            if (isset($submission->exportfullpath) && $submission->exportfullpath == false) {
                $result[$file->get_filename()] = $file;
            } else {
                $result[$file->get_filepath().$file->get_filename()] = $file;
            }
        }
        return $result;
    }

    /**
     * Posts the file to the url under the given param name.
     *
     * @param stored_file $file the file to post.
     * @param string $url the url to post to.
     * @param string $paramname the param name for the file.
     * @return mixed
     */
    private function mojec_post_file($file, $url, $paramname) {
        if (!isset($file) or !isset($url) or !isset($paramname)) {
            return false;
        }

        $params = array(
            $paramname     => $file,
        );
        $options = array(
            "CURLOPT_RETURNTRANSFER" => true
        );
        $this->set_curl_proxy($options);
        $curl = new curl();
        $response = $curl->post($url, $params, $options);

        return $response;
    }

    /**
     * Adds the HfT proxy settings, just for development.
     *
     * TODO Remove this method in final version
     *
     * @param array $options
     */
    private function set_curl_proxy(& $options) {
        if (self::WS_BASE_ADDRESS == "http://10.40.10.5:8080") {
            $options["CURLOPT_PROXY"] = "proxy.hft-stuttgart.de";
            $options["CURLOPT_PROXYPORT"] = 80;
        }
    }

    /**
     * Display the test results of the submission.
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $PAGE;

        if ($PAGE->url->get_param("action") == "grading") {
            return $this->view_grading_summary($submission, $showviewlink);
        } else {
            return $this->view_student_summary($submission, $showviewlink);
        }
    }

    /**
     * Returns the view that should be displayed in the grading table.
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    private function view_grading_summary(stdClass $submission, & $showviewlink) {
        global $DB;
        $showviewlink = true;

        $mojecsubmission = $DB->get_record(self::TABLE_ASSIGNSUBMISSION_MOJEC, array("submission_id" => $submission->id));
        $testresults = $DB->get_records(self::TABLE_MOJEC_TESTRESULT, array("mojec_id" => $mojecsubmission->id));
        $testcount = 0;
        $succcount = 0;
        foreach ($testresults as $tr) {
            $testcount += $tr->testcount;
            $succcount += count($this->split_string(",", $tr->succtests));
        }
        $comperrorcount = $DB->count_records(self::TABLE_MOJEC_COMPILATIONERROR, array("mojec_id" => $mojecsubmission->id));

        $result = "Comp. Err.: " . $comperrorcount;
        $result .= "<br>";
        $result .= "Tests: " . $succcount . "/" . $testcount;
        $result = html_writer::div($result, "submissionmojecgrading");

        return $result;
    }

    /**
     * Splits a string by string.
     *
     * Behave exactly like {@link explode} apart from returning an
     * empty array in case string is empty.
     *
     * @param string $delimiter the boundary string.
     * @param string $string the input string.
     * @return array
     */
    private function split_string($delimiter, $string) {
        if (empty($string)) {
            return array();
        } else {
            return explode($delimiter, $string);
        }
    }

    /**
     * Returns the view that should be displayed to the student.
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    private function view_student_summary(stdClass $submission, & $showviewlink) {
        return $this->view_grading_summary($submission, $showviewlink);
    }

    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $DB;
        $html = "";

        $html .= $this->assignment->render_area_files(self::COMPONENT_NAME,
            ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION,
            $submission->id);

        $mojecsubmission = $DB->get_record(self::TABLE_ASSIGNSUBMISSION_MOJEC, array("submission_id" => $submission->id));
        $testresults = $DB->get_records(self::TABLE_MOJEC_TESTRESULT, array("mojec_id" => $mojecsubmission->id));
        foreach ($testresults as $tr) {
            $html = html_writer::div($tr->testname);

            if ($tr->succtests) {
                $html .= html_writer::tag("h5", "Successful Tests");
                $html .= html_writer::alist(explode(",", $tr->succtests));
            }

            $testfailures = $DB->get_records(self::TABLE_MOJEC_TESTFAILURE, array("testresult_id" => $tr->id));
            if ($testfailures) {
                $html .= html_writer::tag("h5", "Failed Tests");

                foreach ($testfailures as $tf) {
                    $tmpdiv = html_writer::div("Testheader:", "failedtestsidebar");
                    $tmpdiv .= html_writer::div($tf->testheader, "failedtestcontent");
                    $html .= html_writer::div($tmpdiv, "failedTestWrapper");

                    $tmpdiv = html_writer::div("Message:", "failedtestsidebar");
                    $tmpdiv .= html_writer::div($tf->message, "failedtestcontent");
                    $html .= html_writer::div($tmpdiv, "failedTestWrapper");

                    $tmpdiv = html_writer::div("Trace:", "failedtestsidebar");
                    if ($tf->trace) {
                        $tmpdiv .= html_writer::start_div("failedtestcontent");
                        $checkbid = html_writer::random_id();
                        $tmpdiv .= html_writer::label("show trace", $checkbid, false, array("class" => "collapsible"));
                        $tmpdiv .= html_writer::checkbox(null, null, false, null, array("id" => $checkbid));
                        $tmpdiv .= html_writer::div($tf->trace);
                        $tmpdiv .= html_writer::end_div();
                    } else {
                        $tmpdiv .= html_writer::div("no trace", "failedtestcontent");
                    }
                    $html .= html_writer::div($tmpdiv, "failedTestWrapper");
                }

            }
            $html = html_writer::div($html);
        }
        $compilationerrors = $DB->get_records(self::TABLE_MOJEC_COMPILATIONERROR, array("mojec_id" => $mojecsubmission->id));
        if ($compilationerrors) {
            $html .= html_writer::tag("h5", "Compilation errors");
            foreach ($compilationerrors as $ce) {
                $tmpdiv = html_writer::div("Message:", "failedtestsidebar");
                $tmpdiv .= html_writer::div($ce->message, "failedtestcontent");
                $html .= html_writer::div($tmpdiv, "failedTestWrapper");

                $tmpdiv = html_writer::div("Column-No.:", "failedtestsidebar");
                $tmpdiv .= html_writer::div($ce->columnnumber, "failedtestcontent");
                $html .= html_writer::div($tmpdiv, "failedTestWrapper");

                $tmpdiv = html_writer::div("Line-No.:", "failedtestsidebar");
                $tmpdiv .= html_writer::div($ce->linenumber, "failedtestcontent");
                $html .= html_writer::div($tmpdiv, "failedTestWrapper");

                $tmpdiv = html_writer::div("Position:", "failedtestsidebar");
                $tmpdiv .= html_writer::div($ce->position, "failedtestcontent");
                $html .= html_writer::div($tmpdiv, "failedTestWrapper");
            }
        }

        return $html;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $assignmentid = $this->assignment->get_instance()->id;

        $mojec = $DB->get_record(self::TABLE_ASSIGNSUBMISSION_MOJEC, array('assignment_id' => $assignmentid), "id");

        if ($mojec) {
            $this->delete_test_data($mojec->id);
        }

        // Delete mojec assignment.
        $DB->delete_records(self::TABLE_ASSIGNSUBMISSION_MOJEC, array("assignment_id" => $assignmentid));

        return true;
    }

    private function delete_test_data($mojecid) {
        global $DB;

        $testresult = $DB->get_record(self::TABLE_MOJEC_TESTRESULT, array("mojec_id" => $mojecid), "id", IGNORE_MISSING);
        if (!$testresult) {
            return true;
        }

        // Delete compilation errors.
        $DB->delete_records(self::TABLE_MOJEC_COMPILATIONERROR, array("mojec_id" => $mojecid));

        // Delete test failures.
        $DB->delete_records(self::TABLE_MOJEC_TESTFAILURE, array("testresult_id" => $testresult->id));

        // Delete test results.
        $DB->delete_records(self::TABLE_MOJEC_TESTRESULT, array("mojec_id" => $mojecid));

        return true;
    }

    /**
     * Return true if there are no submission files
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION) == 0;
    }


    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(
            ASSIGNSUBMISSION_MOJEC_FILEAREA_SUBMISSION => get_string("mojec_submissions", self::COMPONENT_NAME),
            ASSIGNSUBMISSION_MOJEC_FILEAREA_TEST => get_string("mojec_tests", self::COMPONENT_NAME)
        );
    }
}


