<template>
  <div class="AG-viewer-title">
    <h1> {{ dashboard.title }}</h1>
  </div>
  <div class="AG-viewer-dashboard">
    <div 
      v-for="(row, index) in rows" :key="index" class="AG-viewer-row">
      <div
        v-for="(graph, index) in row"
        :key="index"
      >
        <div class="AG-viewer-col">
          <component
            :is="GraphTypes[graph.type].graph"
            :parameters="graph.parameters"
            :editorMode="false"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import GraphTypes from "./components/GraphTypes.js";

  export default {
    name: "DashboardViewer",
    props: ['module', 'dashboard', 'report', 'data_dictionary', 'report_fields_by_repeat_instrument'],
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
    mounted() {
      console.log(this.rows);
    },
    methods: {
      // Decode HTML entities such as &lt;, &amp;, etc.
      decodeHTML(s) {
        if (typeof s !== 'string') return s;
        const t = document.createElement('textarea');
        t.innerHTML = s;
        return t.value;
      },

      // Strip HTML tags using browser parser
      stripHTML(s) {
        if (typeof s !== 'string') return s;
        const div = document.createElement('div');
        div.innerHTML = s;
        return div.textContent || div.innerText || '';
      },

      // Recursively walk object/array
      sanitizeDeep(value, stripFields, path = []) {
        if (typeof value === 'string') {
          const key = path[path.length - 1];

          let decoded = this.decodeHTML(value);

          // Only strip for allowed fields
          if (stripFields.includes(key)) {
            decoded = this.stripHTML(decoded);
          }

          return decoded;
        }

        if (Array.isArray(value)) {
          return value.map((item, i) =>
            this.sanitizeDeep(item, stripFields, path.concat(i))
          );
        }

        if (value && typeof value === 'object') {
          const out = {};
          for (const [k, v] of Object.entries(value)) {
            out[k] = this.sanitizeDeep(v, stripFields, path.concat(k));
          }
          return out;
        }

        return value; // numbers, null, booleans
      }
    },
    computed: {
      sanitized_data_dictionary() {
        const stripFields = ['label', 'note', 'description'];
        return this.sanitizeDeep(this.data_dictionary, stripFields);
      },

      sanitized_report_fields_by_repeat_instrument() {
        const stripFields = ['label', 'note', 'description'];
        return this.sanitizeDeep(this.report_fields_by_repeat_instrument, stripFields);
      },

      rows() {
        return this.dashboard && this.dashboard.body ? JSON.parse(JSON.stringify(this.dashboard.body)) : [];
      },
    },
    data() {
      return {
        GraphTypes,
      };
    }
  };
</script>

<style scoped>
.AG-viewer-dashboard{
  overflow: auto;
}

.AG-viewer-row {
  margin-bottom: 1rem;
  display: flex;
  flex-direction: row;
}

.AG-viewer-col {
  min-width: 400px;
  margin-right: 1rem;
  margin-left: 2rem;
}

.AG-viewer-title {
  margin-left: 2rem;
}
</style>