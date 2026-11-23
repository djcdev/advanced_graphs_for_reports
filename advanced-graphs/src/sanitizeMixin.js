// sanitizeMixin.js
// Decodes HTML entities in the data dictionary / report fields passed in from PHP (which escapes
// everything) and strips tags from label-like fields. Provides sanitized_data_dictionary and
// sanitized_report_fields_by_repeat_instrument to components whose props include those values.

const STRIP_FIELDS = ['label', 'note', 'description'];

export default {
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
        return value.map((item, i) => this.sanitizeDeep(item, stripFields, path.concat(i)));
      }

      if (value && typeof value === 'object') {
        const out = {};
        for (const [k, v] of Object.entries(value)) {
          out[k] = this.sanitizeDeep(v, stripFields, path.concat(k));
        }
        return out;
      }

      return value; // numbers, null, booleans
    },
  },
  computed: {
    sanitized_data_dictionary() {
      return this.sanitizeDeep(this.data_dictionary, STRIP_FIELDS);
    },
    sanitized_report_fields_by_repeat_instrument() {
      return this.sanitizeDeep(this.report_fields_by_repeat_instrument, STRIP_FIELDS);
    },
  },
};
