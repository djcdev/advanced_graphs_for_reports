<?php
namespace VIHA\AdvancedGraphsInteractive;

	use ExternalModules\AbstractExternalModule;
	use ExternalModules\ExternalModules;

	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<style>
	.rcproject-navbar {
		display: none !important;
	}
</style>
<?php
	// Get the project ID
	$project_id = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
	if ($project_id === null || $project_id === false) {
		echo "<h1 style='color: red;'>Unable to obtain project ID</h1>";
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}

	// Get the data dictionary
	$dashboards = $module->getDashboards($project_id);


	$module->loadJS('advanced-graphs/dist/AdvancedGraphs.umd.js');
	$module->loadCSS('advanced-graphs/dist/AdvancedGraphs.css');

	$js_module = $module->initializeJavascriptModuleObject();
	$module->tt_transferToJavascriptModuleObject();
?>

<div id="advanced_graphs">

</div>

<script>
	    // in an anonymous function to avoid polluting the global namespace
		$(document).ready(function() {
			var module = <?=ExternalModules::getJavascriptModuleObjectName($module)?>;
			var dashboards = <?php echo json_encode($module::escape($dashboards)); ?>;

			// Initialize the module from AdvancedGraphsModule.js
			var app = AdvancedGraphs.createDashboardListApp(module, dashboards);

			app.mount('#advanced_graphs');
		});
</script>

<?php
// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>