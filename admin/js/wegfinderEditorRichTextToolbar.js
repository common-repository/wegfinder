(function(wp) {


	try {

		function wegfinderEditorRichTextToolbar(wp, menuData, translations) {

			const wegfinderIcon = wp.element.createElement("svg", {
				width: "16",
				height: "16",
				viewBox: "0 0 4.233 4.233",
				xmlns: "http://www.w3.org/2000/svg"
			  }, wp.element.createElement("path", {
				d: "M2.118 0C1.212 0 .44.568.14 1.367h.74l.774 1.63.296-.45c-.296-.478-.478-.87-.478-1.166 0-.34.24-.688.65-.688s.655.35.655.688c0 .292-.18.69-.477 1.166l.29.448.774-1.63h.736A2.12 2.12 0 0 0 2.118 0zm.001.985a.4.4 0 0 0-.084.009.34.34 0 0 0-.184.106.41.41 0 0 0-.095.281c0 .205.105.454.363.884.263-.43.363-.678.363-.884 0-.272-.182-.397-.363-.397zm0 .272c.09 0 .162.072.162.167s-.072.167-.162.167-.162-.077-.162-.167.072-.167.162-.167zM.05 1.66a2.12 2.12 0 0 0-.05.458c0 1.17.948 2.116 2.116 2.116a2.12 2.12 0 0 0 2.117-2.116c0-.154-.017-.308-.05-.458h-.636L2.62 3.61l-.502-.784-.502.784-.922-1.95z"
			  }));


			var wegfinderRichTextToolbarButton = function(props) {

				return wp.element.createElement(
					wp.editor.RichTextToolbarButton, {
						icon: wegfinderIcon,
						title: 'wegfinder',
						onClick: function() {

							// toggle popover
							let elPopover = document.getElementById('wegfinderPopOver');
							if (elPopover) {
								elPopover.parentNode.removeChild(elPopover);
							} else {
								document.querySelectorAll('button[aria-label="wegfinder"]')[0].append(wegfinderPopover(props, menuData));
							}

						},
						isActive: props.isActive
					}

				);
			}


			var wegfinderPopover = function(props, menuData) {

				let toolbarButtonBounds = document.querySelectorAll('button[aria-label="wegfinder"]')[0].getBoundingClientRect();
				let toolbarBounds = document.querySelectorAll('.editor-block-toolbar')[0].getBoundingClientRect();

				let el = document.createElement('div');
				el.setAttribute('id', 'wegfinderPopOver');

				let tabindex = document.createElement('div');
				tabindex.setAttribute('tabindex', '-1');
				el.appendChild(tabindex);

				let emptydiv = document.createElement('div');
				el.appendChild(emptydiv);

				let popover = document.createElement('div');
				popover.setAttribute('style', ' top: ' + toolbarBounds.height + 'px; left: ' + (toolbarButtonBounds.left - toolbarBounds.left + toolbarButtonBounds.width / 2) + 'px;');
				popover.classList = 'components-popover components-dropdown-menu__popover is-bottom is-center';
				emptydiv.appendChild(popover);

				let popoverContent = document.createElement('div');
				popoverContent.setAttribute('tabindex', '-1');
				popoverContent.classList = 'components-popover__content';
				popoverContent.setAttribute('style', 'max-height:225px;overflow-y: scroll;');
				popover.appendChild(popoverContent);

				let menu = document.createElement('div');
				menu.setAttribute('role', 'menu');
				menu.setAttribute('aria-orientation', 'vertical');
				menu.classList = 'components-dropdown-menu__menu';
				popoverContent.appendChild(menu);

				let menuButton = null;

				if (menuData.length > 0) {

					menuData.forEach(function(item) {
						menuButton = document.createElement('button');
						menuButton.setAttribute('type', 'button');
						menuButton.setAttribute('role', 'menuitem');
						menuButton.classList = 'components-button components-icon-button components-dropdown-menu__menu-item';
						menuButton.innerText = item.name;
						menuButton.addEventListener("click", function(event) {
							return wegfinderMenuSelected(item.id, item.name, props, el);
						});
						menu.appendChild(menuButton);
					});

					menu.appendChild(document.createElement('hr'));
				} 

				// Empty Button
				menuButton = document.createElement('button');
				menuButton.setAttribute('type', 'button');
				menuButton.setAttribute('role', 'menuitem');
				menuButton.classList = 'components-button components-icon-button components-dropdown-menu__menu-item';
				menuButton.innerText = translations.generic;
				menuButton.addEventListener("click", function(event) {
				return wegfinderMenuSelected(null, 'blank', props, el);
				});
				menu.appendChild(menuButton);

				// Define new target
				menuButton = document.createElement('button');
				menuButton.setAttribute('type', 'button');
				menuButton.setAttribute('role', 'menuitem');
				menuButton.classList = 'components-button components-icon-button components-dropdown-menu__menu-item';
				menuButton.innerText = translations.new;
				menuButton.addEventListener("click", function(event) {
				return wegfinderMenuSelected(null, 'new', props, el);
				});
				menu.appendChild(menuButton);

				return el;

			}


			function wegfinderMenuSelected(id, name, props, el) {
				el.parentNode.removeChild(el);
				if (id) {
					// Insert Shortcode with id, name is just for info for the editor
					props.onChange(wp.richText.insert(props.value, "[wegfinder id=\"" + id + "\" name=\"" + name.replace(/\"/g, '')+ "\"]", props.value.start, props.value.start));
				} else {
					if (name == 'new') {
						// Link to create new Shortcode
						window.location.href='admin.php?page=wegfinder-new';
					} else {
						// Add shortcode without ID
						props.onChange(wp.richText.insert(props.value, "[wegfinder]", props.value.start, props.value.start));
					}
				}
				
			}

			wp.richText.registerFormatType(
				'wegfinder/wegfinder-shortcodeinsert', {
					title: 'Add wegfinder Shortcode',
					tagName: 'wegfinder',
					className: null,
					edit: wegfinderRichTextToolbarButton,
				}
			);

		}

		wegfinderEditorRichTextToolbar(window.wp, wegfinderEditorRichtextToolbarMenuData, wegfinderEditorRichtextToolbarTranslations);

	} catch (e) {}

})(window.wp);