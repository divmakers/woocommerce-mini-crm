document.addEventListener('DOMContentLoaded', function () {
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                pattern_name: '',
                creator_id: woocrCall.user_id, // Automatically set to the current user's ID
                pattern_text: '',
                patterns: [],
                editingPatternId: null,
                nonce: woocrCall.nonce, // Store the nonce in the Vue instance
            };
        },
        created() {
            this.fetchPatterns();
        },
        computed: {
            pattern_text_count() {
                return this.pattern_text.length;
            }
        },
        methods: {
            fetchPatterns() {
                fetch(woocrCall.ajax_url + '?action=fetch_patterns', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ nonce: this.nonce }),
                })
                .then(response => response.json())
                .then(data => {
                    this.patterns = data;
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
            submitForm() {
                // Convert the form data to URL-encoded format
                const formData = new URLSearchParams({
                    action: 'save_pattern',
                    nonce: this.nonce,
                    pattern_id: this.editingPatternId,
                    pattern_name: this.pattern_name,
                    creator_id: this.creator_id,
                    pattern_text: this.pattern_text,
                }).toString();
            
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.fetchPatterns();
                        this.resetForm();
                    } else {
                        console.error('Error saving pattern:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
            editPattern(pattern) {
                this.pattern_name = pattern.pattern_name;
                this.pattern_text = pattern.pattern_text;
                this.editingPatternId = pattern.pattern_id;
            },
            deletePattern(pattern_id) {
                // Prepare the request body
                const formData = new URLSearchParams({
                    action: 'delete_pattern',
                    pattern_id: pattern_id,
                    nonce: this.nonce,
                }).toString();
            
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.fetchPatterns(); // Refresh the patterns list
                    } else {
                        console.error('Error deleting pattern:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
            resetForm() {
                this.pattern_name = '';
                this.pattern_text = '';
                this.editingPatternId = null;
                this.creator_id = woocrCall.user_id; // Reset to the current user's ID
            }
        },
    }).mount('#app_woocrCall_smsPattern');
});
