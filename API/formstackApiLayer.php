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

require_once dirname(__FILE__) . '/FormstackApi.php';

class FormStackApiLayer {
	private $apiToken = '';
	private $formstack = '';
	private $rawResponse = '';
	private $status = '';
	private $lastError = '';
	private $lastCall = '';

	public function __construct($at) {
		$this->apiToken = $at;
		$this->formstack = new FormstackApi($this->apiToken);
	}

	/**
     * Get the list of forms
     *
     * @return  array   rawResponse    Array of all Forms or Array of Folders
     */
	public function getFormList() {
		try {
			$response = $this->formstack->getForms(true);
			$this->status = 'success';
			$this->rawResponse = $response;
			return $this->rawResponse;
		} catch (Exception $e) {
			$this->status = 'error';
			$this->lastError = $e->getMessage();
			return null;
		}
	}

	/**
     * Get the detailed information of a specific Form
     *
     * @param   int     $form     The ID of the Form to look up
     *
     * @return  object  $rawResponse   A \stdClass representing all of the Form's data
     */
	public function getFormInfo($form) {
		try {
			$response = $this->formstack->getFormDetails($form);
			$this->status = 'success';
			$this->rawResponse = $response;
			unset($response->html);
			unset($response->javascript);
			return $this->rawResponse;
		} catch (Exception $e) {
			$this->status = 'error';
			$this->lastError = $e->getMessage();
			return null;
		}
	}

	/**
     * Get all the fields of a specific form
     *
     * @param   int     $form     The ID of the Form to look up
     *
     * @return  object  $rawResponse   A \stdClass representing all of the field's data
     */
	public function getFormFields($form) {
		try {
			$res = (object) array();
			$response = $this->makeRequest('form/' . $form . '/field.json');
			unset($response->html);
			unset($response->javascript);
			return $response;
		} catch (Exception $e) {
			return null;
		}
	}

	/**
     * Get all Submissions
     *
     * @param   string  $encryptionPassword The encryption password (if applicable)
     * @param   string  $minTime            Date/Time string for start time in EST to group Submissions
     * @param   string  $maxTime            Date/Time string for end time in EST to group Submissions
     * @param   array   $searchFieldIds     Array of Field IDs to base searching around
     * @param   array   $searchFieldValues  Array of values related to IDs in searchFieldIds
     * @param   int     $pageNumber         Page of Submissions to collect from
     * @param   int     $perPage            Number of Submissions to retrieve per request
     * @param   string  $sort               Sort direction ('DESC or 'ASC')
     * @param   bool    $data               Whether to include Submission data in request
     * @param   bool    $expandData         Whether to include extra data formatting for included data
     *
     * @return  array   $rawResponse        All retrieved Submissions for the given Form
     */
	public function getSubmissions($encryptionPassword = '',
        $minTime = '', $maxTime = '', $searchFieldIds = array(),
        $searchFieldValues = array(), $pageNumber = 1, $perPage = 99, $sort = 'DESC',
        $data = false, $expandData = false) {

			$out = array();
			$out['count'] = 0;
			try {
				foreach ($this->getFormList() as $forms) {
					foreach ($forms as $form) {		
						$formId = $form->id;
						$response = $this->formstack->getSubmissions($formId,
																 $encryptionPassword,
																 $minTime,
																 $maxTime,
																 $searchFieldIds,
																 $searchFieldValues,
																 $pageNumber,
																 $perPage,
																 $sort,
																 $data,
																 $expandData);
						$out['count'] = $out['count'] + count($response);
						$out[$formId] = $response;
					}
				}
				$this->rawResponse = $out;
				$this->status = 'success';
				return $this->rawResponse;
			} catch (Exception $e) {
				$this->status = 'error';
				$this->lastError = $e->getMessage();
				return null;
			}
	}

	/**
     * Get all Submissions for a specific form
     *
     * @param   int     $formId             The ID of the Form to retrieve Submissions for
     * @param   string  $encryptionPassword The encryption password (if applicable)
     * @param   string  $minTime            Date/Time string for start time in EST to group Submissions
     * @param   string  $maxTime            Date/Time string for end time in EST to group Submissions
     * @param   array   $searchFieldIds     Array of Field IDs to base searching around
     * @param   array   $searchFieldValues  Array of values related to IDs in searchFieldIds
     * @param   int     $pageNumber         Page of Submissions to collect from
     * @param   int     $perPage            Number of Submissions to retrieve per request
     * @param   string  $sort               Sort direction ('DESC or 'ASC')
     * @param   bool    $data               Whether to include Submission data in request
     * @param   bool    $expandData         Whether to include extra data formatting for included data
     *
     * @return  array   $rawResponse        All retrieved Submissions for the given Form
     */
	public function getFormSubmissions($form, $encryptionPassword = '',
        $minTime = '', $maxTime = '', $searchFieldIds = array(),
        $searchFieldValues = array(), $pageNumber = 1, $perPage = 99, $sort = 'DESC',
        $data = false, $expandData = false) {

		try {
			$response = $this->formstack->getSubmissions($form,
														 $encryptionPassword,
														 $minTime,
														 $maxTime,
														 $searchFieldIds,
														 $searchFieldValues,
														 $pageNumber,
														 $perPage,
														 $sort,
														 $data,
														 $expandData);
			$this->status = 'success';
			$this->rawResponse = $response;
			return $this->rawResponse;
		} catch (Exception $e) {
			$this->status = 'error';
			$this->lastError = $e->getMessage();
			return null;
		}
	}

    /**
     * Get the details of a specific Submission
     *
     * @param   int     $submissionId       The ID of the Submission to get data for
     * @param   string  $encryptionPassword The encryption password on the Form (if applicable)
     *
     * @return  object  $rawResponse         \stdClass representation of the Submission Data
     */
	public function getSubmissionDetails($submissionId, $encryptionPassword = '') {
		try {
			$response = $this->formstack->getSubmissionDetails(intval($submissionId),
														 	   $encryptionPassword);
			$this->status = 'success';
			$this->rawResponse = $response;
			return $this->rawResponse;
		} catch (Exception $e) {
			$this->status = 'error';
			$this->lastError = $e->getMessage();
			return null;
		}
	}

	/**
     * Get the last Raw Response
     *
     * @return  object  $rawResponse         Last Raw Response
     */
	public function getRawResponse() {
		return $this->rawResponse;
	}

	/**
     * Get last status
     *
     * @return  object  $status         Last Status
     */
	public function getStatus() {
		return $this->status;
	}

	/**
     * Get last error
     *
     * @return  object  $error         Last Error
     */
	public function getLastError() {
		return $this->lastError;
	}

	/**
     * Get last call
     *
     * @return  object  $lastCall         Last call 
     */
	public function getLastCall() {
		return $this->lastCall;
	}

	/**
     * Make a custom request
	 *
     * @throws  Exception                   If some communication error happened
     *
     * @param   string    $req    The reuqest to be sent to FormStack Web Services
     */
	private function makeRequest($req) {
		$this->lastCall = $req;
		try {
			$this->rawResponse = $this->formstack->request($req);
			$this->status = 'success';
			return json_decode($this->rawResponse);
		} catch (Exception $e) {
			$this->status = 'error';
			$this->lastError = $e->getMessage;
			throw new Exception($e->getMessage(), 1);
		}
	}
}

?>