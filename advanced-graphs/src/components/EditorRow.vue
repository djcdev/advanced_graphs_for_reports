<!-- EditorRow.vue -->
<template>
    <div class="card AG-editor-row">
      <div class="card-header AG-editor-row-header">
        <span class="AG-editor-row-title">{{ module.tt('editor_row_label') }} {{ index + 1 }}</span>
        <div class="AG-editor-row-actions">
          <button type="button" class="btn btn-sm btn-primary" @click="addCell">
            <i class="fa fa-plus" aria-hidden="true"></i>
            {{ module.tt('editor_row_add_chart') }}
          </button>
          <div class="btn-group btn-group-sm" role="group">
            <button
              type="button"
              class="btn btn-outline-secondary AG-editor-row-button-up"
              :disabled="index === 0"
              :title="module.tt('editor_row_move_up')"
              :aria-label="module.tt('editor_row_move_up')"
              @click="$emit('moveRowUp')">
              <i class="fa fa-arrow-up" aria-hidden="true"></i>
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary AG-editor-row-button-down"
              :disabled="index === rowCount - 1"
              :title="module.tt('editor_row_move_down')"
              :aria-label="module.tt('editor_row_move_down')"
              @click="$emit('moveRowDown')">
              <i class="fa fa-arrow-down" aria-hidden="true"></i>
            </button>
          </div>
          <button
            type="button"
            class="btn btn-sm btn-outline-danger AG-editor-row-button-delete"
            :title="module.tt('editor_row_delete')"
            :aria-label="module.tt('editor_row_delete')"
            @click="$emit('removeRow')">
            <i class="fa fa-trash" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <p v-if="!rowCells.length" class="AG-editor-row-empty">{{ module.tt('editor_row_empty') }}</p>
        <div v-else class="AG-editor-row-cells">
          <editor-cell
            v-for="(cell, cellIndex) in row"
            :cell="cell"
            :key="cell.id"
            :index="cellIndex"
            :cellCount="rowCells.length"
            @updateCell="updateCell(cellIndex, $event)"
            @moveCellLeft="moveCellLeft(cellIndex)"
            @moveCellRight="moveCellRight(cellIndex)"
            @deleteCell="deleteCell(cellIndex)"
            @addCellRight="addCellRight(cellIndex)"
          ></editor-cell>
        </div>
      </div>
    </div>
  </template>

  <script>
  import EditorCell from './EditorCell.vue';
  import { getUuid } from '@/utils';

  export default {
    name: 'EditorRow',
    inject: ['module'],
    components: {
      EditorCell,
    },
    props: {
      row: {
        type: Array,
        default: null,
      },
      index: {
        type: Number,
        default: 0,
      },
      rowCount: {
        type: Number,
        default: 1,
      },
    },
    emits: ['updateRow', 'moveRowUp', 'moveRowDown', 'removeRow'],
    computed: {
      rowCells() {
        return this.row || [];
      },
    },
    methods: {
      addCell() {
        this.$emit('updateRow', [{ id: getUuid() }, ...this.rowCells]);
      },
        updateCell(index, cell) {
            this.$emit('updateRow', [
            ...this.rowCells.slice(0, index),
            cell,
            ...this.rowCells.slice(index + 1),
            ]);
        },
        async moveCellLeft(index) {
            if (index === 0) {
            return;
            }
            this.$emit('updateRow', [
            ...this.rowCells.slice(0, index - 1),
            this.rowCells[index],
            this.rowCells[index - 1],
            ...this.rowCells.slice(index + 1),
            ]);
        },
        async moveCellRight(index) {
            if (index === this.rowCells.length - 1) {
            return;
            }
            this.$emit('updateRow', [
            ...this.rowCells.slice(0, index),
            this.rowCells[index + 1],
            this.rowCells[index],
            ...this.rowCells.slice(index + 2),
            ]);
        },
        deleteCell(index) {
            this.$emit('updateRow', [
            ...this.rowCells.slice(0, index),
            ...this.rowCells.slice(index + 1),
            ]);
        },
        addCellRight(index) {
            this.$emit('updateRow', [
            ...this.rowCells.slice(0, index + 1),
            { id: getUuid() },
            ...this.rowCells.slice(index + 1),
            ]);
        },
    },
  };
  </script>

  <style scoped>
    .AG-editor-row {
      margin-bottom: 1rem;
      border-color: #d5dbe1;
    }

    .AG-editor-row-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
      background: #f1f4f7;
    }

    .AG-editor-row-title {
      font-weight: 600;
    }

    .AG-editor-row-actions {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .AG-editor-row-cells {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: flex-start;
    }

    .AG-editor-row-empty {
      margin: 0;
      color: #6c757d;
      font-size: 0.9rem;
    }
  </style>
