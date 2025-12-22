define(['jquery', 'theme_boost/popover'], function($, PopoverModule) {
    return {
        init: function() {
            $(function() {
                try {
                    // Resolve the Popover class from the module
                    var Popover = PopoverModule.Popover;
                    if (!Popover && PopoverModule.default) {
                        Popover = PopoverModule.default;
                    } else if (!Popover) {
                        Popover = PopoverModule;
                    }

                    var elems = document.querySelectorAll('[data-toggle="popover"]');
                    
                    Array.prototype.slice.call(elems).forEach(function(el) {
                        try {
                            new Popover(el);
                        } catch (e) {
                            // console.debug('Popover init error', e);
                        }
                    });
                } catch (e) {
                    // console.error('Error initializing popovers', e);
                }
            });
        }
    };
});
