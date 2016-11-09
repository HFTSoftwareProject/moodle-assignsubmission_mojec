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
define('ASSIGNSUBMISSION_MOJEC_FILEAREA', 'submissions_mojec');

/**
 * library class for mojec submission plugin extending submission plugin base class
 *
 * @package assignsubmission_mojec
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_mojec extends assign_submission_plugin {

    /**
     * Get the name of the mojec submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('mojec', 'assignsubmission_mojec');
    }

    /**
     * File format options
     *
     * @return array
     */
    private function get_file_options() {
        $fileoptions = array('subdirs'=>1,
            //'maxbytes'=>$this->get_config('maxsubmissionsizebytes'),
            //'maxfiles'=>$this->get_config('maxfilesubmissions'),
            'accepted_types'=>'*',
            'return_types'=>FILE_INTERNAL);
        if ($fileoptions['maxbytes'] == 0) {
            // Use module default.
            //$fileoptions['maxbytes'] = get_config('assignsubmission_file', 'maxbytes');
        }
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
            'files',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_mojec',
            ASSIGNSUBMISSION_MOJEC_FILEAREA,
            $submissionid);
        $mform->addElement('filemanager', 'files_filemanager', $this->get_name(), null, $fileoptions);

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
            'assignsubmission_mojec',
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
        global $OUTPUT;

        $fileoptions = $this->get_file_options();

        $data = file_postupdate_standard_filemanager($data,
            'files',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_mojec',
            ASSIGNSUBMISSION_MOJEC_FILEAREA,
            $submission->id);

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id,
            'assignsubmission_mojec',
            ASSIGNSUBMISSION_MOJEC_FILEAREA,
            $submission->id,
            'id',
            false);


        // Get the file and post it to our backend.
        $file = reset($files);
        $this->mojec_post_file($file);

        return true;
    }

    private function mojec_post_file($file) {
        if ($file) {
            $fpmetadata = stream_get_meta_data($file->get_content_file_handle());
            $fileuri = $fpmetadata["uri"];
            $filename = $file->get_filename();
            $curl = curl_init("http://localhost:8080/v1/task");
            $curlfile = curl_file_create($fileuri, null, $filename);
            $filedata = array(
                'taskFile' => $curlfile,
                'user' => $this->get_user_json());

            $headers = array("Content-Type:multipart/form-data");
            curl_setopt($curl, CURLOPT_HEADER, $headers);
            curl_setopt($curl, CURLOPT_POST, true); // enable posting
            curl_setopt($curl, CURLOPT_POSTFIELDS, $filedata);
            curl_exec($curl);
            if (!curl_errno($curl)) {
                $info = curl_getinfo($curl);
                if ($info['http_code'] == 200)
                    $errmsg = "File uploaded successfully";
            } else {
                $errmsg = curl_error($curl);
            }
            curl_close($curl);
        }
    }

    private function get_user_json() {
        global $USER;

        $userjson = json_encode(array(
            "email" => $USER->email,
            "id" => $USER->id,
            "userName" => $USER->username,
            "firstName" => $USER->firstname,
            "lastName" => $USER->lastname));

        return $userjson;
    }

    private function mojec_get_results() {
        $curl = curl_init("http://localhost:8080/v1/results");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        curl_close($curl);
        if ($result) {
            return $result;
        }
    }

    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $count = $this->count_files($submission->id, ASSIGNSUBMISSION_MOJEC_FILEAREA);

        $result = $this->assignment->render_area_files('assignsubmission_mojec',
            ASSIGNSUBMISSION_MOJEC_FILEAREA,
            $submission->id);
        $result .= "<br>";
        $result .= $this->mojec_get_results();
        return $result;
    }

    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        return $this->assignment->render_area_files('assignsubmission_mojec',
            ASSIGNSUBMISSION_MOJEC_FILEAREA,
            $submission->id);
    }

    /**
     * Return true if there are no submission files
     * @param stdClass $submission
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, ASSIGNSUBMISSION_MOJEC_FILEAREA) == 0;
    }


    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_MOJEC_FILEAREA => $this->get_name());
    }
}


