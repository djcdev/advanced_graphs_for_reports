<!-- PaletteSelector.vue -->
<template>
    <div class="AG-palette">
      <button
        type="button"
        class="AG-palette-preview"
        :title="module.tt('palette_edit')"
        @click="openEditor">
        <span class="AG-palette-strip" aria-hidden="true">
          <span
            v-for="(color, index) in colors"
            :key="index"
            class="AG-palette-strip-swatch"
            :style="{ backgroundColor: color }"></span>
        </span>
        <span class="AG-palette-preview-label">
          <i class="fa fa-paint-brush" aria-hidden="true"></i>
          {{ module.tt('palette_edit') }}
        </span>
      </button>
      <slot></slot>

      <base-modal
        v-if="showModal"
        :title="module.tt('palette_palette_selector')"
        icon="fa-paint-brush"
        variant="info"
        @close="showModal = false">
        <p class="AG-palette-help">{{ module.tt('palette_dialog_help') }}</p>

        <div class="AG-palette-gradient" :style="{ background: gradient }" aria-hidden="true"></div>

        <ul class="AG-palette-list" @dragover.prevent @drop.prevent="onDrop">
          <li
            v-for="(color, index) in colors"
            :key="index"
            ref="items"
            class="AG-palette-item"
            :class="{
              'AG-palette-dragging': dragIndex === index,
              'AG-palette-drop-before': showDropMarker && dropIndex === index,
              'AG-palette-drop-after': showDropMarker && dropIndex === colors.length && index === colors.length - 1,
            }"
            @dragover.prevent="onDragOver(index, $event)">
            <label class="AG-palette-swatch" :style="{ backgroundColor: color }">
              <input
                type="color"
                class="AG-palette-input"
                :value="toHex(color)"
                :aria-label="module.tt('palette_color_n', index + 1)"
                @input="updateColor(index, $event.target.value)" />
            </label>
            <button
              type="button"
              class="AG-palette-handle"
              draggable="true"
              :title="module.tt('palette_drag_hint')"
              :data-palette-handle="index"
              :aria-label="module.tt('palette_move_color', index + 1)"
              @dragstart="onDragStart(index, $event)"
              @dragend="onDragEnd"
              @keydown.left.prevent="moveByKey(index, -1, $event)"
              @keydown.right.prevent="moveByKey(index, 1, $event)">
              <i class="fa fa-grip-vertical" aria-hidden="true"></i>
              <span class="AG-palette-hex">{{ toHex(color).toUpperCase() }}</span>
            </button>
            <button
              type="button"
              class="AG-palette-remove"
              :disabled="colors.length <= minColors"
              :title="module.tt('palette_remove_color')"
              :aria-label="module.tt('palette_remove_color') + ' ' + (index + 1)"
              @click="removeColor(index)">
              <i class="fa fa-times" aria-hidden="true"></i>
            </button>
          </li>
          <li class="AG-palette-item">
            <button
              type="button"
              class="AG-palette-add"
              :title="module.tt('palette_add_color')"
              :aria-label="module.tt('palette_add_color')"
              @click="addColor">
              <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
            <span class="AG-palette-hex">{{ module.tt('palette_add_color') }}</span>
          </li>
        </ul>
        <p class="visually-hidden" aria-live="polite">{{ liveMessage }}</p>

        <template #footer>
          <button type="button" class="btn btn-outline-secondary me-auto" @click="resetColors">
            <i class="fa fa-undo" aria-hidden="true"></i>
            {{ module.tt('palette_reset') }}
          </button>
          <button type="button" class="btn btn-primary" data-autofocus @click="showModal = false">
            {{ module.tt('modal_done') }}
          </button>
        </template>
      </base-modal>
    </div>
  </template>

  <script>
  import { color as d3color } from 'd3';
  import BaseModal from './BaseModal.vue';

  const DEFAULT_PALETTE = ["#7734EA", "#00A7EA", "#8AE800", "#FAF100", "#FFAA00", "#FF0061"];

  export default {
    name: 'PaletteSelector',
    components: {
      BaseModal
    },
    inject: ['module'],
    props: {
      modelValue: {
        type: Array,
        default: () => [...DEFAULT_PALETTE],
      }
    },
    emits: ['update:modelValue'],
    data() {
      return {
        // Remember the palette the chart started with so "Reset" returns to it
        initialColors: [...this.modelValue],
        colors: this.modelValue,
        showModal: false,
        // The charts blend between colours, which needs at least two
        minColors: 2,
        // Drag-to-reorder: the colour being dragged, and the gap (0..length) it would drop into
        dragIndex: null,
        dropIndex: null,
        liveMessage: '',
      };
    },
    computed: {
      // Hide the marker when dropping would leave the colour where it already is
      showDropMarker() {
        return this.dragIndex !== null
          && this.dropIndex !== null
          && this.dropIndex !== this.dragIndex
          && this.dropIndex !== this.dragIndex + 1;
      },
      gradient() {
        if (!this.colors.length) return 'transparent';
        if (this.colors.length === 1) return this.colors[0];
        return `linear-gradient(to right, ${this.colors.join(', ')})`;
      },
    },
    mounted() {
      this.$emit('update:modelValue', this.colors);
    },
    methods: {
      openEditor() {
        this.showModal = true;
      },
      // The colour picker only accepts #rrggbb, so names like "green" are converted for display
      toHex(value) {
        const parsed = d3color(value);
        return parsed ? parsed.formatHex() : '#000000';
      },
      setColors(newColors) {
        this.colors = newColors;
        this.$emit('update:modelValue', this.colors);
      },
      updateColor(index, value) {
        const next = [...this.colors];
        next[index] = value;
        this.setColors(next);
      },
      removeColor(index) {
        if (this.colors.length <= this.minColors) return;
        this.setColors(this.colors.filter((_, i) => i !== index));
      },
      addColor() {
        // Start the new colour as a copy of the last one so the blend does not jump to black
        const last = this.colors.length ? this.toHex(this.colors[this.colors.length - 1]) : '#000000';
        this.setColors([...this.colors, last]);
      },
      // Move the colour at "from" into the gap before position "gap" (0..length)
      moveColor(from, gap) {
        const next = [...this.colors];
        const [moved] = next.splice(from, 1);
        const target = gap > from ? gap - 1 : gap;
        next.splice(target, 0, moved);
        this.setColors(next);
        return target;
      },
      onDragStart(index, event) {
        this.dragIndex = index;
        this.dropIndex = null;
        event.dataTransfer.effectAllowed = 'move';
        // Firefox only starts a drag when some data is set
        event.dataTransfer.setData('text/plain', String(index));
        const item = event.currentTarget.closest('li');
        if (item && event.dataTransfer.setDragImage) {
          event.dataTransfer.setDragImage(item, item.offsetWidth / 2, 28);
        }
      },
      onDragOver(index, event) {
        if (this.dragIndex === null) return;
        event.dataTransfer.dropEffect = 'move';
        const rect = event.currentTarget.getBoundingClientRect();
        const after = event.clientX > rect.left + rect.width / 2;
        this.dropIndex = after ? index + 1 : index;
      },
      onDrop() {
        if (this.showDropMarker) {
          const target = this.moveColor(this.dragIndex, this.dropIndex);
          this.liveMessage = this.module.tt('palette_moved', target + 1);
        }
        this.onDragEnd();
      },
      onDragEnd() {
        this.dragIndex = null;
        this.dropIndex = null;
      },
      // Left/right arrow keys on a handle move that colour one place
      moveByKey(index, delta, event) {
        const target = index + delta;
        if (target < 0 || target >= this.colors.length) return;
        const list = event.currentTarget.closest('ul');
        this.moveColor(index, delta > 0 ? target + 1 : target);
        this.liveMessage = this.module.tt('palette_moved', target + 1);
        // Keep focus on the colour that moved
        this.$nextTick(() => {
          const handle = list && list.querySelector(`[data-palette-handle="${target}"]`);
          if (handle) handle.focus();
        });
      },
      resetColors() {
        this.setColors([...this.initialColors]);
      },
    },
  };
  </script>

  <style scoped>
  .AG-palette-preview {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
    padding: 0.35rem 0.6rem;
    font-size: 0.875rem;
    color: #212529;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .AG-palette-preview:hover {
    border-color: #86b7fe;
  }

  .AG-palette-preview:focus-visible {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2);
  }

  .AG-palette-strip {
    display: flex;
    flex: 1 1 auto;
    height: 1.25rem;
    border-radius: 0.25rem;
    overflow: hidden;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
  }

  .AG-palette-strip-swatch {
    flex: 1 1 0;
  }

  .AG-palette-preview-label {
    flex: 0 0 auto;
    color: #0d6efd;
    white-space: nowrap;
  }

  .AG-palette-help {
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
  }

  .AG-palette-gradient {
    height: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 0.375rem;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
  }

  .AG-palette-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .AG-palette-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
  }

  .AG-palette-swatch,
  .AG-palette-add {
    position: relative;
    width: 56px;
    height: 56px;
    margin: 0;
    border-radius: 0.5rem;
    cursor: pointer;
  }

  .AG-palette-swatch {
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.15);
    transition: transform 0.1s ease;
  }

  .AG-palette-swatch:hover {
    transform: scale(1.05);
  }

  .AG-palette-swatch:focus-within {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
  }

  /* The real colour input fills the swatch but stays invisible, so clicking the swatch opens the picker */
  .AG-palette-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    border: 0;
    padding: 0;
  }

  .AG-palette-add {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: 1.1rem;
    color: #6c757d;
    background: #f8f9fa;
    border: 2px dashed #ced4da;
  }

  .AG-palette-add:hover,
  .AG-palette-add:focus-visible {
    color: #0d6efd;
    border-color: #86b7fe;
    outline: 0;
  }

  .AG-palette-handle {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.1rem 0.35rem;
    color: #adb5bd;
    background: none;
    border: 1px solid transparent;
    border-radius: 0.25rem;
    cursor: grab;
  }

  .AG-palette-handle:hover,
  .AG-palette-handle:focus-visible {
    color: #495057;
    background: #f1f3f5;
    border-color: #dee2e6;
    outline: 0;
  }

  .AG-palette-handle:active {
    cursor: grabbing;
  }

  .AG-palette-handle .fa {
    font-size: 0.7rem;
  }

  .AG-palette-dragging {
    opacity: 0.4;
  }

  /* Blue bar in the gap where the dragged colour will land */
  .AG-palette-drop-before::before,
  .AG-palette-drop-after::after {
    content: "";
    position: absolute;
    top: 0;
    height: 56px;
    width: 3px;
    background: #0d6efd;
    border-radius: 2px;
  }

  .AG-palette-drop-before::before {
    left: -0.45rem;
  }

  .AG-palette-drop-after::after {
    right: -0.45rem;
  }

  .AG-palette-hex {
    font-size: 0.75rem;
    font-family: SFMono-Regular, Menlo, Consolas, monospace;
    color: #6c757d;
    text-align: center;
  }

  .AG-palette-remove {
    position: absolute;
    top: -6px;
    right: calc(50% - 34px);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    padding: 0;
    font-size: 0.7rem;
    color: #495057;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  }

  .AG-palette-remove:hover:not(:disabled),
  .AG-palette-remove:focus-visible {
    color: #fff;
    background: #dc3545;
    border-color: #dc3545;
    outline: 0;
  }

  .AG-palette-remove:disabled {
    opacity: 0.35;
    cursor: not-allowed;
  }
  </style>
