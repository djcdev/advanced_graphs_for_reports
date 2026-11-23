// main.js
import { createApp } from 'vue';
import DashboardEditor from './DashboardEditor.vue';
import DashboardList from './DashboardList.vue';
import DashboardViewer from './DashboardViewer.vue';
import DashboardLayoutEditor from './DashboardLayoutEditor.vue';
import 'bootstrap/dist/css/bootstrap.min.css';
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
