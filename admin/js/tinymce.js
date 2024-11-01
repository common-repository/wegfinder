(function() {


	var wegfinder_menuData=[{text: 'Unbekanntes Ziel', value: ''},];

	// Load data 
	jQuery.post(ajaxurl, {action:'wegfinder_menudata_ajax'}, function(json){
            wegfinder_menuData = JSON.parse(json);
       });

    tinymce.create("tinymce.plugins.wegfinder", {
 	
        //url argument holds the absolute url of our plugin directory
        init : function(ed, url) {
 				
            //add new button     
            ed.addButton("wegfinder", {
                title : "wegfinder",
                cmd : "wegfinder_sc_command",
                image : url + "/../img/wegfinderLogoS.svg"
            });

            //button functionality.
            ed.addCommand("wegfinder_sc_command", function() {  


				ed.windowManager.open({
								title: 'wegfinder',
								body: [
									{
										type: 'listbox', 
										name: 'id',            
										'values': wegfinder_menuData
									}
								],
								onsubmit: function (e) {
									ed.insertContent('[wegfinder id="' + e.data.id + '"]');
								  }
							   });
            });

        },

        createControl : function(n, cm) {
            return null;
        },

        getInfo : function() {
            return {
                longname : "wegfinder",
                author : "iMobility GmbH",
                version : "1"
            };
        }
    });

    tinymce.PluginManager.add("wegfinder", tinymce.plugins.wegfinder);
})();