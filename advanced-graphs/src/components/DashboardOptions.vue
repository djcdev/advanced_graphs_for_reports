<!-- DashboardOptions.vue -->
<template>
    <div id="AG-dashboard-options" class="card AG-dashboard-options">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label for="AG-dash-title" class="form-label">{{ module.tt('dbo_dashboard_title') }}</label>
            <input
              type="text"
              id="AG-dash-title"
              class="form-control"
              name="dash_title"
              v-model="titleString" />
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch AG-public-switch">
              <input
                type="checkbox"
                role="switch"
                class="form-check-input"
                name="is_public"
                id="is_public"
                v-model="isPublicVal"
              />
              <label class="form-check-label" for="is_public">
                <span class="AG-public-label">{{ module.tt('dbo_public') }}</span>
                <span class="AG-public-description">{{ module.tt('dbo_is_public_description') }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  export default {
    name: 'DashboardOptions',
    inject: ['module', 'dashboard'],
    props: {
      title: {
        type: String,
        required: true,
      },
      isPublic: {
        type: Boolean,
        required: true,
      }
    },
    data() {
      return {
        titleString: this.title,
        isPublicVal: this.isPublic,
      };
    },
    watch: {
      titleString(newVal) {
        this.$emit('updateTitle', newVal);
      },
      isPublicVal(newVal) {
        this.$emit('updatePublic', newVal);
      }
    }
  };
  </script>

  <style scoped>
    .AG-dashboard-options {
      margin-bottom: 1.25rem;
    }

    .form-label,
    .AG-public-label {
      font-weight: 600;
    }

    .AG-public-switch {
      margin-bottom: 0.4rem;
    }

    .AG-public-label {
      display: block;
    }

    .AG-public-description {
      display: block;
      font-size: 0.875rem;
      color: #6c757d;
    }
  </style>
