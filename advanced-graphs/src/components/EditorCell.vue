<!-- EditorCell.vue -->
<template>
  <div class="card AG-editor-cell-container">
    <div class="card-header AG-editor-cell-buttons">
      <GraphTypeSelector 
      :currentGraphType="currentGraphType"
      :availableGraphTypes="availableGraphTypes"
      @graphTypeChange="graphTypeChange" />
      <div class="btn-group btn-group-sm" role="group">
        <button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="index === 0"
          :title="module.tt('editor_cell_move_left')"
          :aria-label="module.tt('editor_cell_move_left')"
          @click="$emit('moveCellLeft')">
          <i class="fa fa-arrow-left" aria-hidden="true"></i>
        </button>
        <button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="index === cellCount - 1"
          :title="module.tt('editor_cell_move_right')"
          :aria-label="module.tt('editor_cell_move_right')"
          @click="$emit('moveCellRight')">
          <i class="fa fa-arrow-right" aria-hidden="true"></i>
        </button>
        <button
          type="button"
          class="btn btn-outline-secondary AG-editor-cell-button-add-right"
          :title="module.tt('editor_cell_add_right')"
          :aria-label="module.tt('editor_cell_add_right')"
          @click="$emit('addCellRight')">
          <i class="fa fa-plus" aria-hidden="true"></i>
        </button>
      </div>
      <button
        type="button"
        class="btn btn-sm btn-outline-danger"
        :title="module.tt('editor_cell_delete_cell')"
        :aria-label="module.tt('editor_cell_delete_cell')"
        @click="confirmDelete">
        <i class="fa fa-trash" aria-hidden="true"></i>
      </button>
    </div>
    <div class="card-body AG-editor-cell AG-editor-chart-body">
      <p v-if="!currentGraphForm" class="AG-editor-cell-empty">{{ module.tt('editor_cell_choose_type') }}</p>
      <div class="AG-editor-graph-form-container">
        <component 
          :is="currentGraphForm" 
          :cellData="cellData"
          @updateCellData="updateCellData"
          @isReady="updateFormStatus"/>
      </div>
      <div v-if="showGraph" class="AG-editor-graph-container">
        <component 
          :editorMode="true"
          :key="graphId"
          :is="currentGraph" 
          :parameters="cellData"
          @updateParameters="updateCellData" />
      </div>
    </div>
    <div v-if="currentGraphForm" class="card-footer AG-editor-graph-buttons">
      <button 
        type="button"
        class="btn btn-sm btn-primary"
        :disabled="!formReady"
        @click="previewGraph">
        <i class="fa fa-eye" aria-hidden="true"></i>
        {{module.tt('editor_cell_preview')}}
      </button>
    </div>
  </div>
  <confirmation-modal
    ref="confirmationModal"
  >
  </confirmation-modal>
</template>
  
  <script>
  import GraphTypeSelector from './GraphTypeSelector.vue';
  import GraphTypes from './GraphTypes.js'
  import ConfirmationModal from './ConfirmationModal.vue';
  import { markRaw } from 'vue';

  
  export default {
    name: 'EditorCell',
    components: {
      GraphTypeSelector,
      ConfirmationModal,
    },
    inject: ['module', 'report_fields_by_repeat_instrument'],
    props: {
      cell: {
        type: Object,
        default: null,
      },
      index: {
        type: Number,
        default: 0,
      },
      cellCount: {
        type: Number,
        default: 1,
      },
    },
    emits: ['updateCell', 'deleteCell', 'moveCellLeft', 'moveCellRight', 'addCellRight'],
    data() {
      return {
        currentGraphType: this.cell && this.cell.type ? this.cell.type : null,
        currentGraphForm: this.cell && this.cell.type && GraphTypes[this.cell.type] ? markRaw(GraphTypes[this.cell.type].form) : null,
        currentGraph: this.cell && this.cell.type && GraphTypes[this.cell.type] ? markRaw(GraphTypes[this.cell.type].graph) : null,
        cellData: this.cell ? this.cell.parameters : {},
        cellID: this.cell ? this.cell.id : null,
        // Saved grid position (set on the layout page); carried through edits so it is not lost
        cellLayout: this.cell ? this.cell.layout : undefined,
        graphId: 0,
        showGraph: false,
        formReady: false,
      };
    },
    mounted() {
      if (this.cell && this.cell.type) {
        // Initial setup if cell prop is passed
        this.createGraphFormFromSelectedGraphType(this.cell.type);
      }
    },
    computed: {
      availableGraphTypes() {
        return Object.keys(GraphTypes).filter(graphType => {
          return GraphTypes[graphType].form.methods.isCreatable(this.report_fields_by_repeat_instrument);
        });
      },
    },
    methods: {
      graphTypeChange(graphType) {
        this.updateCellData({});

        this.createGraphFormFromSelectedGraphType(graphType);
      },
      createGraphFormFromSelectedGraphType(graphType) {
        // Implementation for creating the graph form from the selected graph type
        if (!graphType) {
          return;
        }

        this.currentGraphType = graphType;

        this.currentGraphObject = GraphTypes[graphType];

        this.currentGraphForm = markRaw(this.currentGraphObject.form);

        this.currentGraph = markRaw(this.currentGraphObject.graph);

        this.showGraph = false;

        this.$emit('updateCell', {type: graphType, parameters: this.cellData, id: this.cellID, layout: this.cellLayout});
      },
      updateFormStatus(status) {
        this.formReady = status;
        if (!status) {
          this.showGraph = false;
        }
      },
      updateCellData(data) {
        this.cellData = data;
        this.$emit('updateCell', {type: this.currentGraphType, parameters: this.cellData, id: this.cellID, layout: this.cellLayout});
      },
      previewGraph() {
        this.showGraph = true;
        this.graphId++;
      },
      async confirmDelete() {
        const confirmed = await this.$refs.confirmationModal.show({
          title: this.module.tt('editor_cell_delete_cell'),
          message: this.module.tt('editor_cell_delete_cell_confirmation'),
        });

        if (confirmed) {
          this.$emit('deleteCell');
        }
        
      },
    },
  };
  </script>

  <style scoped>
.AG-editor-cell-container {
  flex: 1 1 560px;
  min-width: 0;
  max-width: 100%;
  border-color: #d5dbe1;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.AG-editor-cell-buttons {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #fff;
}

.AG-editor-cell {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.AG-editor-cell-empty {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
}

.AG-editor-graph-form-container:empty {
  display: none;
}

.AG-editor-graph-container {
  overflow-x: auto;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.AG-editor-graph-buttons {
  display: flex;
  justify-content: flex-end;
  background: #fff;
}
</style>
