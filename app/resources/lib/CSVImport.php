<?php

	/*
	 TODO:
	 	* hook transform function? overrides or compliments default transform function?
	 	* along with csvMap, pass a conversion function (function name) for changing date, time, datetime, and number formats -- 
	 	  this would be applied before passing the row to the transform function(s)
	 */

	class CSVImport {
		const JOBS_TABLE = 'appgini_csv_import_jobs';
		const JOBS_LOG_TABLE = 'appgini_csv_import_jobs_log'; // TODO
		const OLD_JOBS_AGE = 2; // age in days after which jobs are cleaned up form db
		const MAX_EXECUTION_TIME = 1; // batch processing would respect this time, in seconds
		const CLEAN_CSV_DIR = 'import-csv'; // relative to appDir
		const DEBUG = false; // causes printing debug array contents to CSVImport.log inside app dir

		/*
			CONFIG LIMITATION: if you are running a batch import by passing a jobId,
			you have to redefine rowFilterCallback and rowTransformCallback since
			callbacks can't be serialized for storage :/
		*/
		private $config = [ /* see $this->configure() */ ];

		private $jobId, $memberInfo, $timeElapsed = 0.0, $startTS, $pkAutoInc = [];

		public $error = ''; // on error, would include a string to be used as key to $Translation
		public $logs = []; // array containing detailed logging info
		public $debug = []; // array containing debug info

		public function __destruct() {
			if(self::DEBUG)
				@file_put_contents(APP_DIR . '/CSVImport.log', json_encode([
					'datetime' => date('Y-m-d H:i:s'),
					'_REQUEST' => $_REQUEST,
					'_SERVER' => $_SERVER,
					'debug' => $this->debug,
					'logs' => $this->logs,
					'config' => $this->config,
				], JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n/* ----------------------------- */\n", FILE_APPEND);
		}

		public function __construct($config = []) {
			@ini_set('auto_detect_line_endings', '1');

			$this->memberInfo = getMemberInfo();

			// initial setup and cleanup if needed
			$this->setupDirs();
			$this->setupDb();
			$this->cleanupOldJobs();

			$this->debug[] = 'threadId: ' . $this->threadId();

			// do we have a jobId? This case means we're resuming a long import job
			if(!empty($config['jobId'])) {
				if(!$this->loadJob($config['jobId'])) {
					$this->error = 'Invalid import job';
					return;
				}

				$this->debug[] = 'Loaded existing job ' . $this->jobId;

				// if passed config contains more than just a jobId, we should reconfigure the job with passed config
				if(count($config) > 1) $this->configure($config);
				return;
			}

			// we are creating a new import job
			// assign provided config to class props
			$this->configure($config, false);

			// get PK field name and whether it's auto-incrment or not, and store into config
			$this->pkIsAutoIncrement();

			// create a jobId that we can refer to later
			$this->saveJob();

			$this->debug[] = 'Created new job ' . $this->jobId;
		}

		/* if nothing returned, check ->error */
		public function getJobId() {
			return $this->jobId;
		}

		public function getJobInfo() {
			if(!$this->jobId) {
				$this->error = 'invalid import job';
				return false;
			}

			// which config keys to return?
			$configKeys = ['table', 'fieldSeparator', 'fieldWrapper', 'updateExistingRecords', 'csvHasHeaderRow', 'csvMap', 'importedCount', 'importedCountInsert', 'importedCountUpdate', 'importedCountSkip', 'totalRecords', 'importFinished', 'fastMode'];

			return array_merge(
				array_intersect_key($this->config, array_flip($configKeys)),
				['jobId' => $this->jobId, 'progress' => $this->progress()]
			);
		}

		/* function to reconfigure job -- useful during user's csv preview step, and also is used when 1st creating a job to set defaults */
		public function configure($config = [], $save = true) {
			if(!$this->jobId && $save) {
				$this->error = 'invalid import job';
				return false;
			}

			$this->debug[] = [ 'configure' => [ 'config' => $config, 'save' => $save ]];

			$publicConfig = [
				'table' => '',
				'fieldSeparator' => ',',
				'fieldWrapper' => '"',
				'updateExistingRecords' => false,
				'rowFilterCallback' => null,
				'rowTransformCallback' => null,
				'csvFile' => '',
				'csvHasHeaderRow' => false,
				'csvMap' => [], // ['fieldname', ...] -- to ignore a CSV column rather than map it to a field, use false instead of field name
				'moreOptions' => [], // this is passed as-is to callback functions (rowFilterCallback, rowTransformCallback)
				'fastMode' => false, // can be enabled only by super admin
			];

			// initial defaults for private configs -- for internal use, can't be set outside class
			$privateConfig = [
				'recordsPerInsert' => 100, // this will be respected only if fastMode is on, otherwise only 1 record per insert
				'cleanCsvFileReady' => false, // boolean indicating if clean CSV file ready for importing to db
				// 'cleanCsvFile' => full path to clean CSV file
				// 'rawIndex' => last read position from csvFile
				// 'cleanIndex' => last read position from cleanCsvFile
				// 'pkAutoInc' => bool indicating if PK of table is auto-increment
				// 'pkFieldName' => name of pk field of table
				'totalRecords' => 0, // # total records to import
				'importedCount' => 0, // # records imported
				'importedCountSkip' => 0, // # records skipped
				'importedCountInsert' => 0, // # records imported as new ones
				'importedCountUpdate' => 0, // # records imported as updated ones
				'lastInsertId' => null,
				'importFinished' => false,
			];

			// apply any missing config items as default to $this->config
			$this->config = array_merge($publicConfig, $privateConfig, $this->config);
			
			foreach ($publicConfig as $key => $value)
				if(isset($config[$key])) $this->config[$key] = $config[$key];

			// if user specified a table, reset pk config
			if(isset($config['table'])) {
				unset($this->config['pkAutoInc'], $this->config['pkAutoInc']);
				$this->pkIsAutoIncrement();
			}

			if($save) return $this->saveJob();

			return true;
		}

		public function importBatch() {
			if(!$this->jobId) {
				$this->error = 'invalid import job';
				return false;
			}

			// if job finished, no need to proceed!
			if($this->config['importFinished']) {
				$this->debug[] = 'Import finished';
				return true;
			}

			if(!$this->validateConfig()) {
				$this->debug[] = 'Invalid general config';
				return false;
			}

			// next, try to create a lock and proceed
			if(!$this->lockJob()) {
				$this->debug[] = 'Can\'t lock job';
				return false;
			}

			// Then, check that cleanCsvFile is ready, $this->cleanupCSV() should return true;
			// either there is an error during cleanup, which would be mentioned in ->error
			// or cleanup is still in progress, which would be indicated in return val of ->progress()
			if(!$this->cleanupCSV()) {
				$this->debug[] = 'Cleanup error, or still in progress';
				return $this->unlockJob(false);
			}

			// validate config for import
			if(!$this->validateConfig(0, 1)) {
				$this->debug[] = 'Invalid import config';
				return $this->unlockJob(false);
			}

			// open clean csv file for reading records and importing them to db
			if(!($fpClean = @fopen($this->config['cleanCsvFile'], 'r'))) {
				$this->error = 'error reading csv file';
				return $this->unlockJob(false);
			}

			// skip already read lines
			if($this->config['cleanIndex']) {
				$this->debug[] = 'Clean CSV file: jumping to position ' . $this->config['cleanIndex'];
				fseek($fpClean, $this->config['cleanIndex']);
			}

			$eo = ['silentErrors' => true];
			$table = makeSafe($this->config['table']);
			$numFields = count($this->config['csvMap']);

			while(!feof($fpClean) && !$this->timeUp()) {
				// Starting with (cleanIndex), read up to {recordsPerInsert} lines from {cleanCsvFile}
				$rows = [];

				while(
					!feof($fpClean) &&
					!$this->timeUp() &&
					// if recordsPerInsert == 1, count(rows) will always be 0, and the condition would always
					// be true .. the loop would continue until any of the above conditions is no longer true
					count($rows) < $this->config['recordsPerInsert']
				) {
					$line = @fgetcsv($fpClean, 0, $this->config['fieldSeparator'], $this->config['fieldWrapper']);

					// record current position in clean csv
					$this->config['cleanIndex'] = ftell($fpClean);

					if(!is_array($line)) {
						$this->debug[] = 'Error reading a new line. Current cleanIndex: ' . $this->config['cleanIndex'];
						continue;
					}
					
					// skip if num columns in read line not same as num fields
					// --- this shouldn't usually happen for clean CSV files
					if(count($line) != $numFields) {
						$this->logs[] = "csv-index: {$this->config['cleanIndex']}, error: value count doesn't match field count, action: skipped.";
						$this->saveJob();
						continue;
					}

					$this->debug[] = 'Current cleanIndex: ' . $this->config['cleanIndex'];
					$this->debug[] = ['line before mapping' => $line];
					
					// combine csvMap and line, skipping CSV values if corresponding csvMap value is false rather than a fieldname
					$row = [];
					foreach ($this->config['csvMap'] as $i => $fieldname)
						if($fieldname) $row[$fieldname] = $line[$i];

					// if rowFilterCallback returns false, skip this row
					if(is_callable($this->config['rowFilterCallback']))
						if(!call_user_func_array($this->config['rowFilterCallback'], [$row, $this->config['moreOptions']])) {
							$this->logs[] = "csv-index: {$this->config['cleanIndex']}, status: rowFilterCallback returned false, action: skipped.";
							$this->saveJob();
							continue;
						}

					// if rowTransformCallback is a function, use it to return transformed data
					if(is_callable($this->config['rowTransformCallback'])) {
						$this->debug[] = ['Passing line to rowTransformCallback' => $row];
						$row = call_user_func_array($this->config['rowTransformCallback'], [$row, $this->config['moreOptions']]);
						$this->debug[] = ['rowTransformCallback returned' => $row];
					}

					// prepare $line for query
					$line = array_map(function($v) {
						return $v === '' ? 'NULL' :  "'" . makeSafe($v) . "'";
					}, array_values($row));
					
					$this->debug[] = ['line after mapping, transforming and prepping' => $line];

					// if recordsPerInsert > 1 then do a mass insert after the loop 
					if($this->config['recordsPerInsert'] > 1 && $this->config['fastMode'] && $this->memberInfo['admin']) {
						$rows[] = '(' . implode(',', $line) . ')';
						$this->debug[] = 'Rows to insert in this run so far: ' . count($rows);
						continue;
					}

					$this->insertOneRecord($line);
				}
				
				if(!count($rows)) {
					$this->debug[] = 'No rows to insert in this run';
					continue;
				}

				$this->saveJob();
				
				$query = 'INSERT IGNORE ' . // IGNORE has no effect in case of using ON DUPLICATE KEY UPDATE
						"INTO `{$table}` " .
						'(`' . implode('`,`', array_filter($this->config['csvMap'])) . '`) ' .
						"VALUES \n" .
						implode(",\n", $rows) .
						$this->updateClause();
				
				$this->debug[] = ['batch query' => $query];

				$eo['error'] = ''; // to reset previous errors
				$res = sql($query, $eo);

				/*
				 The problem with mysqli_affected_rows() is that it counts an UPDATEd record as 2 affected rows,
				 and an INSERTed one as 1. Doing a bulk INSERT+UPDATE makes this very unreliable when determining
				 actual count of changes because there is probably a mix of INSERT and UPDATE operations :/
				 */
				$affectedRows = min(count($rows), db_affected_rows());

				if(!$res) {
					$error = $this->memberInfo['admin'] && $eo['error'] ? ", error: {$eo['error']}" : ($eo['error'] ? ', error: db error' : '');
					$action = $affectedRows > 0 ? 'inserted' : 'skipped';
					$this->logs[] = "csv-index: {$this->config['cleanIndex']}{$error}, action: {$action}.";
					$this->saveJob();
					continue;
				}

				$this->config['importedCount'] += $affectedRows;

				$this->saveJob();
			}

			if(feof($fpClean)) $this->config['importFinished'] = true;

			$this->unlockJob();

			if($this->config['importFinished'] === true) $this->removeJobArtifacts();

			// empty error property to avoid false errors in case of escaped records
			$this->error = '';

			return $this->config['importFinished'];
		}

		private function updateClause() {
			if(!$this->config['updateExistingRecords']) return '';

			// if update enabled, append 'ON DUPLICATE KEY UPDATE `field1`=VALUES(`field1`), `field2`=VALUES(`field2`), ...'
			return ' ON DUPLICATE KEY UPDATE ' . implode(
				', ',
				array_map(
					function($field) { return "`{$field}`=VALUES(`{$field}`)"; },
					array_filter($this->config['csvMap'])
				)
			);
		}

		private function removeJobArtifacts() {
			if(!$this->config['importFinished']) return;

			/* delete raw csv file */
			@unlink($this->config['csvFile']);

			/* delete clean csv file */
			@unlink($this->config['cleanCsvFile']);

			/* delete job from db */
			$tn = self::JOBS_TABLE;
			$eo = ['silentErrors' => true];
			$sUsername = makeSafe($this->memberInfo['username']);
			$sjId = makeSafe($this->jobId);
			sql("DELETE FROM `{$tn}` WHERE `id`='{$sjId}' AND `memberID`='{$sUsername}'", $eo);
		}

		public function elapsed() {
			if(!$this->jobId) {
				$this->error = 'invalid import job';
				return false;
			}

			$tn = self::JOBS_TABLE;
			$startTime = sqlValue("SELECT `inserted` FROM `{$tn}` WHERE ``");
		}

		public function progress() {
			if(!$this->jobId) {
				$this->error = 'invalid import job';
				return false;
			}

			if($this->config['importFinished']) return 1;

			// if clean csv file not yet started, assume zero progress
			if(!$cleanCsvSize = @filesize($this->config['cleanCsvFile'])) return 0;

			// if raw file removed by some cleanup process or whatever, assume size same as clean csv
			if(!$rawCsvSize = @filesize($this->config['csvFile'])) $rawCsvSize = $cleanCsvSize;
			
			// progress of cleanup task, weighted down to 5% of total progress
			$progress =  min($this->config['rawIndex'] / $rawCsvSize, 1) * .05;

			// progress of import task, given 95% weight of total progress
			$progress += min(thisOr($this->config['cleanIndex'], 0) / thisOr($cleanCsvSize, $rawCsvSize), 1) * .95;

			// max progress is 100%
			$progress = min(1, round($progress, 3));

			return $progress;
		}

		private function validateConfig($forCleanup = 0, $forImport = 0) {
			// general purpose validation (initial, before any specific processing steps)
			if(
				empty($this->config['table']) ||
				!userCanImport() ||
				(
					!getTablePermissions($this->config['table'])['insert'] &&
					!getTablePermissions($this->config['table'])['edit']
				)
			) {
				$this->debug[] = ['getTablePermissions' => getTablePermissions($this->config['table'])];
				$this->error = 'tableAccessDenied';
				return false;
			}

			// force default fieldSeparator, fieldWrapper if empty
			$this->config['fieldSeparator'] = $this->fixUtf8($this->config['fieldSeparator'], ',');
			$this->config['fieldWrapper'] = $this->fixUtf8($this->config['fieldWrapper'], '"');

			if(
				empty($this->config['csvMap']) ||
				!is_array($this->config['csvMap']) ||
				!count($this->config['csvMap']) ||
				!strlen(implode('', $this->config['csvMap'])) // this means all CSV columns are skipped and nothing to import
			) {
				$this->error = 'invalid csv map';
				return false;
			}

			// only super admin can enable fast mode
			$this->config['fastMode'] = ($this->memberInfo['admin'] && $this->config['fastMode']);

			// if current user not a super admin, or if super admin didn't enable fast mode, make sure recordsPerInsert is 1
			if(!$this->memberInfo['admin'] || !$this->config['fastMode']) $this->config['recordsPerInsert'] = 1;

			if(
				$forCleanup && (
					empty($this->config['csvFile']) ||
					!is_readable($this->config['csvFile'])
				)
			) {
				$this->error = 'error reading csv file';
				return false;
			}

			if(
				$forImport && (
					empty($this->config['cleanCsvFileReady']) || 
					!is_readable($this->config['cleanCsvFile']) ||
					!$this->config['cleanCsvFileReady']
				)
			) {
				$this->error = 'invalid clean csv file';
				return false;
			}

			// user can't insert and hasn't enabled updating?
			if(
				$forImport &&
				!$this->config['updateExistingRecords'] && 
				!getTablePermissions($this->config['table'])['insert']
			) {
				$this->debug[] = 'user has no insert permission to table ' . $this->config['table'] . ' and hasn\'t enabled updates';
				$this->error = 'tableAccessDenied';
				return false;
			}

			return true;
		}

		private function userCanUpdate($fields) {
			// super admin can do whatever he wants!
			if($this->memberInfo['admin']) return true;

			// if we're inserting only without replacing, no need to continue further checks
			// the rationale here is that we'll rechecking updateExistingRecords while constructing the query
		    // and would make an INSERT query if it's false, and thus the true returned here merely means we
		    // need not worry about an authorized UPDATE as this would be prevented somewhere else
			// and we don't need to perform further checks unnecessarily here
			if(!$this->config['updateExistingRecords']) return true;


			// if user has full edit access to table, no further checks needed
			$table = makeSafe($this->config['table']);
			if(getTablePermissions($table)['edit'] == 3) return true;


			// OR else, PK must be part of csvMap and user have edit permission to that record
			// pk value must be provided to check if current user can replace record
			$pkIndex = array_search(
				$this->config['pkFieldName'], 
				/* purpose of array_filter below is to skip fields set to false */
				array_filter($this->config['csvMap'])
			);

			// the raiotnale here is that if user wants to update records, but is not supplying a PK
			// she might accidently overwrite an unauthorized record. what we need to fix here later on
			// is that we should check to see if any of the csvMap fields specified is a unique and in
			// this case, we can determine if the user is authorized to update it or not
			if($pkIndex === false) {
				$this->debug[] = "userCanUpdate(): updateExistingRecords but no PK value in CSV";
				$this->error = 'missing pk field in csv';
				return false;
			}

			$id = trim($fields[$pkIndex], "'");
			$recordExists = sqlValue("SELECT COUNT(1) FROM `{$table}` WHERE `{$this->config['pkFieldName']}`='" . makeSafe($id) . "'") > 0;
			$this->debug[] = "A record with id '{$id}' " . ($recordExists ? 'found' : 'not found');

			// if id exists, make sure user can edit
			if($recordExists &&	!check_record_permission($table, $id, 'edit')) {
				$this->debug[] = "userCanUpdate(): updateExistingRecords but user has no edit permission for record {$id}";
				$this->error = 'tableAccessDenied';
				return false;
			}

			// at this point, record is either new (id doesn't exist), or user can edit record with given $id
			$this->debug[] = 'userCanUpdate(): updateExistingRecords and user has can ' . ($recordExists ? "edit record {$id}" : 'insert record');
			return true;
		}

		private function insertOneRecord($fields) {
			// we'll insert a single record here
			// and if it's ok, assign owner as current user
			$table = makeSafe($this->config['table']);

			if(!$this->userCanUpdate($fields)) {
				$this->config['importedCountSkip']++;
				$this->logs[] = "csv-index: {$this->config['cleanIndex']}, error: {$this->error}, action: skipped.";
				$this->saveJob();
				return;
			}

			$query = "INSERT IGNORE INTO `{$table}` " .
				/* purpose of array_filter below is to skip fields set to false */
				'(`' . implode('`,`', array_filter($this->config['csvMap'])) . '`) ' .
				"VALUES \n" .
				'(' . implode(",", $fields) . ')' . $this->updateClause();

			$this->debug[] = $query;

			$eo = ['silentErrors' => true];
			sql($query, $eo);
			$affectedRows = db_affected_rows(); // 0 = no action, 1 = insert, 2 = update
			$action = (!$affectedRows ? 'skipped' : ($affectedRows == 1 ? 'inserted' : 'updated'));

			$error = $this->memberInfo['admin'] && $eo['error'] ? ", error: {$eo['error']}" : ($eo['error'] ? ', error: db error' : '');
			$this->logs[] = "csv-index: {$this->config['cleanIndex']}, action: {$action}{$error}";

			if($action == 'skipped') {
				$this->config['importedCountSkip']++;
				$this->saveJob();
				return;
			}

			// update import stats
			$this->config['importedCount']++;
			$action == 'updated' ? $this->config['importedCountUpdate']++ : $this->config['importedCountInsert']++;

			// if no new insert id, skip ownership
			$insertId = $this->getInsertId($fields);
			if($this->config['lastInsertId'] == $insertId) {
				$this->logs[] = "csv-index: {$this->config['cleanIndex']}, status: no insert id. no owner assigned.";
				$this->saveJob();
				return;
			}

			// keep record of insert id
			$this->config['lastInsertId'] = $insertId;

			// assign owner
			// if record updated, update ownership dateUpdated only, leaving owner user as-is
			$setOwner = ($action == 'updated' ? false : $this->memberInfo['username']);
			if(!set_record_owner($table, $insertId, $setOwner)) {
				$this->logs[] = "csv-index: {$this->config['cleanIndex']}, error: failed to assign/update ownership.";
				$this->saveJob();
				return;
			}

			$this->logs[] = "csv-index: {$this->config['cleanIndex']}, id: {$insertId}, " . ($setOwner === false ? 'updated' : 'added new') . ' ownership record.';
			$this->saveJob();
		}

		/* returns boolean indicating if PK of given table is auto-increment, cached for performance */
		private function pkIsAutoIncrement() {
			// caching
			if(isset($this->config['pkAutoInc']) && !empty($this->config['pkFieldName'])) return $this->config['pkAutoInc'];

			// get pk of table
			$table = $this->config['table'];
			if(empty($table)) return false;

			$pk = getPKFieldName($table);
			$this->config['pkFieldName'] = $pk;
			$this->debug[] = "PK field for this table is {$pk}";

			// check if it's auto-increment
			$field = sqlValue("SHOW COLUMNS FROM `{$table}` WHERE Field='{$pk}' AND Extra LIKE '%auto_increment%'", $eo);
			$this->config['pkAutoInc'] = ($field ? true : false);

			$this->debug[] = "PK field is auto-increment? " . ($field ? 'yes' : 'no');
			return $this->config['pkAutoInc'];
		}

		private function getInsertId($values) {
			// if table has auto-inc PK, return result of db_insert_id()
			if($this->pkIsAutoIncrement()) {
				$insertId = db_insert_id();
				$this->debug[] = "getInsertId(): auto-increment: $insertId";
				return $insertId;
			}

			// else, get pk value from given values if possible
			$pkIndex = array_search(
				$this->config['pkFieldName'], 
				/* purpose of array_filter below is to skip fields set to false */
				array_filter($this->config['csvMap'])
			);

			if($pkIndex === false) {
				$this->debug[] = "getInsertId(): not auto-increment: no PK value in CSV";
				return false; // no pk value given
			}

			$id = trim($values[$pkIndex], "'");
			$this->debug[] = "getInsertId(): not auto-increment: {$id}";
			return $id;
		}

		/*
		 	returns true if clean file ready
		 	returns false on error or work in progress
		 	(in case of error, $this->error would include the error string)
		 */
		private function cleanupCSV() {
			/*
			 For efficient importing of CSV files, we need to prepare a clean
			 copy of the CSV file, where there is no header line, nor any blank
			 lines. And where all lines have the exact number of fields to be
			 inserted.

			 A file, as specified in $this->config['cleanCsvFile'] is created.
			 A batch of lines is read sequentially from original CSV file ($this->config['csvFile']).
			 Filterd files are added to a buffer and appended to cleanCsvFile.
			 Progress of clean up is recorded in $this->config['rawIndex'], which stores
			 the index of the last read position from csvFile.
			 The above is repeated till end of csvFile, or MAX_EXECUTION_TIME reached.

			 If after loop, we're done cleaning up all rows, return true, else return false

			 Related configs:
			 csvFile: the full path to the original csv file
			 cleanCsvFile: determined here the first time, then loaded from stored config later
			 				includes path, APP_DIR . '/' . self::CLEAN_CSV_DIR
			 	>>> index of last line read from csvFile should be stored somewhere:
			 	>>> a flag to indicate if cleanCsvFile is ready for importing
			 */

			// if file ready, with all records, quit
			if($this->config['cleanCsvFileReady']) return true;

			// validate config for cleanup
			if(!$this->validateConfig(1)) return false;

			// first run? create and open clean file for appending
			if(!$this->config['cleanCsvFile']) {
				if(!($fpClean = $this->newCleanCsvFile())) return false; // file can't be created ... see ->error

			// otherwise open clean file to continue from where we left
			} elseif(!($fpClean = @fopen($this->config['cleanCsvFile'], 'a'))) {
				$this->error = 'clean csv dir error';
				return false;
			}

			// open raw csv file for reading raw records and saving them to clean csv
			if(!($fpRaw = @fopen($this->config['csvFile'], 'r'))) {
				$this->error = 'error reading csv file';
				return false;
			}

			// skip already read lines
			if($this->config['rawIndex']) {
				fseek($fpRaw, $this->config['rawIndex']);

			// if first read, skip BOM characters if needed, and update rawIndex
			} elseif(!isset($this->config['rawIndex'])) {
				$this->skipBOM($fpRaw);
				$this->config['rawIndex'] = ftell($fpRaw);
				$this->saveJob();
			}

			$numFields = count($this->config['csvMap']);

			while(
				!feof($fpRaw) &&
				($line = fgetcsv($fpRaw, 0, $this->config['fieldSeparator'], $this->config['fieldWrapper'])) !== false &&
				!$this->timeUp()
			) {
				$this->config['rawIndex'] = ftell($fpRaw);

				// skip blanks
				if(!trim(implode('', $line))) continue;

				// skip header if not already
				if($this->config['csvHasHeaderRow'] && !$this->config['headerRowSkipped']) {
					$this->config['headerRowSkipped'] = true;
					continue;
				}

				// if num columns < num fields, append empty columns
				if(count($line) < $numFields)
					$line = array_pad($line, $numFields, '');
				// if num columns > num fields, trim extra ones at the right
				elseif (count($line) > $numFields)
					$line = array_slice($line, 0, $numFields);

				// write line to clean csv
				fputcsv($fpClean, $line, $this->config['fieldSeparator'], $this->config['fieldWrapper']);
				$this->config['totalRecords']++;

				// we should saveJob() here but that would slow down the import :/
			}

			fclose($fpClean);

			// raw file still being read beyond time allowance?
			if(!feof($fpRaw)) {
				fclose($fpRaw);
				$this->saveJob();
				$this->debug[] = 'Cleanup still in progress';
				return false;
			}

			$this->debug[] = 'Cleanup finished';
			$this->config['cleanCsvFileReady'] = true;
			fclose($fpRaw);

			$this->saveJob();

			return true;
		}

		private function fixUtf8($str, $default = '') {
			$str = from_utf8($str);
			if(empty($str)) return $default;
			$str = str_replace(chr(160), ' ', $str); // convert non-breaking space to space

			return $str;
		}

		/* returns first n lines from a CSV file as a 2D array */
		public function previewCSV($n = 10) {
			if(!$this->jobId) {
				$this->error = 'invalid import job';
				return [];
			}

			$this->debug[] = "previewCSV({$n})";

			// do mild validation of config -- require only fieldSeparator, csvFile
			$this->config['fieldSeparator'] = $this->fixUtf8($this->config['fieldSeparator'], ',');
			$this->config['fieldWrapper'] = $this->fixUtf8($this->config['fieldWrapper'], '"');

			if(!($fpRaw = @fopen($this->config['csvFile'], 'r'))) {
				$this->error = 'error reading csv file';
				return [];
			}

			// skip BOM
			$this->skipBOM($fpRaw);

			// now start reading till n lines are read, or EOF reached
			$lines = [];
			while(
				!feof($fpRaw) &&
				count($lines) <= $n && // the equal here to account for header line if present
				!$this->timeUp()
			) {
				$line = @fgetcsv($fpRaw, 0, $this->config['fieldSeparator'], $this->config['fieldWrapper']);

				// if $line is not an array, it means we've reached EOF, or there is some other error
				// so, to avoid an unnecessary freeze we should break the loop
				if(!is_array($line)) {
					$this->error = 'error reading csv file';
					break;
				}

				// skip blanks
				if(!trim(implode('', $line))) continue;

				$lines[] = $line;
			}

			fclose($fpRaw);

			// if csvHasHeaderRow, remove 1st line
			if($this->config['csvHasHeaderRow']) array_shift($lines);

			// remove last line if we have more than requested # lines
			if(count($lines) > $n) array_pop($lines);

			$this->debug[] = ["previewCSV: lines" => $lines];

			return $lines;
		}

		private function newCleanCsvFile() {
			$cleanDir = APP_DIR . '/' . self::CLEAN_CSV_DIR;
			if(!is_dir($cleanDir)) {
				$this->error = 'clean csv dir error';
				return false;
			}

			$this->config['cleanCsvFile'] = $cleanDir . '/' . uniqid('', true);

			if(!($fp = fopen($this->config['cleanCsvFile'], 'w'))) {
				unset($this->config['cleanCsvFile']);
				$this->error = 'clean csv dir error';
				return false;
			}

			return $fp;
		}

		private function saveJob() {
			$error = '';

			// if job already saved, update it
			if($this->jobId) {
				// don't save job if locked by another thread
				if($this->config['lockerThreadId'] && $this->config['lockerThreadId'] != $this->threadId()) {
					$this->error = 'job in progress by another process';
					return false;
				}

				$this->debug[] = 'Saving changes to job ' . $this->jobId;

				update(
					self::JOBS_TABLE, [
						// fields to update

						// REMEMBER!!
						// rowFilterCallback, rowTransformCallback
						// can't be serialized and MUST be redefined in subsequent runs of this job :/

						'config' => json_encode($this->config),
						'last_update_ts' => time(),
						'total' => $this->config['totalRecords'],
						'done' => $this->config['importedCount'],
					], [
						// where conditions
						'id' => $this->jobId,
						'memberID' => $this->memberInfo['username'] // user can update only their own jobs
					],
					$error
				);

			// new job, so create a jobId then save it
			} else {
				$this->jobId = uniqid('', true);

				$this->debug[] = 'Saving new job ' . $this->jobId;

				insert(
					self::JOBS_TABLE, [
						// fields to update -- see note in update() call above!
						'config' => json_encode($this->config),
						'insert_ts' => time(),
						'id' => $this->jobId,
						'memberID' => $this->memberInfo['username'] // user can update only their own jobs
					],
					$error
				);
			}

			if($error) $this->error = $error;
			return $error ? false : true;
		}

		private function setupDb() {
			$eo = ['silentErrors' => true];

			$tn = self::JOBS_TABLE;
			sql(
				"CREATE TABLE IF NOT EXISTS `{$tn}` (
					`id` VARCHAR(40) NOT NULL,
					`memberID` VARCHAR(100) NOT NULL,
					`config` TEXT,
					`insert_ts` INT,
					`last_update_ts` INT,
					`total` INT DEFAULT 99999999,
					`done` INT DEFAULT 0,
					PRIMARY KEY (`id`),
					INDEX `memberID` (`memberID`)
				) CHARSET " . mysql_charset,
			$eo);

			// TODO
			/*
			$tn = self::JOBS_LOG_TABLE;
			sql(
				"CREATE TABLE IF NOT EXISTS `{$tn}` (
					`id` BIGINT NOT NULL AUTO_INCREMENT,
					`job_id` VARCHAR(40) NOT NULL,
					`ts` INT,
					`details` TEXT,
					PRIMARY KEY (`id`),
					INDEX `job_id` (`job_id`)
				) CHARSET " . mysql_charset,
			$eo);
			*/
		}

		private function setupDirs() {
			$cleanDir = APP_DIR . '/' . self::CLEAN_CSV_DIR;
			if(is_dir($cleanDir)) return;

			@mkdir($cleanDir);
			@touch("{$cleanDir}/index.html");
			@file_put_contents("{$cleanDir}/.htaccess", implode("\n", [
				'<FilesMatch "\.(csv)$">',
				'    Order allow,deny',
				'</FilesMatch>',
			]));
		}

		/* returns jobId if found in db and belongs to current user, falsy value otherwise */
		private function loadJob($jobId) {
			$sjId = makeSafe($jobId);
			$tn = self::JOBS_TABLE;
			$sUsername = makeSafe($this->memberInfo['username']);
			$configJson = sqlValue("SELECT `config` FROM `{$tn}` WHERE `id`='{$sjId}' AND `memberID`='{$sUsername}'");
			if(!$configJson) return false;

			$this->config = json_decode($configJson, true);
			$this->jobId = $jobId;

			$this->debug[] = "Config array loaded for job {$jobId}. Contains " . count($this->config) . ' items.';

			return $jobId;
		}

		private function cleanupOldJobs() {
			// clean up old job entries from db
			$eo = ['silentErrors' => true];
			$tn = self::JOBS_TABLE;
			$oldTS = time() - 86400 * self::OLD_JOBS_AGE;
			sql("DELETE FROM `{$tn}` WHERE `insert_ts` < {$oldTS}", $eo);

			// clean up old csv files
			// 1st, get list of clean CSV files
			$cleanDir = APP_DIR . '/' . self::CLEAN_CSV_DIR;
			$cleanCSVs = array_diff(
				@scandir($cleanDir),
				['.', '..', '.htaccess', 'index.html'] // exclude these from list
			);

			// then, check date of each file and delete if old
			foreach($cleanCSVs as $csv)
				if(@filemtime("$cleanDir/$csv") < $oldTS) @unlink("$cleanDir/$csv");
		}

		/*
			Use to enforce MAX_EXECUTION_TIME, like so:

		    while(thingNotFinished() && !$this->timeUp()) {
				continueThing();
		    }

		    updateThingProgress();
		*/
		private function timeUp() {
			// startTS recorded yet?
			if(!$this->startTS) $this->startTS = microtime(true);

			$nowTS = microtime(true);
			$this->timeElapsed = $nowTS - $this->startTS;

			return $this->timeElapsed >= self::MAX_EXECUTION_TIME;
		}

		// detect type of UTF encoding and skip the appropriate number of characters
		// returns the detected encoding, or false if unable to detect
		private function skipBOM($fp) {
			$this->debug[] = ["skipBOM" => $fp];

			$pos = ftell($fp);
			if($pos === false || $pos > 5) return false; // file pointer error, or already passed BOM section
			
			rewind($fp); // back to start
			$f5b = '';
			do { $f5b .= fgetc($fp); } while(strlen($f5b) < 5 && !feof($fp));

			if(strlen($f5b) < 5) return false;

			// for convenience, we'll define $f2b, $f3b and $f4b
			$f2b = $f5b[0] . $f5b[1];
			$f3b = $f2b . $f5b[2];
			$f4b = $f3b . $f5b[3];

			$res = [-5, false];

			// https://en.wikipedia.org/wiki/Byte_order_mark
			if($f3b == '\xEF\xBB\xBF') $res = [-2, 'UTF-8'];

			elseif($f4b == '\xFF\xFE\x00\x00') $res = [-1, 'UTF-32'];
			elseif($f4b == '\x00\x00\xFE\xFF') $res = [-1, 'UTF-32'];

			elseif($f2b == '\xFE\xFF') $res = [-3, 'UTF-16'];
			elseif($f2b == '\xFF\xFE') $res = [-3, 'UTF-16'];

			elseif($f5b == '\x2B\x2F\x76\x38\x2D') $res = [0, 'UTF-7'];

			elseif(in_array($f4b, [
				'\x2B\x2F\x76\x38',
				'\x2B\x2F\x76\x39',
				'\x2B\x2F\x76\x2B',
				'\x2B\x2F\x76\x2F'
			])) $res = [-1, 'UTF-7'];

			elseif($f3b == '\xF7\x64\x4C') $res = [-2, 'UTF-1'];
			
			elseif($f4b == '\xDD\x73\x66\x73') $res = [-1, 'UTF-EBCDIC'];
			elseif($f4b == '\x84\x31\x95\x33') $res = [-1, 'GB-18030'];

			elseif($f3b == '\x00\xFE\xFF') $res = [-2, 'SCSU'];
			
			elseif($f3b == '\xFB\xEE\x28') $res = [-2, 'BOCU-1'];

			fseek($fp, $res[0], SEEK_CUR);
			return $res[1];
		}

		private function threadId() {
			// https://stackoverflow.com/questions/10404979/get-unique-worker-thread-process-request-id-in-php/22508709#22508709
			return sprintf("%08x", 
				abs(crc32($_SERVER['REMOTE_ADDR'] . 
				thisOr($_SERVER['REQUEST_TIME_FLOAT'], $_SERVER['REQUEST_TIME']) . 
				$_SERVER['REMOTE_PORT']))
			);
		}

		private function lockJob() {
			// return true if current jobId locked successfully
			// false otherwise (if another script is already locking the current jobId)
			// ->error is set on false
			
			/*
			 * job locking aims at preventing multiple threads/processes from handling the same job
			 * simultaneously, causing corruption/duplication/conflicts.
			 * the way we're handling this is by using 2 config variables: lockerThreadId and lockStartTS
			 * lockerThreadId is unique to each thread, and can be obtained from $this->threadId()
			 * 
			 * and to prevent stalled threads from permanently locking a job, lockStartTS is used to determine
			 * the age of a lock and force lock release if age exceeds a threshod of MAX_EXECUTION_TIME + 5
			 */
			$this->debug[] = 'Attempting to lock job ' . $this->jobId;
			
			// check existing lock
			$currentThread = $this->threadId();
			if(!empty($this->config['lockerThreadId']) && !empty($this->config['lockStartTS'])) {
				$this->debug[] = 'A lock already exists!';

				// current thread already locking job?
				if($this->config['lockerThreadId'] == $currentThread) return true;

				// if another thread locking job and is not stalled, fail
				if($this->config['lockStartTS'] > (time() - self::MAX_EXECUTION_TIME - 5)) {
					$this->error = 'job in progress by another process';
					return false;
				}
			}

			// create a lock
			$this->config['lockerThreadId'] = $currentThread;
			$this->config['lockStartTS'] = time();

			$this->debug[] = 'Job successfully locked by current thread ' . $currentThread;

			return $this->saveJob();
		}

		private function unlockJob($return = null) {
			// job locked by another thread?
			if(!empty($this->config['lockerThreadId']) && $this->config['lockerThreadId'] != $this->threadId()) {
				$this->error = 'job in progress by another process';
				return $return;
			}

			// release lock, save and return
			unset($this->config['lockerThreadId']);
			unset($this->config['lockStartTS']);
			$this->saveJob();

			return $return;
		}
	}