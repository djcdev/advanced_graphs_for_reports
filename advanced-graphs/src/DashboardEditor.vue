<!-- DashboardEditor.vue -->
  <template>
    <div>
      <h1>Dashboard Editor</h1>
      <DashboardOptions 
      :title="title" 
      :isPublic="isPublic" 
      @updateTitle="updateTitle($event)"
      @updatePublic="updatePublic($event)"/>
      <editor-table
        :rows="body"
        @moveRowUp="moveRowUp($event)"
        @moveRowDown="moveRowDown($event)"
        @updateRow="updateDashboardRow($event)"
        @removeRow="removeDashboardRow($event)"
        @addRow="addDashboardRow"
      ></editor-table>
      <div class="AG-editor-final-buttons">
        <button @click="saveDashboard" class="btn btn-primary">
          {{ dashboard ? module.tt('dbe_save') : module.tt('dbe_create') }}
        </button>
        <button @click="cancel()" class="btn btn-secondary">{{ module.tt('dbe_cancel') }}</button>
      </div>
    </div>
    <saved-modal
        v-if="savedModal"
        :name="title"
        :list_link="savedModal.list_link"
        :dash_link="savedModal.dash_link"
        @close="savedModal=null">
    </saved-modal>
    <confirmation-modal ref="confirmationModal"></confirmation-modal>
  </template>
  
  <script>
  import DashboardOptions from './components/DashboardOptions.vue';
  import EditorTable from './components/EditorTable.vue';
  import SavedModal from './components/SavedModal.vue';
  import ConfirmationModal from './components/ConfirmationModal.vue';
  import { reactive } from 'vue';
  import { getUuid } from './utils.js';

  export default {
    name: "DashboardEditor",
    components: {
        DashboardOptions,
        EditorTable,
        SavedModal,
        ConfirmationModal,
    },
    props: ['module', 'dashboard', 'report', 'data_dictionary', 'report_fields_by_repeat_instrument'],
    provide()  {
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
      sanitized_data_dictionary() {
        const stripFields = ['label', 'note', 'description'];
        return this.sanitizeDeep(this.data_dictionary, stripFields);
      },

      sanitized_report_fields_by_repeat_instrument() {
        const stripFields = ['label', 'note', 'description'];
        return this.sanitizeDeep(this.report_fields_by_repeat_instrument, stripFields);
      }
    },
    data() {
        return {
            title: this.dashboard.title || this.module.tt('dbe_new_dashboard'),
            isPublic: this.dashboard.is_public || false,
            body: reactive( 
              this.dashboard.body ? 
              JSON.parse(JSON.stringify(this.dashboard.body)).map(row => row.map(cell => ({ ...cell, id: getUuid() }))): 
              [],
            ),
            localDashboard: this.dashboard,
            savedModal: null,
        }
    },
    methods: {
      cancel() {
        if(history.length > 1)
          history.back();
        else
          location.reload();
      },
      updateTitle(new_title) {
        this.title = new_title;
      },
      updatePublic(new_public) {
        this.isPublic = new_public;
      },
        moveRowUp(index) {
            if (index > 0) {
                const row = this.body[index];
                this.body.splice(index, 1);
                this.body.splice(index - 1, 0, row);
            }
        },
        moveRowDown(index) {
            if (index < this.body.length - 1) {
                const row = this.body[index];
                this.body.splice(index, 1);
                this.body.splice(index + 1, 0, row);
            }
        },
        updateDashboardRow({ index, row }) {
            this.body.splice(index, 1, row);
            console.log('updateBody', this.body);
        },
        async removeDashboardRow(index) {
            const confirm = await this.$refs.confirmationModal.show(
                {
                  title: this.module.tt('dbe_confirm_delete_row'),
                  message: this.module.tt('dbe_confirm_delete_row_message'),
                }
            );

            if (!confirm) {
                return;
            }

            this.body.splice(index, 1);
        },
        addDashboardRow() {
            this.body.push([]);
        },
        async saveDashboard() {
          this.localDashboard = this.localDashboard && this.localDashboard.body ? this.localDashboard : await this.newDashboard();
          this.localDashboard.body = this.body;
          this.localDashboard.title = this.title;
          this.localDashboard.is_public = this.isPublic;
          this.module.ajax('saveDashboard', this.localDashboard).then(function (result) {
            console.log('saveDashboard', result);
            var new_dash = JSON.parse(result)[0];
            this.savedModal = {
              name: new_dash.title,
              list_link: this.module.getUrl('advanced_graphs.php'),
              dash_link: this.module.getUrl('view_dash.php') + '&report_id=' + new_dash.report_id 
          + '&dash_id=' + new_dash.dash_id,
            };
          }.bind(this)).catch(function (error) {
            console.log(error);
          });
      },
      async newDashboard() {
        try {
            var result = await this.module.ajax('newDashboard', this.module.getUrlParameter('report_id'));
            console.log('new_dashboard', result);
            return JSON.parse(result);
        } catch (error) {
            console.log(error);
        }
     },
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
    watch: {
      body: {
        handler: function (newBody) {
          this.body = newBody;
        },
        deep: true,
      },
    }
 };
  </script>