<!-- DashboardLayoutEditor.vue -->
<!-- Lets the user drag and resize a dashboard's charts, then saves the positions. -->
<template>
  <div class="AG-layout-editor">
    <div class="AG-layout-editor-header">
      <h1>{{ dashboard.title }} <small class="text-muted">&ndash; {{ module.tt('layout_page_title') }}</small></h1>
      <p class="text-muted mb-2">{{ module.tt('layout_instructions') }}</p>
      <div class="AG-layout-editor-buttons">
        <button class="btn btn-primary" :disabled="saving" @click="saveLayout">
          <span v-if="saving" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
          {{ module.tt('layout_save') }}
        </button>
        <button class="btn btn-outline-secondary" :disabled="saving" @click="resetLayout">
          {{ module.tt('layout_reset') }}
        </button>
        <a class="btn btn-secondary" :href="viewUrl">{{ module.tt('layout_back') }}</a>
        <span v-if="status === 'saved'" class="text-success" role="status">
          <i class="fa fa-check" aria-hidden="true"></i> {{ module.tt('layout_saved') }}
        </span>
        <span v-if="status === 'error'" class="text-danger" role="alert">
          {{ module.tt('layout_save_error') }}
        </span>
      </div>
    </div>
    <div class="AG-layout-editor-grid">
      <dashboard-grid ref="grid" :rows="rows" :editable="true" />
    </div>
  </div>
</template>

<script>
import DashboardGrid from './components/DashboardGrid.vue';
import sanitizeMixin from './sanitizeMixin.js';

export default {
  name: 'DashboardLayoutEditor',
  components: { DashboardGrid },
  mixins: [sanitizeMixin],
  props: ['module', 'dashboard', 'report', 'data_dictionary', 'report_fields_by_repeat_instrument'],
  provide() {
    return {
      module: this.module,
      dashboard: this.dashboard,
      report: this.report,
      data_dictionary: this.sanitized_data_dictionary,
      report_fields_by_repeat_instrument: this.sanitized_report_fields_by_repeat_instrument,
    };
  },
  data() {
    return {
      rows: this.dashboard && this.dashboard.body ? JSON.parse(JSON.stringify(this.dashboard.body)) : [],
      saving: false,
      status: null, // null | 'saved' | 'error'
    };
  },
  computed: {
    viewUrl() {
      return this.module.getUrl('view_dash.php')
        + '&report_id=' + encodeURIComponent(this.dashboard.report_id)
        + '&dash_id=' + encodeURIComponent(this.dashboard.dash_id);
    },
  },
  methods: {
    resetLayout() {
      this.status = null;
      this.$refs.grid.resetLayout();
    },
    async saveLayout() {
      this.saving = true;
      this.status = null;
      try {
        const result = await this.module.ajax('saveDashboardLayout', {
          dash_id: this.dashboard.dash_id,
          layout: this.$refs.grid.getLayout(),
        });
        this.status = JSON.parse(result) === true ? 'saved' : 'error';
      } catch (error) {
        console.error(error);
        this.status = 'error';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.AG-layout-editor {
  margin: 0 2rem 1rem 2rem;
}

.AG-layout-editor-buttons {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.AG-layout-editor-grid {
  overflow: auto;
  /* Light grid backdrop so the drop area is visible */
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  min-height: 400px;
}
</style>
