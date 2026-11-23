<?php
namespace VIHA\AdvancedGraphsInteractive;

// Layout editor: drag and resize a dashboard's charts (opened from the "Edit layout" button on view_dash.php)

include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<style>
	.rcproject-navbar {
		display: none !important;
	}
</style>
<?php
$project_id = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
$dash_id = filter_input(INPUT_GET, 'dash_id', FILTER_VALIDATE_INT);
if ($project_id === null || $project_id === false || $dash_id === null || $dash_id === false || $dash_id <= 0) {
    echo "<h1 style='color: red;'>Invalid dashboard or project ID</h1>";
    include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
    exit;
}

$dashboards = $module->getDashboards($project_id, $dash_id);
if (empty($dashboards)) {
    echo "<h1 style='color: red;'>Dashboard not found</h1>";
    include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
    exit;
}
$dashboard = $dashboards[0];
$report_id = $dashboard['report_id'];

// The charts render from the report data, exactly as on view_dash.php
$report = $module->getReport($report_id);
$data_dictionary = $module->getDataDictionary($project_id);
$report_fields_by_reapeat_instrument = $module->getReportFieldsByRepeatInstrument($project_id, $report_id);

$js_module = $module->initializeJavascriptModuleObject();
$module->tt_transferToJavascriptModuleObject();

$module->loadJS('advanced-graphs/dist/AdvancedGraphs.umd.js');
$module->loadCSS('advanced-graphs/dist/AdvancedGraphs.css');
?>

<div id="advanced_graphs"></div>

<script>
    $(document).ready(function() {
        var module = <?=$module->getJavascriptModuleObjectName()?>;
        var dashboard = <?php echo json_encode($module::escape($dashboard)); ?>;
        var data_dictionary = <?php echo json_encode($module::escape($data_dictionary)); ?>;
        var report = <?php echo json_encode($module::escape($report)); ?>;
        var report_fields_by_reapeat_instrument = <?php echo json_encode($module::escape($report_fields_by_reapeat_instrument)); ?>;

        var app = AdvancedGraphs.createDashboardLayoutEditorApp(module, dashboard, report, data_dictionary, report_fields_by_reapeat_instrument);
        app.mount('#advanced_graphs');
    });
</script>

<?php
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>
