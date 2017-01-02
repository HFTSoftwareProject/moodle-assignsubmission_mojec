# Moodle JUnit Exercise Corrector (MoJEC) Plugin

A Moodle plugin to assist teachers correcting JUnit exercises.

This plugin allows students to submit their Java exercises, let them be tested against
a set of JUnit tests (that have been priorly provided by the teacher) and receive immediate feedback
on the test results.

For this to work, the plugin communicates with an external webservice providing essentially the following services on the given paths:
* **/v1/unittest**: Expects the assignment id and a zip file containing the unit test files as http post parameters. 
* **/v1/tasks**: Expects the assignment id and a zip file containing the java files that should be tested as http post parameters. Extracts the zip file, compiles the java files and runs the tests (provided via /v1/unittest). Returns the results in form of JSON.

See here for an implementation of the webservice: [MoJEC Backend](https://github.com/HFTSoftwareProject/MoJEC-Backend)

## Installation/Configuration
* Install this plugin by using the Moodle web installer, or by extracting the plugin archive to {Moodle_Root}/mod/assign/submission/mojec and visting the admins notifications page.
* Visit the plugin's settings and configure the base URL of the web service to use. (You need a running webservice to use, see [MoJEC Backend](https://github.com/HFTSoftwareProject/MoJEC-Backend) for a working solution)
* Done!

## Usage (Teacher)
* Create an Assignment
* In the Assignment settings: Scroll to the section **Submission types** and check the type **JUnit Exercise Corrector**
* Once **JUnit Exercise Corrector** is checked, upload a *single* ZIP file containing your JUnit tests in the corresponding JUnit test file upload environment.
* View aggregated test results in the grading table column **JUnit Exercise Corrector**
* View detailed results of a particular submission by clicking the magnifyer icon in the respective cell of the JUnit Exercise Corrector column of the grading table
* Download all MoJEC submissions by selecting the Grading Action **Download all submissions**

## Usage (Student)
* Navigate to the assignment
* Press **Add Submission** respectively **Edit Submission**
* Upload a *single* ZIP file containing the Java files to be tested and click **Save changes**
* View your test results in the **JUnit Exercise Corrector** row of the submission status table.
