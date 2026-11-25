<!-- EditorTable.vue -->
<template>
    <div class="AG-editor-table" ref="editorTable">
      <div v-if="!rows || !rows.length" class="AG-editor-empty">
        <i class="fa fa-th-large AG-editor-empty-icon" aria-hidden="true"></i>
        <p class="AG-editor-empty-text">{{ module.tt('editor_table_empty') }}</p>
      </div>
      <editor-row
        v-for="(row, index) in rows"
        :key="index"
        :row="row"
        :index="index"
        :rowCount="rows.length"
        @moveRowUp="moveRowUp(index)"
        @moveRowDown="moveRowDown(index)"
        @updateRow="updateRow(index, $event)"
        @removeRow="deleteRow(index)"
      ></editor-row>
      <button type="button" class="btn btn-outline-primary AG-editor-add-row" @click="addRow">
        <i class="fa fa-plus" aria-hidden="true"></i>
        {{ module.tt('editor_table_add_row') }}
      </button>
    </div>
  </template>

  <script>
  import EditorRow from './EditorRow.vue';

  export default {
    name: 'EditorTable',
    inject: ['module'],
    components: {
      EditorRow,
    },
    props: {
      rows: {
        type: Array,
        default: null,
      },
    },
    methods: {
        moveRowUp(index) {
          this.$emit('moveRowUp', index);
        },
        moveRowDown(index) {
          this.$emit('moveRowDown', index);
        },
        async updateRow(index, row) {
          this.$emit('updateRow', { index, row });
          await this.$nextTick();
        },
        deleteRow(index) {
          this.$emit('removeRow', index);
        },
        addRow() {
            this.$emit('addRow');
        },
    },
  };
  </script>

  <style scoped>
    .AG-editor-empty {
      text-align: center;
      padding: 2.5rem 1rem;
      margin-bottom: 1rem;
      border: 2px dashed #ced4da;
      border-radius: 0.5rem;
      color: #6c757d;
      background: #f8f9fa;
    }

    .AG-editor-empty-icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .AG-editor-empty-text {
      margin: 0;
    }

    .AG-editor-add-row {
      width: 100%;
      border-style: dashed;
    }
  </style>
