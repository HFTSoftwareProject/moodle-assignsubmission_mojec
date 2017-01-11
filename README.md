# Moodle JUnit Exercise Corrector (MoJEC) Plugin

A Moodle plugin to assist teachers correcting JUnit exercises.

This plugin allows students to submit their Java exercises, let them be tested against
a set of JUnit tests (that have been priorly provided by the teacher) and receive immediate feedback
on the test results.

For this to work, the plugin communicates with an external webservice providing essentially the following services on the given paths:
* POST **/v1/unittest**: Expects the assignment id and a zip file containing the unit test files as http post parameters. 
* POST **/v1/tasks**: Expects the assignment id and a zip file containing the java files that should be tested as http post parameters. Extracts the zip file, compiles the java files and runs the tests (provided via /v1/unittest). Returns the results in form of JSON.
* DELETE **/v1/unittest?assignmentId={id}**: Triggers the deletion of the test files.

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

## Technical Details
For the communication between the plugin and the webservice the data is en/decoded as JSON.
Here is an example JSON response after uploading the task Java file.

```JSON
{
  "testResults": [
    {
      "testName": "CalculatorTest",
      "testCount": 5,
      "failureCount": 0,
      "successfulTests": [
        "div",
        "mult",
        "sub",
        "add",
        "sum"
      ],
      "testFailures": []
    },
    {
      "testName": "CalculatorSecondTest",
      "testCount": 5,
      "failureCount": 1,
      "successfulTests": [
        "add2",
        "sub2",
        "div2",
        "sum2"
      ],
      "testFailures": [
        {
          "testHeader": "mult2(CalculatorSecondTest)",
          "message": "expected:<15.0> but was:<10.0>",
          "trace": "stacktrace (if existent)"
        }
      ]
}
```

The above shows the result of two JUnit test files (CalculatorTest and CalculatorSecondTest). The field “testCount” indicates the number of test methods within the test file. The field “failureCount” indicates how many tests have failed and the field “successfulTest” indicates the method names of passed tests. In case a test failed, the necessary information can be found as an entry in the "testFailures" array.

If there was an compilation error the relevant information is part of the "compilationErrors" array as shown below.

```JSON
"compilationErrors": [
    {
      "code": "compiler.err.expected",
      "columnNumber": 0,
      "kind": "ERROR",
      "lineNumber": 0,
      "message": "';' expected",
      "position": 46,
      "filePath": "/tmp/TaskNotCompilable.java",
      "startPosition": 46,
      "endPosition": 46
    }
  ]
}
```

## Bugs and Improvements?

If you've found a bug or if you've made an improvement to this plugin and want to share your code, please
open an issue in our Github project:
https://github.com/HFTSoftwareProject/moodle-assignsubmission_mojec/issues

