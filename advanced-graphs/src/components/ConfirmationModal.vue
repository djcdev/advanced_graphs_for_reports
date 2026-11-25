<!-- ConfirmationModal.vue: call show({ title, message }) and await true (confirmed) or false (cancelled) -->
<template>
    <base-modal
        :show="showModal"
        :title="title"
        icon="fa-exclamation-triangle"
        variant="danger"
        size="sm"
        @close="cancel">
        <p class="AG-confirm-message">{{ message }}</p>
        <template #footer>
            <button type="button" class="btn btn-outline-secondary" data-autofocus @click="cancel">
                {{ module.tt('confirmation_modal_cancel') }}
            </button>
            <button type="button" class="btn btn-danger" @click="confirm">
                {{ module.tt('confirmation_modal_confirm') }}
            </button>
        </template>
    </base-modal>
</template>

<script>
import BaseModal from './BaseModal.vue';

export default {
    name: 'ConfirmationModal',
    components: { BaseModal },
    inject: ['module'],
    data() {
        return {
            showModal: false,
            title: '',
            message: '',
            resolvePromise: null,
        };
    },
    methods: {
        show(opts = {}) {
            this.showModal = true;
            this.title = opts.title || '';
            this.message = opts.message || '';

            return new Promise((resolve) => {
                this.resolvePromise = resolve;
            });
        },

        confirm() {
            this.close(true);
        },

        cancel() {
            this.close(false);
        },

        close(result) {
            if (!this.showModal) return;
            this.showModal = false;
            if (this.resolvePromise) this.resolvePromise(result);
            this.resolvePromise = null;
        },
    }

};
</script>

<style scoped>
.AG-confirm-message {
    margin: 0;
}
</style>
