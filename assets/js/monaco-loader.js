/**
 * Загрузчик Monaco Editor, который избегает конфликтов с React
 */
(function(global) {
    // Сохраняем оригинальный define и require
    var originalDefine = global.define;
    var originalRequire = global.require;
    
    // Функция для загрузки Monaco Editor
    function loadMonaco(callback) {
        // Очищаем глобальные define и require
        if (global.define && global.define.amd) {
            global._monaco_amd = global.define.amd;
            global.define.amd = undefined;
        }
        
        // Создаем элемент script для загрузки Monaco
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = wpFasty.monacoPath + 'vs/loader.js';
        script.onload = function() {
            // Настраиваем require для Monaco
            global.require.config({
                paths: { 'vs': wpFasty.monacoPath + 'vs' }
            });
            
            // Восстанавливаем оригинальный AMD после загрузки Monaco
            if (global._monaco_amd) {
                global.define.amd = global._monaco_amd;
                global._monaco_amd = undefined;
            }
            
            // Вызываем callback
            if (typeof callback === 'function') {
                callback(global.require);
            }
        };
        
        document.head.appendChild(script);
    }
    
    // Экспортируем функцию загрузки
    global.loadMonacoEditor = function(callback) {
        loadMonaco(function(require) {
            require(['vs/editor/editor.main'], function() {
                if (typeof callback === 'function') {
                    callback(global.monaco);
                }
            });
        });
    };
})(window); 