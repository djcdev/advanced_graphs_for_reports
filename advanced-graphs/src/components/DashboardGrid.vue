<!-- DashboardGrid.vue -->
<!-- Renders a dashboard's charts in a grid. In editable mode the charts can be dragged (by their handle) and resized. -->
<template>
  <div class="AG-dashboard-grid" :class="{ 'AG-dashboard-grid-editable': editable }">
    <grid-layout
      v-model:layout="layout"
      :col-num="GRID_COLUMNS"
      :row-height="GRID_ROW_HEIGHT"
      :margin="[10, 10]"
      :is-draggable="editable"
      :is-resizable="editable"
      :vertical-compact="false"
      :use-css-transforms="true"
    >
      <grid-item
        v-for="item in layout"
        :key="item.i"
        :i="item.i"
        :x="item.x"
        :y="item.y"
        :w="item.w"
        :h="item.h"
        :min-w="MIN_W"
        :min-h="MIN_H"
        drag-allow-from=".AG-grid-drag-handle"
        class="AG-grid-item"
      >
        <div v-if="editable" class="AG-grid-drag-handle" :title="module.tt('layout_drag_hint')">
          <i class="fa fa-arrows-alt" aria-hidden="true"></i>
          <span>{{ graphsByKey[item.i].parameters && graphsByKey[item.i].parameters.title || '' }}</span>
        </div>
        <div class="AG-grid-item-content">
          <component
            :is="GraphTypes[graphsByKey[item.i].type].graph"
            :parameters="graphsByKey[item.i].parameters"
            :editorMode="false"
          />
        </div>
      </grid-item>
    </grid-layout>
  </div>
</template>

<script>
import { GridLayout, GridItem } from 'grid-layout-plus';
import GraphTypes from './GraphTypes.js';
import { buildGridLayout, GRID_COLUMNS, GRID_ROW_HEIGHT, MIN_W, MIN_H } from './gridLayoutUtils.js';

// Only cells with a known graph type are placed on the grid (empty editor cells are skipped)
const isRenderable = (cell) => !!(cell && cell.type && GraphTypes[cell.type]);

export default {
  name: 'DashboardGrid',
  inject: ['module'],
  components: { GridLayout, GridItem },
  props: {
    // The dashboard body: an array of rows, each an array of cells ({type, parameters, layout?})
    rows: { type: Array, default: () => [] },
    editable: { type: Boolean, default: false },
  },
  data() {
    return {
      GraphTypes,
      GRID_COLUMNS,
      GRID_ROW_HEIGHT,
      MIN_W,
      MIN_H,
      layout: buildGridLayout(this.rows, isRenderable),
    };
  },
  computed: {
    // Map each layout key ("row-col") back to its cell
    graphsByKey() {
      const map = {};
      this.rows.forEach((row, r) => row.forEach((cell, c) => { map[`${r}-${c}`] = cell; }));
      return map;
    },
  },
  methods: {
    // Current positions, as [{row, col, x, y, w, h}] (called by the layout editor through a ref)
    getLayout() {
      return this.layout.map(({ i, x, y, w, h }) => {
        const [row, col] = i.split('-').map(Number);
        return { row, col, x, y, w, h };
      });
    },
    // Discard saved positions and lay the charts out again from the editor's row order
    resetLayout() {
      this.layout = buildGridLayout(this.rows, isRenderable, true);
    },
  },
};
</script>

<style>
.AG-dashboard-grid {
  /* Below this width the grid scrolls horizontally rather than squeezing charts */
  min-width: 900px;
}

.AG-grid-item {
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  overflow: hidden;
}

.AG-dashboard-grid-editable .AG-grid-item {
  border: 1px dashed #6c757d;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.AG-grid-drag-handle {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.5rem;
  background: #e9ecef;
  border-bottom: 1px solid #dee2e6;
  cursor: move;
  user-select: none;
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.AG-grid-item-content {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  padding: 0.5rem;
}

/* Let the charts shrink to fit their container instead of forcing 640x480. */
.AG-dashboard-grid .AG-grid-item .AG-graph-container {
  height: 100%;
  justify-content: flex-start;
}

.AG-dashboard-grid .AG-grid-item .AG-graphContainer {
  min-width: 0;
  min-height: 0;
  width: 100%;
  flex: 1 1 auto;
}

/* Scale the chart (up or down) to fill its box, keeping its aspect ratio via the SVG viewBox.
   Only the chart's own SVG is targeted, not legend swatches (Plot wraps chart + legend in a <figure>). */
.AG-dashboard-grid .AG-grid-item .AG-graphContainer > svg,
.AG-dashboard-grid .AG-grid-item .AG-graphContainer > figure > svg {
  width: 100% !important;
  height: 100% !important;
  max-width: none !important;
  display: block;
}

.AG-dashboard-grid .AG-grid-item .AG-graphContainer > figure {
  display: flex;
  flex-direction: column;
  height: 100%;
  margin: 0;
}

.AG-dashboard-grid .AG-grid-item .AG-graphContainer > figure > svg {
  flex: 1 1 auto;
  min-height: 0;
}
</style>
