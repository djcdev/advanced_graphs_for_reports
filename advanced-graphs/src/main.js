// main.js
import { createApp } from 'vue';
import DashboardEditor from './DashboardEditor.vue';
import DashboardList from './DashboardList.vue';
import DashboardViewer from './DashboardViewer.vue';
import DashboardLayoutEditor from './DashboardLayoutEditor.vue';
// Bootstrap CSS is not bundled: REDCap already loads its own Bootstrap 5, and a second copy
// loaded after it overrides REDCap's page styles (left menu font size, top navbar).
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';


export function createDashboardEditorApp (module, dashboard, report, data_dictionary, report_fields_by_repeat_instrument) {
    const app = createApp(DashboardEditor, {
        module: module,
        dashboard: dashboard,
        report: report,
        data_dictionary: data_dictionary,
        report_fields_by_repeat_instrument: report_fields_by_repeat_instrument,
    });

    app.component('font-awesome-icon', FontAwesomeIcon);
    
    return app;
}

export function createDashboardListApp(module, dashboards) {
    const app = createApp(DashboardList, {
        module: module,
        dashboards: dashboards,
    });

    app.component('font-awesome-icon', FontAwesomeIcon);
    
    return app;
}

// can_edit_layout: show the "Edit layout" button (view_dash.php only; omitted on the public page)
export function createDashboardViewerApp (module, dashboard, report, data_dictionary, report_fields_by_repeat_instrument, can_edit_layout = false) {
    const app = createApp(DashboardViewer, {
        module: module,
        dashboard: dashboard,
        report: report,
        data_dictionary: data_dictionary,
        report_fields_by_repeat_instrument: report_fields_by_repeat_instrument,
        can_edit_layout: can_edit_layout,
    });

    app.component('font-awesome-icon', FontAwesomeIcon);
    
    return app;
}

export function createDashboardLayoutEditorApp (module, dashboard, report, data_dictionary, report_fields_by_repeat_instrument) {
    const app = createApp(DashboardLayoutEditor, {
        module: module,
        dashboard: dashboard,
        report: report,
        data_dictionary: data_dictionary,
        report_fields_by_repeat_instrument: report_fields_by_repeat_instrument,
    });

    app.component('font-awesome-icon', FontAwesomeIcon);

    return app;
}
