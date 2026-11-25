<!-- BaseModal.vue: shared dialog shell used by the confirmation, help and saved dialogs -->
<template>
  <Teleport to="body">
    <transition name="AG-modal-fade" appear>
      <div v-if="show" class="AG-modal-backdrop" @mousedown.self="$emit('close')">
        <div
          ref="dialog"
          class="AG-modal-dialog"
          :class="'AG-modal-' + size"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="title ? titleId : null"
          tabindex="-1"
          @keydown.esc.stop="$emit('close')">
          <div v-if="title || icon" class="AG-modal-header">
            <span v-if="icon" class="AG-modal-icon" :class="'AG-modal-icon-' + variant">
              <i :class="'fa ' + icon" aria-hidden="true"></i>
            </span>
            <h2 :id="titleId" class="AG-modal-title">{{ title }}</h2>
            <button
              type="button"
              class="btn-close AG-modal-close"
              :aria-label="closeLabel"
              :title="closeLabel"
              @click="$emit('close')"></button>
          </div>
          <div class="AG-modal-body">
            <slot></slot>
          </div>
          <div v-if="$slots.footer" class="AG-modal-footer">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script>
import { getUuid } from '../utils.js';

export default {
  name: 'BaseModal',
  inject: {
    module: { default: null },
  },
  props: {
    show: { type: Boolean, default: true },
    title: { type: String, default: '' },
    // Font Awesome icon class shown beside the title, e.g. 'fa-exclamation-triangle'
    icon: { type: String, default: '' },
    // 'danger', 'success' or 'info': colours the icon
    variant: { type: String, default: 'info' },
    // 'sm' (confirmations), 'md' (default) or 'lg'
    size: { type: String, default: 'md' },
  },
  emits: ['close'],
  data() {
    return {
      titleId: 'AG-modal-title-' + getUuid(),
      previousFocus: null,
    };
  },
  computed: {
    closeLabel() {
      return this.module ? this.module.tt('modal_close') : 'Close';
    },
  },
  watch: {
    show: {
      immediate: true,
      handler(isShown) {
        if (isShown) {
          this.previousFocus = document.activeElement;
          this.$nextTick(() => this.focusDialog());
        } else {
          this.restoreFocus();
        }
      },
    },
  },
  beforeUnmount() {
    this.restoreFocus();
  },
  methods: {
    // Focus the button marked autofocus (if any), otherwise the dialog itself, so Esc works straight away
    focusDialog() {
      const dialog = this.$refs.dialog;
      if (!dialog) return;
      const preferred = dialog.querySelector('[data-autofocus]');
      (preferred || dialog).focus();
    },
    restoreFocus() {
      if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
        this.previousFocus.focus();
      }
      this.previousFocus = null;
    },
  },
};
</script>

<style scoped>
.AG-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 10050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background-color: rgba(33, 37, 41, 0.55);
}

.AG-modal-dialog {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-height: calc(100vh - 2rem);
  background: #fff;
  border-radius: 0.6rem;
  box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.25);
  outline: none;
  overflow: hidden;
}

.AG-modal-sm { max-width: 440px; }
.AG-modal-md { max-width: 560px; }
.AG-modal-lg { max-width: 760px; }

.AG-modal-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem 0.5rem;
}

.AG-modal-title {
  flex: 1 1 auto;
  margin: 0;
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1.3;
  color: #212529;
}

.AG-modal-close {
  flex: 0 0 auto;
  align-self: flex-start;
}

.AG-modal-icon {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  font-size: 1rem;
}

.AG-modal-icon-danger { background: #f8d7da; color: #b02a37; }
.AG-modal-icon-success { background: #d1e7dd; color: #146c43; }
.AG-modal-icon-info { background: #cfe2ff; color: #0a58ca; }

.AG-modal-body {
  padding: 0.5rem 1.25rem 1.25rem;
  overflow-y: auto;
  color: #495057;
}

.AG-modal-footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.AG-modal-fade-enter-active,
.AG-modal-fade-leave-active {
  transition: opacity 0.15s ease;
}

.AG-modal-fade-enter-active .AG-modal-dialog,
.AG-modal-fade-leave-active .AG-modal-dialog {
  transition: transform 0.15s ease;
}

.AG-modal-fade-enter-from,
.AG-modal-fade-leave-to {
  opacity: 0;
}

.AG-modal-fade-enter-from .AG-modal-dialog,
.AG-modal-fade-leave-to .AG-modal-dialog {
  transform: translateY(8px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .AG-modal-fade-enter-active,
  .AG-modal-fade-leave-active,
  .AG-modal-fade-enter-active .AG-modal-dialog,
  .AG-modal-fade-leave-active .AG-modal-dialog {
    transition: none;
  }
}
</style>
