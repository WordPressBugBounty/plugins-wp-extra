(function() {
    var scriptsToDefer = js_data_object;

    function deferScripts(scripts) {
        scripts.forEach(function(handle) {
            var script = document.createElement('script');
            script.src = handle;
            script.defer = true;
            document.body.appendChild(script);
        });
    }

    deferScripts(scriptsToDefer);
})();
