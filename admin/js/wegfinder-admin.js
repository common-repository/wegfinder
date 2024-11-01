(function( $ ) {
	'use strict';


var wegfinder_lib_autoComplete = (function(){
    // "use strict";
    function autoComplete(options){
        if (!document.querySelector) return;

        // helpers
        function hasClass(el, className){ return el.classList ? el.classList.contains(className) : new RegExp('\\b'+ className+'\\b').test(el.className); }

        function addEvent(el, type, handler){
            if (el.attachEvent) el.attachEvent('on'+type, handler); else el.addEventListener(type, handler);
        }
        function removeEvent(el, type, handler){
            // if (el.removeEventListener) not working in IE11
            if (el.detachEvent) el.detachEvent('on'+type, handler); else el.removeEventListener(type, handler);
        }
        function live(elClass, event, cb, context){
            addEvent(context || document, event, function(e){
                var found, el = e.target || e.srcElement;
                while (el && !(found = hasClass(el, elClass))) el = el.parentElement;
                if (found) cb.call(el, e);
            });
        }

        var o = {
            selector: 0,
            source: 0,
            minChars: 3,
            delay: 150,
            offsetLeft: 0,
            offsetTop: 1,
            cache: 1,
            menuClass: '',
            renderItem: function (item, search){
                // escape special characters
                search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                var re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
                return '<div class="wegfinder-autocomplete-suggestion" data-val="' + item + '">' + item.replace(re, "<b>$1</b>") + '</div>';
            },
            onSelect: function(e, term, item){}
        };
        for (var k in options) { if (options.hasOwnProperty(k)) o[k] = options[k]; }

        // init
        var elems = typeof o.selector == 'object' ? [o.selector] : document.querySelectorAll(o.selector);
        for (var i=0; i<elems.length; i++) {
            var that = elems[i];

            // create suggestions container "sc"
            that.sc = document.createElement('div');
            that.sc.className = 'wegfinder-autocomplete-suggestions '+o.menuClass;

            that.autocompleteAttr = that.getAttribute('autocomplete');
            that.setAttribute('autocomplete', 'off');
            that.cache = {};
            that.last_val = '';

            that.updateSC = function(resize, next){
                var rect = that.getBoundingClientRect();
                that.sc.style.left = Math.round(rect.left + (window.pageXOffset || document.documentElement.scrollLeft) + o.offsetLeft) + 'px';
                that.sc.style.top = Math.round(rect.bottom + (window.pageYOffset || document.documentElement.scrollTop) + o.offsetTop) + 'px';
                that.sc.style.width = Math.round(rect.right - rect.left) + 'px'; // outerWidth
                if (!resize) {
                    that.sc.style.display = 'block';
                    if (!that.sc.maxHeight) { that.sc.maxHeight = parseInt((window.getComputedStyle ? getComputedStyle(that.sc, null) : that.sc.currentStyle).maxHeight); }
                    if (!that.sc.suggestionHeight) that.sc.suggestionHeight = that.sc.querySelector('.wegfinder-autocomplete-suggestion').offsetHeight;
                    if (that.sc.suggestionHeight)
                        if (!next) that.sc.scrollTop = 0;
                        else {
                            var scrTop = that.sc.scrollTop, selTop = next.getBoundingClientRect().top - that.sc.getBoundingClientRect().top;
                            if (selTop + that.sc.suggestionHeight - that.sc.maxHeight > 0)
                                that.sc.scrollTop = selTop + that.sc.suggestionHeight + scrTop - that.sc.maxHeight;
                            else if (selTop < 0)
                                that.sc.scrollTop = selTop + scrTop;
                        }
                }
            }
            addEvent(window, 'resize', that.updateSC);
            document.body.appendChild(that.sc);

            live('wegfinder-autocomplete-suggestion', 'mouseleave', function(e){
                var sel = that.sc.querySelector('.wegfinder-autocomplete-suggestion.selected');
                if (sel) setTimeout(function(){ sel.className = sel.className.replace('selected', ''); }, 20);
            }, that.sc);

            live('wegfinder-autocomplete-suggestion', 'mouseover', function(e){
                var sel = that.sc.querySelector('.wegfinder-autocomplete-suggestion.selected');
                if (sel) sel.className = sel.className.replace('selected', '');
                this.className += ' selected';
            }, that.sc);

            live('wegfinder-autocomplete-suggestion', 'mousedown', function(e){
                if (hasClass(this, 'wegfinder-autocomplete-suggestion')) { // else outside click
                    var v = this.getAttribute('data-val');
                    that.value = v;
                    o.onSelect(e, v, this);
                    that.sc.style.display = 'none';
                }
            }, that.sc);

            that.blurHandler = function(){
                try { var over_sb = document.querySelector('.wegfinder-autocomplete-suggestions:hover'); } catch(e){ var over_sb = 0; }
                if (!over_sb) {
                    that.last_val = that.value;
                    that.sc.style.display = 'none';
                    setTimeout(function(){ that.sc.style.display = 'none'; }, 350); // hide suggestions on fast input
                } else if (that !== document.activeElement) setTimeout(function(){ that.focus(); }, 20);
            };
            addEvent(that, 'blur', that.blurHandler);

            var suggest = function(data){
                var val = that.value;
                that.cache[val] = data;
                if (data.length && val.length >= o.minChars) {
                    var s = '';
                    for (var i=0;i<data.length;i++) s += o.renderItem(data[i], val);
                    that.sc.innerHTML = s;
                    that.updateSC(0);
                }
                else
                    that.sc.style.display = 'none';
            }

            that.keydownHandler = function(e){
                var key = window.event ? e.keyCode : e.which;
                // down (40), up (38)
                if ((key == 40 || key == 38) && that.sc.innerHTML) {
                    var next, sel = that.sc.querySelector('.wegfinder-autocomplete-suggestion.selected');
                    if (!sel) {
                        next = (key == 40) ? that.sc.querySelector('.wegfinder-autocomplete-suggestion') : that.sc.childNodes[that.sc.childNodes.length - 1]; // first : last
                        next.className += ' selected';
                        that.value = next.getAttribute('data-val');
                    } else {
                        next = (key == 40) ? sel.nextSibling : sel.previousSibling;
                        if (next) {
                            sel.className = sel.className.replace('selected', '');
                            next.className += ' selected';
                            that.value = next.getAttribute('data-val');
                        }
                        else { sel.className = sel.className.replace('selected', ''); that.value = that.last_val; next = 0; }
                    }
                    that.updateSC(0, next);
                    return false;
                }
                // esc
                else if (key == 27) { that.value = that.last_val; that.sc.style.display = 'none'; }
                // enter
                else if (key == 13 || key == 9) {
                    var sel = that.sc.querySelector('.wegfinder-autocomplete-suggestion.selected');
                    if (sel && that.sc.style.display != 'none') { o.onSelect(e, sel.getAttribute('data-val'), sel); setTimeout(function(){ that.sc.style.display = 'none'; }, 20); }
                }
            };
            addEvent(that, 'keydown', that.keydownHandler);

            that.keyupHandler = function(e){
                var key = window.event ? e.keyCode : e.which;
                if (!key || (key < 35 || key > 40) && key != 13 && key != 27) {
                    var val = that.value;
                    if (val.length >= o.minChars) {
                        if (val != that.last_val) {
                            that.last_val = val;
                            clearTimeout(that.timer);
                            if (o.cache) {
                                if (val in that.cache) { suggest(that.cache[val]); return; }
                                // no requests if previous suggestions were empty
                                for (var i=1; i<val.length-o.minChars; i++) {
                                    var part = val.slice(0, val.length-i);
                                    if (part in that.cache && !that.cache[part].length) { suggest([]); return; }
                                }
                            }
                            that.timer = setTimeout(function(){ o.source(val, suggest) }, o.delay);
                        }
                    } else {
                        that.last_val = val;
                        that.sc.style.display = 'none';
                    }
                }
            };
            addEvent(that, 'keyup', that.keyupHandler);

            that.focusHandler = function(e){
                that.last_val = '\n';
                that.keyupHandler(e)
            };
            if (!o.minChars) addEvent(that, 'focus', that.focusHandler);
        }

        // public destroy method
        this.destroy = function(){
            for (var i=0; i<elems.length; i++) {
                var that = elems[i];
                removeEvent(window, 'resize', that.updateSC);
                removeEvent(that, 'blur', that.blurHandler);
                removeEvent(that, 'focus', that.focusHandler);
                removeEvent(that, 'keydown', that.keydownHandler);
                removeEvent(that, 'keyup', that.keyupHandler);
                if (that.autocompleteAttr)
                    that.setAttribute('autocomplete', that.autocompleteAttr);
                else
                    that.removeAttribute('autocomplete');
                document.body.removeChild(that.sc);
                that = null;
            }
        };
    }
    return autoComplete;
})();



    if (typeof define === 'function' && define.amd)
        define('wegfinder_lib_autoComplete', function () { return wegfinder_lib_autoComplete; });
    else if (typeof module !== 'undefined' && module.exports)
        module.exports = wegfinder_lib_autoComplete;
    else
        window.wegfinder_lib_autoComplete = wegfinder_lib_autoComplete;


var wegfinder_xhr;
$( window ).load(function() {
	
	

		
		var wegfinder_autocomplete = new wegfinder_lib_autoComplete({
			selector: '#wegfinder_entry_locname',
			minChars: 3,
			source: function(term, response){

				try { 
					wegfinder_wegfinder_xhr.abort(); 
				} catch(e){ 
					wegfinder_xhr = new XMLHttpRequest();
				}


				wegfinder_xhr.onreadystatechange = function() {

					if (wegfinder_xhr.readyState == 4 && wegfinder_xhr.status == 200) {
							response(JSON.parse(wegfinder_xhr.responseText).data);						
					}

				}
				wegfinder_xhr.open("GET", "https://api.i-mobility.at/routing/api/v2/locations?query=" + encodeURI(term) ,true);
				wegfinder_xhr.setRequestHeader('Accept-Language', 'de');
				wegfinder_xhr.setRequestHeader('Imob-Agent', 'wordpress-plugin');
				wegfinder_xhr.send();

			},
			renderItem: function (item, search){
				search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
				var re = new RegExp("(" + search.split(' ').join('|') + ")", "gi");
				return '<div class="wegfinder-autocomplete-suggestion" data-id="' + item.id + '" data-name="' + item.name + '">' + item.name.replace(re, "<b>$1</b>") + '</div>';
				},
			onSelect: function(e, term, item){		
				document.getElementById("wegfinder_entry_locname").value = item.getAttribute('data-name');
				document.getElementById("wegfinder_entry_locid").value = item.getAttribute('data-id');
				document.getElementById("wegfinder_entry_name").value = item.getAttribute('data-name');
				}			
		});

 });

window['wegfinder_add_ts_set'] = function () {

			var timestamp = new Date(document.getElementById("wegfinder_entry_date").value + ' ' + document.getElementById("wegfinder_entry_time").value);
			
			var y = timestamp.getFullYear();
			var m = ("00" + (timestamp.getMonth() + 1)).slice(-2);
			var d = ("00" + timestamp.getDate()).slice(-2);
			var hh = ("00" + timestamp.getHours()).slice(-2);
			var mm = ("00" + timestamp.getMinutes()).slice(-2);
			var hhh = ("00" + Math.trunc(Math.abs(timestamp.getTimezoneOffset())/60)).slice(-2);
			var mmm = ("00" + Math.abs(timestamp.getTimezoneOffset()) % 60).slice(-2);
		
			document.getElementById("wegfinder_entry_arrival").value = y + '-' + m + '-' + d + 'T' + hh + ":" + mm + ":00+" + hhh + ":" + mmm;


}

window['wegfinder_clipboard'] = function(id) {
	try {
		let copy = "[wegfinder id=\""+id+"\"]";
		navigator.clipboard.writeText(copy);
		document.getElementById('wegfinder_update_copied_to_clipboard').classList.remove('hidden');
	} catch (e) {}
}


window['wegfinder_settings_select_style'] = function(wfClearUpdateFlag) {

    wfClearUpdateFlag = (typeof wfClearUpdateFlag !== 'undefined') ?  wfClearUpdateFlag : true;
	let style = document.getElementsByName('wegfinder_settings[wegfinder_style]')[0].value;
		
	if	(style == 'pink' || style == 'blue' || style == 'red') {	

		document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].disabled = true;
		document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[0].checked = true;
		document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[0].disabled = true;
		document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[1].disabled = true;

		if(style == 'pink') {					
			document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].value="#ff1c7e";				
		} else if(style == 'blue') {					
			document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].value="#056BA6";
		} else if(style == 'red') {					
			document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].value="#E2002A";
		}	
	} else {
		document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].disabled = false;
		document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[0].disabled = false;
		document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[1].disabled = false;
	}

	wegfinder_settings_demo_update(wfClearUpdateFlag);

}

window['wegfinder_settings_submit'] = function() {

	document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].disabled = false;
	document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[0].disabled = false;
	document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[1].disabled = false;
}


window['wegfinder_settings_reset'] = function() {

	document.getElementsByName('wegfinder_settings[wegfinder_style]')[0].value = "pink";
	document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].value="#ff1c7e";
	document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[0].checked = true;
	document.getElementsByName('wegfinder_settings[wegfinder_size]')[0].value = 4;
	document.getElementsByName('wegfinder_settings[wegfinder_target]')[0].value = "wegfinder";	
}



window['wegfinder_settings_demo_update'] = function(wfClearUpdateFlag) {

		let arrSize = ["xxs", "xs", "s", "m", "l", "xl", "xxl"];
		let css = '';
		let style = document.getElementsByName('wegfinder_settings[wegfinder_style]')[0].value;

	let html = '<a href="https://wegfinder.at/route/from//to/1wH5A/at/departure/now?pk_campaign=plugins&amp;pk_keyword=&amp;pk_source=wp.wohntraum21.at&amp;pk_medium=wordpress&amp;pk_content=Wien+Stephansplatz+%28U%29" title="wie wohin - Vergleiche, kombiniere und buche neue Wege" target="' + document.getElementsByName('wegfinder_settings[wegfinder_target]')[0].value + '" ';
		
		html += 'class="wegfinder wegfinder-' + arrSize[document.getElementsByName('wegfinder_settings[wegfinder_size]')[0].value-1];
		
		
		if (style == "pink" || style == "blue" || style =="red") {
			html += ' wegfinder-' + style;
		} else {
			css = 'background-color: ' + document.getElementsByName('wegfinder_settings[wegfinder_custom_color]')[0].value + ';';
			if (document.getElementsByName('wegfinder_settings[wegfinder_custom_text_color]')[0].checked) {
				html += ' wegfinder-dark';
			} else {
				html += ' wegfinder-light';
			}
		}
		html += '" style="' + css + '"';
		
		html += '><span></span></a>';
		document.getElementById('wegfinderDesignDemoButton').innerHTML =  html;
	
 	wfClearUpdateFlag = (typeof wfClearUpdateFlag !== 'undefined') ?  wfClearUpdateFlag : true;

	try {
		if (wfClearUpdateFlag) {
			document.querySelector('.settings-error').remove();
		}
	} catch (e) {}


}


})( jQuery );
