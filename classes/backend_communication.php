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

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the methods to communicate with the mojec backend web service.
 *
 * @package    assignsubmission_mojec
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignsubmission_mojec_backend_communication {

    const COMPONENT_NAME = "assignsubmission_mojec";

    private $wsbaseaddress = "";

    /**
     * assignsubmission_mojec_backend_communication constructor.
     * @param string $wsbaseaddress
     */
    public function __construct($wsbaseaddress) {
        $this->wsbaseaddress = $wsbaseaddress;
    }

    /**
     * Posts the task file to the backend web service and returns the response if successful.
     *
     * @param stored_file $file the file to post
     * @param int $assignmentid the assignment id
     * @return mixed
     */
    public function mojec_post_task_file($file, $assignmentid) {
        $url = $this->wsbaseaddress . "/v1/task";
        return $this->mojec_post_file($url, "taskFile", $file, $assignmentid);
    }

    /**
     * Posts the test file to the backend web service and returns the response if successful.
     *
     * @param stored_file $file the file to post
     * @param int $assignmentid the assignment id
     * @return mixed
     */
    public function mojec_post_test_file($file, $assignmentid) {
        $url = $this->wsbaseaddress . "/v1/unittest";
        return $this->mojec_post_file($url, "unitTestFile", $file, $assignmentid);
    }

    /**
     * Posts the file to the url under the given param name.
     *
     * @param string $url the url to post to.
     * @param string $paramname the param name for the file.
     * @param stored_file $file the file to post.
     * @param int $assignmentid the assignment id.
     * @return mixed
     */
    public function mojec_post_file($url, $paramname, $file, $assignmentid) {
        if (!isset($file) or !isset($url) or !isset($paramname)) {
            return false;
        }

        $params = array(
            $paramname     => $file,
            "assignmentId" => $assignmentid
        );
        $options = array(
            "CURLOPT_RETURNTRANSFER" => true
        );
        $curl = new curl();
        $response = $curl->post($url, $params, $options);

        $info = $curl->get_info();
        if ($info["http_code"] == 200) {
            return $response;
        }

        // Something went wrong.
        debugging("MoJEC: Post file to server was not successful: http_code=" . $info["http_code"]);

        if ($info['http_code'] == 400) {
            \core\notification::error(get_string("badrequesterror", self::COMPONENT_NAME));
            return false;
        } else {
            \core\notification::error(get_string("unexpectederror", self::COMPONENT_NAME));
            return false;
        }
    }

    /**
     * Sends a delete request to the backend web service for the given assignmentid.
     *
     * @param int $assignmentid assignment id of the files that should be deleted in the backend.
     * @return bool
     */
    public function mojec_delete_files($assignmentid) {
        $url = $this->wsbaseaddress . "/v1/unittest?assignmentId=" . $assignmentid;
        $curl = new curl();
        return $curl->delete($url);
    }

}