<template>
  <div class="AG-viewer-title">
    <h1> {{ dashboard.title }}</h1>
    <a v-if="editLayoutUrl" :href="editLayoutUrl" class="btn btn-outline-primary btn-sm">
      <i class="fa fa-th-large" aria-hidden="true"></i> {{ module.tt('layout_edit_button') }}
    </a>
  </div>
  <div class="AG-viewer-dashboard">
    <dashboard-grid :rows="rows" :editable="false" />
  </div>
</template>

<script>
  import DashboardGrid from "./components/DashboardGrid.vue";
  import sanitizeMixin from "./sanitizeMixin.js";

  export default {
    name: "DashboardViewer",
    components: { DashboardGrid },
    mixins: [sanitizeMixin],
    // can_edit_layout is only true on view_dash.php; the public viewer leaves it false
    props: ['module', 'dashboard', 'report', 'data_dictionary', 'report_fields_by_repeat_instrument', 'can_edit_layout'],
    provide() {
      return {
        module: this.module,
        dashboard: this.dashboard,
        report: this.report,

        // Provide sanitized computed values
        data_dictionary: this.sanitized_data_dictionary,
        report_fields_by_repeat_instrument: this.sanitized_report_fields_by_repeat_instrument
      };
    },
    computed: {
      editLayoutUrl() {
        if (!this.can_edit_layout || !this.dashboard || !this.dashboard.dash_id) return null;
        return this.module.getUrl('edit_dash_layout.php') + '&dash_id=' + encodeURIComponent(this.dashboard.dash_id);
      },

      rows() {
        return this.dashboard && this.dashboard.body ? JSON.parse(JSON.stringify(this.dashboard.body)) : [];
      },
    },
  };
</script>

<style scoped>
.AG-viewer-dashboard {
  overflow: auto;
  margin: 0 2rem 1rem 2rem;
}

.AG-viewer-title {
  margin-left: 2rem;
  margin-right: 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
</style>
