<?php
namespace VIHA\AdvancedGraphsInteractive;

use REDCap as REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class AdvancedGraphsInteractive extends \ExternalModules\AbstractExternalModule
{
	public $dashboard_table_name = "advanced_graphs_dashboards_v2";

	public $module_js_path;
	public $module_css_path;

	function redcap_module_system_enable($version) {
		// Do stuff, e.g. create DB table
		$dashboard_table_name = $this->dashboard_table_name;
		try {
			$result = $this->query("CREATE TABLE if not exists $dashboard_table_name (
				dash_id INT AUTO_INCREMENT PRIMARY KEY,
				project_id INT, INDEX(project_id),
				report_id INT,
				live_filters json,
				title TEXT,
				body LONGTEXT,
				dash_order INT,
				is_public tinyint DEFAULT 0 NOT NULL,
				ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)"
			, []);
			$this->log("Created new $dashboard_table_name table (if one did not already exist)");
		} catch (\Throwable $e) {
			$this->log("Failed $this->PREFIX to create table $dashboard_table_name with error:\n".$e->getMessage());
		}
	}

	// Called when each link in config.json is rendered (and when its page is loaded directly).
	// Return null to hide the link, or return the (possibly modified) $link.
	// The "New dashboard" link is only shown on a report page, with that report's ID added.
    function redcap_module_link_check_display($project_id, $link)
    {
		$current_page_is_this_page = strpos($_SERVER["QUERY_STRING"],"page=edit_dash") > -1;
		$current_page_is_export_report = strpos($_SERVER["PHP_SELF"],"/DataExport/") > -1 && strpos($_SERVER["QUERY_STRING"],"&report_id=") > -1 && strpos($_SERVER["QUERY_STRING"],"&addedit") <= -1;

		$report_id = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT);

		if ($link['id'] == "edit_dash") {
			if ($report_id !== null && $report_id !== false) {
				$link['url'] .= "&dash_id=0&report_id=$report_id";
			}
			if ($current_page_is_export_report)
				return($link);

			if ($current_page_is_this_page)
				$link['url'] = "#";
			else
				return(null);
		}

		return($link);
    }

	function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id){
        if($action === 'newDashboard'){
            return json_encode($this->newDash($project_id, $payload));
        }
		else if($action === 'saveDashboard'){
			// Use the project ID REDCap verified for this request, never one supplied by the client
			$newDash = $this->saveDash($project_id, $payload);
			if ($newDash) {
				return json_encode($newDash);
			}

			return "error man";
		}
		else if ($action === 'deleteDashboard') {
			$dash_id = filter_var($payload["dash_id"] ?? null, FILTER_VALIDATE_INT);
			if ($dash_id === false || $dash_id === null) return json_encode(false);
			return json_encode($this->deleteDash($project_id, $dash_id));
		}
		else if ($action === 'copyDashboard') {
			$dash_id = filter_var($payload["dash_id"] ?? null, FILTER_VALIDATE_INT);
			if ($dash_id === false || $dash_id === null) return json_encode(false);
			return json_encode($this->copyDash($project_id, $dash_id));
		}
		else if ($action === 'saveDashboardLayout') {
			$dash_id = filter_var($payload["dash_id"] ?? null, FILTER_VALIDATE_INT);
			if ($dash_id === false || $dash_id === null) return json_encode(false);
			return json_encode($this->saveDashLayout($project_id, $dash_id, $payload["layout"] ?? null));
		}
		else if ($action === 'getReportName') {
			// Use the project ID REDCap verified for this request, never one supplied by the client
			$report_id = filter_var($payload["report_id"] ?? null, FILTER_VALIDATE_INT);
			if ($report_id === false || $report_id === null) return json_encode(false);
			return $this->getReportName($project_id, $report_id);
		}
		else {
			return false;
		}
    }
	
	function loadJS($js_file, $folder = "js", $outputToPage=true) {
		if($this->module_js_path == null)
		{
			$this->module_js_path = str_replace("\\","/",$this->getModulePath())."js/";
		}
		// Create script tag
		$output = "<script type=\"text/javascript\" src=\"" . $this->getURL($js_file,  $this->module_js_path). "\"></script>\n";
		if ($outputToPage) {
			print $output;
		} else {
			return $output;
		}
	}

	// Output the link/style tag for a given CSS file
	function loadCSS($css_file, $folder = "css", $outputToPage=true)
	{
		if($this->module_css_path == null)
		{
			$this->module_css_path = str_replace("\\","/",$this->getModulePath())."css/";
		}
		// Create link tag
		$output = "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen,print\" href=\"" . $this->getURL($css_file,  $this->module_css_path) . "\">\n";
		if ($outputToPage) {
			print $output;
		} else {
			return $output;
		}
	}
	

	// returns the data dictionary for a project
	public function getDataDictionary($project_id) {
		return REDCap::getDataDictionary($project_id, 'array');
	}

	// returns the name of the report
	public function getReportName($project_id, $report_id) {
		$sql = "select title from redcap_reports where project_id = ? and report_id = ?";
		$q = $this->query($sql, [$project_id, $report_id]);
		$row = $q->fetch_assoc();
		return $row['title'];
	}

	// returns the report fields
	public function getReportFields($project_id, $report_id) {
		$fields = array();
		$sql = "select * from redcap_reports_fields where report_id = ?
				order by field_order";
		$q = $this->query($sql, [$report_id]);
		while ($row =  $q->fetch_assoc()) {
			$fields[] = $row['field_name'];
		}
		return $fields;
	}
	
	// returns the report fields by repeating instument
	public function getReportFieldsByRepeatInstrument($project_id, $report_id) {
		$Proj = $this->getProject($project_id);
		$data_dictionary = REDCap::getDataDictionary($project_id, "array");
		// getRepeatingForms crashes for longitudinal as at some point in the call stack the project 
		// must have only a single event
		if(!REDCap::isLongitudinal())
			$repeat_instruments = $Proj->getRepeatingForms();
		else
		{
			$repeat_instruments = array();
			$events = REDCap::getEventNames();
			$is_longitudinal = true;
		}

		$report_fields =$this->getReportFields($project_id, $report_id);

		$repeat_dictionary = array();
		$non_repeats = array();

		// get the repeating instruments
		foreach ($repeat_instruments as $instrument) {
			$repeat_dictionary[$instrument] = array("form_name" => $instrument, "label" => REDCap::getInstrumentNames($instrument, $project_id), "fields" => array());
		}

		$repeat_dictionary[""] = array("form_name" => "", "label" => $this->tt('non_repeat_instrument_label'), "fields" => array());

		// If the project is longitudinal we loop over the event id's, get the valid fields for the event, and 
		// if the field is in the report we append the event and add the 
		if($is_longitudinal)
		{
			$event_ids = array_keys($events);
			foreach($event_ids as $event_id)
			{
				$valid_fields = REDCap::getValidFieldsByEvents($project_id, $event_id);
				// Go through the valid fields for this event.
				foreach($valid_fields as $valid_field)
				{
					// If a valid field is in the reports fields array add it.
					if(in_array($valid_field, $report_fields))
					{
						$field = $data_dictionary[$valid_field];
						if(!$field)
							continue;

						$field['field_label'] .= ' - ' . REDCap::getEventNames(false, false, $event_id);
						$repeat_dictionary[""]['fields'][] = $field;
					}
				}
			}			
		}
		else
		{ 
			// get the fields for each repeating instrument
			foreach ($report_fields as $field_name) {
				$field = $data_dictionary[$field_name];

				if (!$field)
					continue;

				if (in_array($field['form_name'], $repeat_instruments))
					$repeat_dictionary[$field['form_name']]['fields'][] = $field;
				else
					$repeat_dictionary[""]['fields'][] = $field;
			}
		}

		if (!count($repeat_dictionary[""]['fields']))
			unset($repeat_dictionary[""]);

		// return an array of repeating instruments and non repeating fields
		return $repeat_dictionary;
	}


	function getReport($report_id) {
		// Retrieve report from redcap
		$report = REDCap::getReport($report_id, 'array');

		// Flatten the report
		$flattened_report = array();

		// For each record in the report
		foreach ($report as $record) {
			// For each event in the record whose key isn't 'repeat_instances'
			foreach ($record as $event_id => $event) {
				if ($event_id != 'repeat_instances') {
					// Add the event id, an empty repeat instrument, and an empty repeat instance to the event
					$event['redcap_repeat_instrument'] = '';
					$event['redcap_repeat_instance'] = '';
					$event['redcap_event_name'] = $event_id;

					// Add the event to the flattened report
					$flattened_report[] = $event;

					// Continue to the next event
					continue;
				}

				// For the event whose key is 'repeat_instances'
				foreach ($event as $event_id => $repeat_instruments) {
					// For each repeat instrument in the event
					foreach ($repeat_instruments as $repeat_instrument => $repeat_instances) {
						// For each repeat instance in the repeat instrument
						foreach ($repeat_instances as $repeat_instance => $repeat_instance_data) {
							// Add the event id, repeat instrument, and repeat instance to the added repeat instance to the flattened report
							$repeat_instance_data['redcap_repeat_instrument'] = $repeat_instrument;
							$repeat_instance_data['redcap_repeat_instance'] = $repeat_instance;
							$repeat_instance_data['redcap_event_name'] = $event_id;

							// Add the repeat instance to the flattened report
							$flattened_report[] = $repeat_instance_data;
						}
					}
				}
			}
		}

		// Return the flattened report
		return $flattened_report;
	}


	// A function that creates a new dashboard and returns the corresponding dashboard object
	public function newDash($pid, $report_id) {
		$dashboard_table_name = $this->dashboard_table_name;
		// Get next dash_order number
		$q = $this->query("select max(dash_order) from $dashboard_table_name where project_id = ?", [$pid]);
		$new_dash_order = $q->fetch_assoc();
		$new_dash_order = $new_dash_order['max(dash_order)'];
		$new_dash_order = ($new_dash_order == '') ? 1 : $new_dash_order+1;

		// Create new dashboard
		$errno = 0;
		$this->query("START TRANSACTION", []);
		// Insert
		$sql = "insert into $dashboard_table_name 
				(project_id, report_id, title, body, dash_order, is_public)
				values (?, ?, ?, ?, ?, ?)";
		$params = [$pid, $report_id, $this->tt("new_dashboard"), "[]", $new_dash_order, 0];

		$query = $this->query($sql, $params);
		if (!$query) $errno += 1;

		// Get the new dashboard id
		$result = $this->query("SELECT LAST_INSERT_ID() as last_id", []);
		
		if (!$result) $errno += 1;
		
		$row = $result->fetch_assoc();
		$last_id = $row['last_id'];

		// Commit
		$this->query("COMMIT", []);

		if ($errno > 0) {
			$this->query("ROLLBACK", []);
			return [];
		}

		// Return the new dashboard
		return $this->getDashboards($pid, $last_id)[0];
	}

	// A function that takes a dashboard and saves it to the database
	// $pid must be the trusted project ID from the request context, not a value from $dashboard.
	public function saveDash($pid, $dashboard) {
		$dashboard_table_name = $this->dashboard_table_name;
		// Count errors
		$errors = 0;

		// Get dashboard id
		$dash_id = filter_var($dashboard['dash_id'] ?? null, FILTER_VALIDATE_INT);
		if ($dash_id === false || $dash_id === null) return false;

		// Make sure the dashboard belongs to this project
		$dash = $this->getDashboards($pid, $dash_id);
		if (empty($dash)) return false;

		// Report title
		$title = decode_filter_tags($dashboard['title']);
		$body = json_encode($dashboard['body']);

		// Store exactly 1 or 0 whatever form the flag arrives in (true, "1", false, "0", ...)
		$is_public = filter_var($dashboard['is_public'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		// Update dashboard
		$sql = "update $dashboard_table_name set 
				title = ?, 
				body = ?, 
				is_public = ?
				where project_id = ? and dash_id = ?";
		$params = [$title, $body, $is_public, $pid, $dash_id];

		$query = $this->query($sql, $params);
		if (!$query) $errors += 1;

		// Return the new dashboard
		return $this->getDashboards($pid, $dash_id);
	}

	// Saves chart positions from the layout page. $layout is a list of
	// {row, col, x, y, w, h}; each position is stored on its cell as cell['layout'].
	// Only the positions are taken from the client; the charts themselves come from the database.
	public function saveDashLayout($pid, $dash_id, $layout)
	{
		$dashboard_table_name = $this->dashboard_table_name;
		if (!is_array($layout)) return false;

		$q = $this->query("select body from $dashboard_table_name where project_id = ? and dash_id = ?", [$pid, $dash_id]);
		$dash = $q->fetch_assoc();
		if (!$dash) return false;

		// Decode as objects (not arrays) so empty {} values survive the round trip unchanged
		$body = json_decode($dash['body']);
		if (!is_array($body)) return false;

		$grid_columns = 12;
		foreach ($layout as $item) {
			if (!is_array($item)) return false;
			$values = [];
			foreach (['row', 'col', 'x', 'y', 'w', 'h'] as $key) {
				$value = filter_var($item[$key] ?? null, FILTER_VALIDATE_INT);
				if ($value === false || $value === null) return false;
				$values[$key] = $value;
			}
			$row = $values['row'];
			$col = $values['col'];
			if (!isset($body[$row]) || !is_array($body[$row]) || !isset($body[$row][$col]) || !is_object($body[$row][$col])) return false;

			$w = max(1, min($grid_columns, $values['w']));
			$body[$row][$col]->layout = (object)[
				'x' => max(0, min($grid_columns - $w, $values['x'])),
				'y' => max(0, min(10000, $values['y'])),
				'w' => $w,
				'h' => max(1, min(1000, $values['h'])),
			];
		}

		$query = $this->query("update $dashboard_table_name set body = ? where project_id = ? and dash_id = ?",
			[json_encode($body), $pid, $dash_id]);
		return $query ? true : false;
	}

	// Return all dashboards (unless one is specified explicitly) as an array of their attributes
	public function getDashboards($project_id, $dash_id=null)
	{

		$dashboard_table_name = $this->dashboard_table_name;
		// Get all dashboards
		$sql = "select * from $dashboard_table_name where project_id = ?";
		$params = [$project_id];
		if ($dash_id != null) {
			$sql .= " and dash_id = ?";
			$params[] = $dash_id;
		}
		$sql .= " order by dash_order";
		$q = $this->query($sql, $params);
		$dashboards = [];
		while ($row = $q->fetch_assoc()) {
			// We need to decode the json in the body as we end up echo'ing out the 
			// dashboard, which requires that we escape the dashboard, which messes
			// up the json. 
			$row['body'] = json_decode($row['body'],true);
			$dashboards[] = $row;
		}
		
		return $dashboards;
	}
	
	// Delete a report
	public function deleteDash($pid, $dash_id)
	{
		$dashboard_table_name = $this->dashboard_table_name;
		// Delete report
		$sql = "delete from $dashboard_table_name where project_id = ? and dash_id = ?";
		$params = array($pid, $dash_id);
		$q = $this->query($sql, $params);
		if (!$q) return false;
		// Fix ordering of reports (if needed) now that this report has been removed
		$this->checkDashOrder($pid);

		// Return success
		return true;
	}

	// Copy a dashboard, placing the copy directly after the original.
	// Returns the new dashboard object, or false on failure.
	public function copyDash($pid, $dash_id)
	{
		$dashboard_table_name = $this->dashboard_table_name;

		// Read the raw row (getDashboards() decodes body, which we don't want here)
		$q = $this->query("select * from $dashboard_table_name where project_id = ? and dash_id = ?", [$pid, $dash_id]);
		$dash = $q->fetch_assoc();
		if (!$dash) return false;

		$new_dash_order = ((int)$dash['dash_order']) + 1;
		$new_title = $dash['title'] . " " . $this->tt("dashboard_copy_suffix");

		$errors = 0;
		$new_dash_id = null;
		$this->query("START TRANSACTION", []);
		try {
			// Shift later dashboards down to make room for the copy
			$sql = "update $dashboard_table_name set dash_order = dash_order + 1
					where project_id = ? and dash_order >= ?";
			if (!$this->query($sql, [$pid, $new_dash_order])) $errors++;

			// Insert the copy (always private)
			$sql = "insert into $dashboard_table_name
					(project_id, report_id, live_filters, title, body, dash_order, is_public)
					values (?, ?, ?, ?, ?, ?, 0)";
			$params = [$pid, $dash['report_id'], $dash['live_filters'], $new_title, $dash['body'], $new_dash_order];
			if (!$this->query($sql, $params)) $errors++;

			$result = $this->query("SELECT LAST_INSERT_ID() as last_id", []);
			if (!$result) $errors++;
			else $new_dash_id = $result->fetch_assoc()['last_id'];
		} catch (\Throwable $e) {
			$this->log("Failed to copy dashboard $dash_id: " . $e->getMessage());
			$errors++;
		}

		if ($errors > 0) {
			$this->query("ROLLBACK", []);
			return false;
		}
		$this->query("COMMIT", []);

		// Just in case, make sure that all dashboard orders are correct
		$this->checkDashOrder($pid);

		return $this->getDashboards($pid, $new_dash_id)[0];
	}

	// Checks for errors in the dashboard order of all dashboards (in case their numbering gets off)
	public function checkDashOrder($pid)
	{
		$dashboard_table_name = $this->dashboard_table_name;	
		// Do a quick compare of the field_order by using Arithmetic Series (not 100% reliable, but highly reliable and quick)
		// and make sure it begins with 1 and ends with field order equal to the total field count.
		$sql = "select sum(dash_order) as actual, round(count(1)*(count(1)+1)/2) as ideal,
				min(dash_order) as min, max(dash_order) as max, count(1) as dash_count
				from $dashboard_table_name where project_id = " . $pid;
		$q = $this->query($sql, []);
		$row = $q->fetch_assoc();
		db_free_result($q);
		if ( ($row['actual'] != $row['ideal']) || ($row['min'] != '1') || ($row['max'] != $row['dash_count']) )
		{
			return $this->fixDashOrder($pid);
		}
	}

	// Fixes the dashboard order of all dashboards (if somehow their numbering gets off)
	public function fixDashOrder($pid)
	{
		$dashboard_table_name = $this->dashboard_table_name;
		// Set all dash_orders to null
		$sql = "select @n := 0";
		$this->query($sql, []);
		// Reset field_order of all fields, beginning with "1"
		$sql = "update $dashboard_table_name
				set dash_order = @n := @n + 1 where project_id = ".$pid."
				order by dash_order, dash_id";
		if (!$this->query($sql, []))
		{
			// If unique key prevented easy fix, then do manually via looping
			$sql = "select dash_id from $dashboard_table_name
					where project_id = ".$pid."
					order by dash_order, dash_id";
			$q = $this->query($sql, []);
			$dash_order = 1;
			$dash_orders = array();
			while ($row = $q->fetch_assoc()) {
				$dash_orders[$row['dash_id']] = $dash_order++;
			}
			// Reset all orders to null
			$sql = "update $dashboard_table_name set dash_order = null where project_id = ".PROJECT_ID;
			$this->query($sql, []);
			foreach ($dash_orders as $dash_id=>$dash_order) {
				// Set order of each individually
				$sql = "update $dashboard_table_name
						set dash_order = $dash_order 
						where dash_id = $dash_id";
				$this->query($sql, []);
			}
		}
		// Return boolean on success
		return true;
	}

	public static function escape($value){
		$type = gettype($value);

		/**
		 * The unnecessary casting on these first few types exists solely to inform psalm and avoid warnings.
		 */
		if($type === 'boolean'){
			return (bool) $value;
		}
		else if($type === 'integer'){
			return (int) $value;
		}
		else if($type === 'double'){
			return (float) $value;
		}
		else if($type === 'array'){
			$newValue = [];
			foreach($value as $key=>$subValue){
				$key = static::escape($key);
				$subValue = static::escape($subValue);
				$newValue[$key] = $subValue;
			}

			return $newValue;
		}
		else if($type === 'NULL'){
			return null;
		}
		else{
			/**
			* Handle strings, resources, and custom objects (via the __toString() method. 
			* Apart from escaping, this produces that same behavior as if the $value was echoed or appended via the "." operator.
			*/
			return htmlspecialchars(''.$value, ENT_QUOTES);
		}
	}
}
