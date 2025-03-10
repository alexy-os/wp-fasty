(function($) {
    // Global variables
    let templateEditor, dataEditor;
    let templateData = {};
    let postData = {};
    
    // Initialize Monaco Editor
    function initMonacoEditor() {
        // Use our loader instead of direct require
        loadMonacoEditor(function(monaco) {
            // Setup Handlebars syntax highlighting
            monaco.languages.register({ id: 'handlebars' });
            monaco.languages.setMonarchTokensProvider('handlebars', {
                tokenizer: {
                    root: [
                        [/\{\{/, { token: 'delimiter.handlebars', next: '@handlebarsInSimpleState' }],
                        [/\{\{/, { token: 'delimiter.handlebars', next: '@handlebarsInEmbeddedState' }]
                    ],
                    handlebarsInSimpleState: [
                        [/\}\}/, { token: 'delimiter.handlebars', next: '@root' }],
                        [/\w+/, 'identifier.handlebars']
                    ],
                    handlebarsInEmbeddedState: [
                        [/\}\}/, { token: 'delimiter.handlebars', next: '@root' }],
                        [/[#\/]?\w+/, 'identifier.handlebars'],
                        [/[\.\w]+/, 'identifier.handlebars']
                    ]
                }
            });
            
            // Create the template editor
            templateEditor = monaco.editor.create(document.getElementById('template-editor'), {
                value: $('#wp_fasty_template').val(),
                language: 'handlebars',
                theme: 'vs',
                automaticLayout: true,
                minimap: { enabled: false }
            });
            
            templateEditor.onDidChangeModelContent(function() {
                // Update the hidden field when the content changes
                $('#wp_fasty_template').val(templateEditor.getValue());
            });
            
            // Create the data editor
            dataEditor = monaco.editor.create(document.getElementById('data-editor'), {
                value: $('#wp_fasty_template_data').val(),
                language: 'json',
                theme: 'vs',
                automaticLayout: true,
                minimap: { enabled: false }
            });
            
            dataEditor.onDidChangeModelContent(function() {
                // Update the hidden field when the content changes
                $('#wp_fasty_template_data').val(dataEditor.getValue());
            });
            
            // Load the post data
            loadPostData();
        });
    }
    
    // Load the post data
    function loadPostData() {
        $.ajax({
            url: wpFasty.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_post_data',
                post_id: wpFasty.postId,
                nonce: wpFasty.nonce
            },
            success: function(response) {
                if (response.success) {
                    postData = response.data;
                    updateFieldsTab();
                }
            }
        });
    }
    
    // Update the fields tab
    function updateFieldsTab() {
        // Analyze the template
        analyzeTemplate();
    }
    
    // Analyze the template to find variables
    function analyzeTemplate() {
        if (!templateEditor) return;
        
        const template = templateEditor.getValue();
        
        $.ajax({
            url: wpFasty.ajaxUrl,
            type: 'POST',
            data: {
                action: 'analyze_template',
                post_id: wpFasty.postId,
                template: template,
                nonce: wpFasty.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderFieldsTab(response.data);
                }
            }
        });
    }
    
    // Display the fields tab
    function renderFieldsTab(data) {
        const $fieldsContainer = $('#template-fields');
        $fieldsContainer.empty();
        
        // Existing fields
        if (Object.keys(data.existingFields).length > 0) {
            const $existingFields = $('<div class="fields-section"><h3>Existing Fields</h3><div class="fields-list"></div></div>');
            const $fieldsList = $existingFields.find('.fields-list');
            
            $.each(data.existingFields, function(key, field) {
                const $field = $(`
                    <div class="field-item">
                        <div class="field-name">${field.name}</div>
                        <div class="field-value">${typeof field.value === 'string' ? field.value.substring(0, 50) : JSON.stringify(field.value).substring(0, 50)}</div>
                        <div class="field-template">{{page.${field.name}}}</div>
                    </div>
                `);
                $fieldsList.append($field);
            });
            
            $fieldsContainer.append($existingFields);
        }
        
        // Missing fields
        if (data.missingFields.length > 0) {
            const $missingFields = $('<div class="fields-section missing-fields"><h3>Missing Fields</h3><div class="fields-list"></div></div>');
            const $fieldsList = $missingFields.find('.fields-list');
            
            $.each(data.missingFields, function(index, field) {
                const $field = $(`
                    <div class="field-item">
                        <div class="field-name">${field}</div>
                        <div class="field-template">{{page.${field}}}</div>
                        <div class="field-actions">
                            <button type="button" class="button create-field" data-field="${field}">Создать</button>
                        </div>
                    </div>
                `);
                $fieldsList.append($field);
            });
            
            $fieldsContainer.append($missingFields);
        }
        
        // If there are no missing fields
        if (data.missingFields.length === 0) {
            $('#create-missing-fields').prop('disabled', true);
        } else {
            $('#create-missing-fields').prop('disabled', false);
        }
    }
    
    // Create missing fields
    function createMissingFields() {
        const $missingFields = $('.missing-fields .field-item');
        const fields = [];
        
        $missingFields.each(function() {
            const field = $(this).find('.field-name').text();
            fields.push(field);
        });
        
        if (fields.length === 0) {
            return;
        }
        
        $.ajax({
            url: wpFasty.ajaxUrl,
            type: 'POST',
            data: {
                action: 'create_missing_fields',
                post_id: wpFasty.postId,
                fields: JSON.stringify(fields),
                nonce: wpFasty.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload the data
                    loadPostData();
                    
                    // Notification
                    alert('Created fields: ' + response.data.created.length);
                }
            }
        });
    }
    
    // Render the template
    function renderTemplate() {
        try {
            if (!templateEditor || !dataEditor) return;
            
            const template = templateEditor.getValue();
            const customData = JSON.parse(dataEditor.getValue());
            
            // Compile the template
            const compiledTemplate = Handlebars.compile(template);
            
            // Merge the data
            const data = $.extend(true, {}, postData, customData);
            templateData = data;
            
            // Render
            const html = compiledTemplate(data);
            $('#wp_fasty_preview').html(html);
        } catch (e) {
            $('#wp_fasty_preview').html(`<div class="error">Ошибка: ${e.message}</div>`);
        }
    }
    
    // Save HTML
    function saveHtml() {
        const html = $('#wp_fasty_preview').html();
        if (!dataEditor) return;
        
        const templateDataJson = dataEditor.getValue();
        
        $.ajax({
            url: wpFasty.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_template_json',
                post_id: wpFasty.postId,
                html: html,
                template_data: templateDataJson,
                nonce: wpFasty.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('HTML saved successfully');
                }
            }
        });
    }
    
    // Initialize Handlebars helpers
    function initHandlebarsHelpers() {
        Handlebars.registerHelper('formatDate', function(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString();
        });
        
        Handlebars.registerHelper('excerpt', function(text, options) {
            if (!text) return '';
            const length = options.hash.length || 150;
            
            // Safe HTML tag removal
            const div = document.createElement('div');
            div.innerHTML = text;
            const plainText = div.textContent || div.innerText || '';
            
            return plainText.length > length ? plainText.substring(0, length) + '...' : plainText;
        });
        
        Handlebars.registerHelper('if_eq', function(a, b, options) {
            return a === b ? options.fn(this) : options.inverse(this);
        });
        
        Handlebars.registerHelper('if_not_eq', function(a, b, options) {
            return a !== b ? options.fn(this) : options.inverse(this);
        });
    }
    
    // Function to save a draft in localStorage
    function saveDraft() {
        if (!templateEditor || !dataEditor) return;
        
        const template = templateEditor.getValue();
        const data = dataEditor.getValue();
        const postId = wpFasty.postId;
        
        const draft = {
            template: template,
            data: data,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem(`wp_fasty_draft_${postId}`, JSON.stringify(draft));
        
        // Show notification
        showNotification('Draft saved locally', 'success');
    }
    
    // Function to load a draft from localStorage
    function loadDraft() {
        const postId = wpFasty.postId;
        const draftJson = localStorage.getItem(`wp_fasty_draft_${postId}`);
        
        if (!draftJson) {
            showNotification('Draft not found', 'error');
            return;
        }
        
        try {
            const draft = JSON.parse(draftJson);
            
            if (templateEditor) {
                templateEditor.setValue(draft.template);
            }
            
            if (dataEditor) {
                dataEditor.setValue(draft.data);
            }
            
            // Show notification with the last save time
            const timestamp = new Date(draft.timestamp);
            showNotification(`Draft loaded (saved ${timestamp.toLocaleString()})`, 'success');
        } catch (e) {
            showNotification('Error loading draft: ' + e.message, 'error');
        }
    }
    
    // Function to automatically save the draft
    function setupAutosave() {
        // Save the draft every 30 seconds
        setInterval(saveDraft, 30000);
        
        // Save the draft when the page is closed
        window.addEventListener('beforeunload', saveDraft);
    }
    
    // Add buttons for working with drafts
    function addDraftButtons() {
        const $templateActions = $('.template-actions');
        
        $templateActions.append(`
            <button type="button" id="save-draft" class="button">Save draft</button>
            <button type="button" id="load-draft" class="button">Load draft</button>
        `);
        
        $('#save-draft').on('click', saveDraft);
        $('#load-draft').on('click', loadDraft);
        
        // Configure autosave
        setupAutosave();
    }
    
    // Function to display notifications
    function showNotification(message, type = 'info') {
        const $notification = $(`<div class="notification ${type}">${message}</div>`);
        
        $('.wp-fasty-template-editor').prepend($notification);
        
        // Remove the notification after 3 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Function to restore a revision
    function restoreRevision(templateFile, dataFile) {
        $.ajax({
            url: wpFasty.ajaxUrl,
            type: 'POST',
            data: {
                action: 'restore_revision',
                template_file: templateFile,
                data_file: dataFile,
                post_id: wpFasty.postId,
                nonce: wpFasty.nonce
            },
            beforeSend: function() {
                showNotification('Loading revision...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    // Update the editors
                    if (templateEditor && response.data.template) {
                        templateEditor.setValue(response.data.template);
                    }
                    
                    if (dataEditor && response.data.data) {
                        dataEditor.setValue(response.data.data);
                    }
                    
                    showNotification('Revision restored', 'success');
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Error loading revision', 'error');
            }
        });
    }
    
    // Initialize the interface
    $(document).ready(function() {
        // Initialize the editors
        initMonacoEditor();
        
        // Initialize the helpers
        initHandlebarsHelpers();
        
        // Switch tabs
        $('.wp-fasty-tab').on('click', function() {
            $('.wp-fasty-tab').removeClass('active');
            $(this).addClass('active');
            
            const tab = $(this).data('tab');
            $('.wp-fasty-tab-content').removeClass('active');
            $(`.wp-fasty-tab-content[data-tab="${tab}"]`).addClass('active');
        });
        
        // Preview template
        $('#wp_fasty_preview_button').on('click', renderTemplate);
        
        // Save HTML
        $('#wp_fasty_save_html').on('click', saveHtml);
        
        // Analyze template
        $('#analyze-template').on('click', analyzeTemplate);
        
        // Create missing fields
        $('#create-missing-fields').on('click', createMissingFields);
        
        // Create a separate field
        $(document).on('click', '.create-field', function() {
            const field = $(this).data('field');
            $.ajax({
                url: wpFasty.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'create_missing_fields',
                    post_id: wpFasty.postId,
                    fields: JSON.stringify([field]),
                    nonce: wpFasty.nonce
                },
                success: function(response) {
                    if (response.success) {
                        loadPostData();
                    }
                }
            });
        });
        
        // Prevent form submission when Enter is pressed in the editors
        $(document).on('keydown', function(e) {
            if (e.key === 'Enter' && $(e.target).closest('.wp-fasty-template-editor').length) {
                // Check that this is not a textarea or other input field
                if (e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Save data before form submission
        $('#post').on('submit', function() {
            if (templateEditor) {
                $('#wp_fasty_template').val(templateEditor.getValue());
            }
            
            if (dataEditor) {
                $('#wp_fasty_template_data').val(dataEditor.getValue());
            }
        });
        
        // Add buttons for working with drafts
        addDraftButtons();
        
        // Handler for revision restore buttons
        $(document).on('click', '.restore-revision', function() {
            const templateFile = $(this).data('template');
            const dataFile = $(this).data('data');
            
            if (confirm('Are you sure you want to restore this revision? Unsaved changes will be lost.')) {
                restoreRevision(templateFile, dataFile);
            }
        });
    });
})(jQuery); 