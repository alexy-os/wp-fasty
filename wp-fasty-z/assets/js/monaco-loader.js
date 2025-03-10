/**
 * Monaco Editor loader that avoids conflicts with React
 */
(function(global) {
    // Save the original define and require
    var originalDefine = global.define;
    var originalRequire = global.require;
    
    // Function to load Monaco Editor
    function loadMonaco(callback) {
        // Clear global define and require
        if (global.define && global.define.amd) {
            global._monaco_amd = global.define.amd;
            global.define.amd = undefined;
        }
        
        // Create a script element for loading Monaco
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = wpFasty.monacoPath + 'vs/loader.js';
        script.onload = function() {
            // Configure require for Monaco
            global.require.config({
                paths: { 'vs': wpFasty.monacoPath + 'vs' }
            });
            
            // Restore the original AMD after loading Monaco
            if (global._monaco_amd) {
                global.define.amd = global._monaco_amd;
                global._monaco_amd = undefined;
            }
            
            // Call the callback
            if (typeof callback === 'function') {
                callback(global.require);
            }
        };
        
        document.head.appendChild(script);
    }
    
    // Export the loading function
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