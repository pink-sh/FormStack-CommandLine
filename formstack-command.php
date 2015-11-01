<?php
/**
 * Developed by: Enrico Anello
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

require_once dirname(__FILE__) . '/API/formstackApiLayer.php';

class FormStackCommand {
	private $formstack = null;
	private $isGoing = true;
	private $separator = "------------------------------------------\n";
	private $options = "1) Get List of Available Forms\n2) Get Form Info\n3) Get Form Fields\n4) Get Submissions For All Forms\n5) Get Form Submissions\n6) Get Submission Details\n7) Clear Current Values\n8) Exit\n\n";
	private $currentForm = 'NONE';
	private $parameters = array();
	private $choose = '0';

	public function __construct() {}

	/**
     * Runs the program
     */
	public function run() {
		$token = '';
		while ($token == '') {
			echo 'Insert FormStack token: ';
			$token = $this->readFromStdIn();
		}
		$this->token = $token;
		//$this->token = '1079f42994e08f027163e48b4b24a5df';
		$this->formstack = new FormStackApiLayer($this->token);
		$this->loop();
	}

	/**
     * User interface loop
     */
	private function loop() {
		while (true) {
			switch ($this->choose) {
				case '1' :
					$result = $this->formstack->getFormList();
					$this->checkForErrors();
					break;
				case '2' :
					$this->askForForm();
					$result = $this->formstack->getFormInfo($this->currentForm);
					$this->checkForErrors();
					break;
				case '3' :
					$this->askForForm();
					$result = $this->formstack->getFormFields($this->currentForm);
					$this->checkForErrors();
					break;
				case '4' :
					$this->askForSubmissions(true);
					$this->checkForErrors(true, true);
					break;
				case '5' :
					$this->askForSubmissions();
					$this->checkForErrors(true, true);
					break;
				case '6' :
					$this->askForParameter('submission_id', '', '');
					$this->askForParameter('encryption_password', '', '');
					$this->formstack->getSubmissionDetails( $this->parameters['submission_id'],
															$this->parameters['encryption_password']);
					$this->checkForErrors();
					break;
				case '7' :
					$this->currentForm = 'NONE';
					$this->parameters = array();
					break;
				case '8' :
					echo "Thank you!\n";
					exit;
			}
			echo $this->options;
			echo "Choose an option: ";
			$this->choose = $this->readFromStdIn();
		}
	}

	/**
     * Checks for errors on requests
     *
     * @param   bool    $askForSaveReport	If true asks to save the report on a file
     * @param   bool    $submissions 		If true we are handling submissions and totals are displayed
     *
     */
	private function checkForErrors($askForSaveReport = true, $submissions = false) {
		echo "\n" . $this->separator;
		if ($this->formstack->getStatus() == 'error')
			echo "Ops, something went wrong...\n" . $this->formstack->getLastError() . "\n\n";
		else {
			echo "\nRESULTS\n" . $this->separator;
			if ($submissions)
				echo "Number of submissions: " . count($this->formstack->getRawResponse()) . "\n\n";
			$this->printObject($this->formstack->getRawResponse());
			if ($askForSaveReport) 
				$this->saveReport($this->formstack->getRawResponse());
		}
		echo $this->separator . "\n\n";
	}

	/**
     * Requests submissions
     *
     * @param   bool    $total		If true gets the total submissions for each form
     *
     */
	private function askForSubmissions($total = false) {
		if (!$total)
			$this->askForForm();
		$this->askForParameter('encryption_password', '', '');
		$this->askForParameter('min_time', '', 'YYYY-MM-DD or YYYY-MM-DD HH:mm:SS');
		$this->askForParameter('max_time', '', 'YYYY-MM-DD or YYYY-MM-DD HH:mm:SS');
		$this->askForParameter('page_number', '1', '');
		$this->askForParameter('per_page', '99', '');
		$this->askForParameter('sort', 'DESC', 'ASC|DESC');
		$this->askForParameter('data', 'false', 'true|false');
		$this->askForParameter('expanded', 'false', 'true|false');
		$this->askForMultipleParameter(array('search_field_id', 'search_field_value'), 10);
		if (!$total)
			$this->formstack->getFormSubmissions(
							$this->currentForm,
							$this->parameters['encryption_password'],
							$this->parameters['min_time'],
							$this->parameters['max_time'],
							$this->parameters['multiple']['search_field_id'],
							$this->parameters['multiple']['search_field_value'],
							intval($this->parameters['page_number']),
							intval($this->parameters['per_page']),
							$this->parameters['sort'],
							$this->parameters['data'],
							$this->parameters['expanded']);
		else
			$this->formstack->getSubmissions(
							$this->parameters['encryption_password'],
							$this->parameters['min_time'],
							$this->parameters['max_time'],
							$this->parameters['multiple']['search_field_id'],
							$this->parameters['multiple']['search_field_value'],
							intval($this->parameters['page_number']),
							intval($this->parameters['per_page']),
							$this->parameters['sort'],
							$this->parameters['data'],
							$this->parameters['expanded']);
	}

	/**
     * Asks user for a form ID
     *
     */
	private function askForForm() {
		$ask = true;
		while ($ask) {
			echo 'Insert Form Id (Current=' . $this->currentForm . '): ';
			$form = $this->readFromStdIn();
			if ($form == '') {
				if ($this->currentForm != 'NONE') 
					$ask = false;
			} else {
				$this->currentForm = $form;
				$ask = false;
			}
		}
	}

	/**
     * Asks user for a custom parameter
     *
     * @param   String    $object 		Object name to be saved
     * @param   String    $default 		Default value
     * @param   String    $helper 		Helper string to be displayed
     * @param   bool 	  $mandatory 	If true the parameter is mandatory
     * @param   bool      $insert 		If true is a new parameter to insert
    */
	private function askForParameter($object, $default = '', $helper = '', $mandatory = false, $insert = true) {
		if (!array_key_exists($object, $this->parameters) || $this->parameters[$object] == '') {
			if ($default == '')
				$this->parameters[$object] = 'NONE';
			else 
				$this->parameters[$object] = $default;
		}
		$ask = true;
		while ($ask) {
			$parameter = $this->askPlain($object, $this->parameters[$object], $helper, $insert);
			if ($parameter == '') {
				if ($mandatory == false) {
					if ($default == '') { $this->parameters[$object] = $default; }
					$ask = false;
				}
			} else {
				$this->parameters[$object] = $parameter;
				$ask = false;
			}
		}
	}

	/**
     * Asks user for multiple parameters
     *
     * @param   array      $askForSaveReport	Object names to be saved
     * @param   integer    $submissions 		Max number of multiciplity
     *
     */
	private function askForMultipleParameter($objects = array(), $max = 10) {
		if (!array_key_exists('multiple', $this->parameters)) {
			$this->parameters['multiple'] = array();
		}
		foreach ($objects as $obj) {
			if (!array_key_exists($obj, $this->parameters['multiple'])) {
				$this->parameters['multiple'][$obj] = array();
			}
		}
		if (count($objects) < 1) return;

		$stop = false;
		for ($i = 1; $i <= $max; $i++) {
			foreach ($objects as $obj) {
				if (count($this->parameters['multiple'][$obj]) >= $i) 
					$default = $this->parameters['multiple'][$obj][$i-1];
				else
					$default = '';
				$parameter = $this->askPlain($obj.'_'.$i, $default, '');
				if ($parameter == '' && $default == '') $stop = true;
				if ($parameter != '') array_push($this->parameters['multiple'][$obj], $parameter);
			}
			if ($stop) break;
		}
	}

	/**
     * Ask something to users
     *
     * @param   String    $object 		Object name to be saved
     * @param   String    $default 		Default value
     * @param   String    $helper 		Helper string to be displayed
     * @param   bool      $insert 		If true is a new parameter to insert
     *
     * @return  String    $readVal	    The inserted value by the user
     */
	private function askPlain($object, $default, $helper, $insert=true) {
		if ($insert) echo 'Insert ';
		echo str_replace('_', ' ', $object) . ' (Current=' . $default .') ';
		if ($helper != '')
			echo '{' . $helper .'} ';
		echo ': ';
		$readVal = $this->readFromStdIn();
		$default = $default === 'NONE' ? '' : $default;
		return $readVal === '' ? $default : $readVal;
	}

	/**
     * Save the report
     *
     * @param   Object    $report		Object to be saved
     * @param   String    $name 		Custom file name
     *
     */
	private function saveReport($report, $name='') {
		$this->askForParameter('Save_report_to_file?', 'Yes', 'Yes|No', false, false);
		$save = strtolower($this->parameters['Save_report_to_file?']);
		if ($save == 'y' || $save == 'yes') {
			$directory = dirname(__FILE__) . '/reports/';
			if (!file_exists($directory)) {
				mkdir($directory);
			}
			$file = 'report_';
			if ($name != '') $file .= $name + '_';
			$file .= time() . '.csv';
			$fh = fopen($directory . $file, 'w');
			foreach ($this->prepareCSVData($report) as $csvLine) {
				fputcsv($fh, $csvLine);
			}
			fclose($fh);
			echo '*** File Saved in: ' . $directory . $file . "\n\n";
		}
	}

	/**
     * Print an object to stdout
     *
     * @param   String    $obj 		Object name to be saved
     *
     */
	private function printObject($obj) {
		$flatArray = $this->object2flatArray($obj);
		foreach ($flatArray as $line) {
			foreach ($line as $entry) {
				if ($entry == '')
					echo str_repeat(' ', 4);
				else
					echo $entry;
			}
			echo "\n";
		}
	}

	/**
     * Makes the lines of the object to be saved on csv having the same lengths
     *
     * @param   Object    $report 		Report to be saved
     *
     */
	private function prepareCSVData($report) {
		$flatArray = $this->object2flatArray($report);
		$max = 0;
		foreach ($flatArray as $entry)
			if ($max < count($entry))
				$max = count($entry);
		for ($x=0; $x < count($flatArray); $x++) {
			if (count($flatArray[$x]) < $max)
				for ($i = $max-count($flatArray[$x]); $i <= $max; $i++) 
					array_push($flatArray[$x], '');
		}
		return $flatArray;
	}

	/**
     * Recursive function to flats the returned object from a query to an array
     *
     * @param   String    $obj  		Object to be flatten
     * @param   Integer   $recursion	Recursion counter
     * @param   Array 	  $toSave		Flatten array
     *
     * @return   Array 	  $toSave		Flatten array
     */
	private function object2flatArray($obj, $recursion = 0, $toSave = array()) {
		if (is_array($obj)) {
			foreach ($obj as $array_entry) {
				foreach ($this->object2flatArray($array_entry, $recursion+1) as $entry)
					array_push($toSave, $entry);
				array_push($toSave, array());
			}
			return $toSave;
		} else {
			if (!empty($obj) && count($obj) > 0 && is_object($obj)) {
				foreach ($obj as $key => $value) {
					if (is_array($value)) {
						$line = array();
						for ($i = 1; $i <= $recursion; $i++) array_push($line, '');
						array_push($line, ucwords(str_replace("_", " ", $key)));
						array_push($toSave, $line);
						foreach ($this->object2flatArray($value, $recursion+1) as $entry)
							array_push($toSave, $entry);
					} else if (is_object($value)) {
						$line = array();
						for ($i = 1; $i <= $recursion; $i++) array_push($line, '');
						array_push($line, ucwords(str_replace("_", " ", $key)));
						array_push($toSave, $line);
						foreach ($this->object2flatArray($value, $recursion+1) as $entry)
							array_push($toSave, $entry);
					} else {
						$line = array();
						for ($i = 1; $i <= $recursion; $i++) array_push($line, '');
						array_push($line, ucwords(str_replace("_", " ", $key)));
						array_push($line, '=');
						array_push($line, $value);
						array_push($toSave, $line);
					}
				}
			}
			return $toSave;
		}
	}

	/**
     * Read from standard input
     *
     *
     * @return   String 	  $line		Line
     */
	private function readFromStdIn() {
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		return trim($line);
	}

}

?>