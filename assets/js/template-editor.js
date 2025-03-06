(function($) {
    // Глобальные переменные
    let templateEditor, dataEditor;
    let templateData = {};
    let postData = {};
    
    // Инициализация Monaco Editor
    function initMonacoEditor() {
        // Используем наш загрузчик вместо прямого require
        loadMonacoEditor(function(monaco) {
            // Настройка подсветки синтаксиса Handlebars
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
            
            // Создание редактора шаблона
            templateEditor = monaco.editor.create(document.getElementById('template-editor'), {
                value: $('#wp_fasty_template').val(),
                language: 'handlebars',
                theme: 'vs',
                automaticLayout: true,
                minimap: { enabled: false }
            });
            
            templateEditor.onDidChangeModelContent(function() {
                // Обновляем скрытое поле при изменении содержимого
                $('#wp_fasty_template').val(templateEditor.getValue());
            });
            
            // Создание редактора данных
            dataEditor = monaco.editor.create(document.getElementById('data-editor'), {
                value: $('#wp_fasty_template_data').val(),
                language: 'json',
                theme: 'vs',
                automaticLayout: true,
                minimap: { enabled: false }
            });
            
            dataEditor.onDidChangeModelContent(function() {
                // Обновляем скрытое поле при изменении содержимого
                $('#wp_fasty_template_data').val(dataEditor.getValue());
            });
            
            // Загрузка данных поста
            loadPostData();
        });
    }
    
    // Загрузка данных поста
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
    
    // Обновление вкладки полей
    function updateFieldsTab() {
        // Анализ шаблона
        analyzeTemplate();
    }
    
    // Анализ шаблона для поиска переменных
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
    
    // Отображение вкладки полей
    function renderFieldsTab(data) {
        const $fieldsContainer = $('#template-fields');
        $fieldsContainer.empty();
        
        // Существующие поля
        if (Object.keys(data.existingFields).length > 0) {
            const $existingFields = $('<div class="fields-section"><h3>Существующие поля</h3><div class="fields-list"></div></div>');
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
        
        // Недостающие поля
        if (data.missingFields.length > 0) {
            const $missingFields = $('<div class="fields-section missing-fields"><h3>Недостающие поля</h3><div class="fields-list"></div></div>');
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
        
        // Если нет недостающих полей
        if (data.missingFields.length === 0) {
            $('#create-missing-fields').prop('disabled', true);
        } else {
            $('#create-missing-fields').prop('disabled', false);
        }
    }
    
    // Создание недостающих полей
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
                    // Перезагрузка данных
                    loadPostData();
                    
                    // Уведомление
                    alert('Создано полей: ' + response.data.created.length);
                }
            }
        });
    }
    
    // Рендеринг шаблона
    function renderTemplate() {
        try {
            if (!templateEditor || !dataEditor) return;
            
            const template = templateEditor.getValue();
            const customData = JSON.parse(dataEditor.getValue());
            
            // Компиляция шаблона
            const compiledTemplate = Handlebars.compile(template);
            
            // Объединение данных
            const data = $.extend(true, {}, postData, customData);
            templateData = data;
            
            // Рендеринг
            const html = compiledTemplate(data);
            $('#wp_fasty_preview').html(html);
        } catch (e) {
            $('#wp_fasty_preview').html(`<div class="error">Ошибка: ${e.message}</div>`);
        }
    }
    
    // Сохранение HTML
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
                    alert('HTML успешно сохранен');
                }
            }
        });
    }
    
    // Инициализация Handlebars хелперов
    function initHandlebarsHelpers() {
        Handlebars.registerHelper('formatDate', function(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString();
        });
        
        Handlebars.registerHelper('excerpt', function(text, options) {
            if (!text) return '';
            const length = options.hash.length || 150;
            
            // Безопасное удаление HTML-тегов
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
    
    // Функция для сохранения черновика в localStorage
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
        
        // Показываем уведомление
        showNotification('Черновик сохранен локально', 'success');
    }
    
    // Функция для загрузки черновика из localStorage
    function loadDraft() {
        const postId = wpFasty.postId;
        const draftJson = localStorage.getItem(`wp_fasty_draft_${postId}`);
        
        if (!draftJson) {
            showNotification('Черновик не найден', 'error');
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
            
            // Показываем уведомление с временем последнего сохранения
            const timestamp = new Date(draft.timestamp);
            showNotification(`Черновик загружен (сохранен ${timestamp.toLocaleString()})`, 'success');
        } catch (e) {
            showNotification('Ошибка загрузки черновика: ' + e.message, 'error');
        }
    }
    
    // Функция для автоматического сохранения черновика
    function setupAutosave() {
        // Сохраняем черновик каждые 30 секунд
        setInterval(saveDraft, 30000);
        
        // Сохраняем черновик при закрытии страницы
        window.addEventListener('beforeunload', saveDraft);
    }
    
    // Добавляем кнопки для работы с черновиками
    function addDraftButtons() {
        const $templateActions = $('.template-actions');
        
        $templateActions.append(`
            <button type="button" id="save-draft" class="button">Сохранить черновик</button>
            <button type="button" id="load-draft" class="button">Загрузить черновик</button>
        `);
        
        $('#save-draft').on('click', saveDraft);
        $('#load-draft').on('click', loadDraft);
        
        // Настраиваем автосохранение
        setupAutosave();
    }
    
    // Функция для отображения уведомлений
    function showNotification(message, type = 'info') {
        const $notification = $(`<div class="notification ${type}">${message}</div>`);
        
        $('.wp-fasty-template-editor').prepend($notification);
        
        // Удаляем уведомление через 3 секунды
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Функция для восстановления ревизии
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
                showNotification('Загрузка ревизии...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    // Обновляем редакторы
                    if (templateEditor && response.data.template) {
                        templateEditor.setValue(response.data.template);
                    }
                    
                    if (dataEditor && response.data.data) {
                        dataEditor.setValue(response.data.data);
                    }
                    
                    showNotification('Ревизия восстановлена', 'success');
                } else {
                    showNotification('Ошибка: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('Ошибка при загрузке ревизии', 'error');
            }
        });
    }
    
    // Инициализация интерфейса
    $(document).ready(function() {
        // Инициализация редакторов
        initMonacoEditor();
        
        // Инициализация хелперов
        initHandlebarsHelpers();
        
        // Переключение вкладок
        $('.wp-fasty-tab').on('click', function() {
            $('.wp-fasty-tab').removeClass('active');
            $(this).addClass('active');
            
            const tab = $(this).data('tab');
            $('.wp-fasty-tab-content').removeClass('active');
            $(`.wp-fasty-tab-content[data-tab="${tab}"]`).addClass('active');
        });
        
        // Предпросмотр шаблона
        $('#wp_fasty_preview_button').on('click', renderTemplate);
        
        // Сохранение HTML
        $('#wp_fasty_save_html').on('click', saveHtml);
        
        // Анализ шаблона
        $('#analyze-template').on('click', analyzeTemplate);
        
        // Создание недостающих полей
        $('#create-missing-fields').on('click', createMissingFields);
        
        // Создание отдельного поля
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
        
        // Предотвращаем отправку формы при нажатии Enter в редакторах
        $(document).on('keydown', function(e) {
            if (e.key === 'Enter' && $(e.target).closest('.wp-fasty-template-editor').length) {
                // Проверяем, что это не textarea или другое поле ввода
                if (e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Сохраняем данные перед отправкой формы
        $('#post').on('submit', function() {
            if (templateEditor) {
                $('#wp_fasty_template').val(templateEditor.getValue());
            }
            
            if (dataEditor) {
                $('#wp_fasty_template_data').val(dataEditor.getValue());
            }
        });
        
        // Добавляем кнопки для работы с черновиками
        addDraftButtons();
        
        // Обработчик для кнопок восстановления ревизий
        $(document).on('click', '.restore-revision', function() {
            const templateFile = $(this).data('template');
            const dataFile = $(this).data('data');
            
            if (confirm('Вы уверены, что хотите восстановить эту ревизию? Несохраненные изменения будут потеряны.')) {
                restoreRevision(templateFile, dataFile);
            }
        });
    });
})(jQuery); 