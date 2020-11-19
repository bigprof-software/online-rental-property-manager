$j(function() {

	AppGini = AppGini || {};

	AppGini.Calendar = {
		_bootstrapQueriesCached: null,
		bootstrapQueries: function() {
			if(this._bootstrapQueriesCached !== null) return this._bootstrapQueriesCached;

			// add hidden items to page to retrieve bootstrap theme's colors
			var colors = ['danger', 'info', 'primary', 'success', 'warning'];
			var queryClassName = 'calendar-common-bootstrap-hidden-container';
			var testBootstrapElements = [
				'<div class="' + queryClassName + ' hidden">',
					'<table class="table table-bordered">',
						'<thead><tr><th></th></tr></thead>',
						'<tbody><tr><td></td></tr></tbody>',
					'</table>'
			];

			for (var i = 0; i < colors.length; i++) {
				var cc = colors[i];
				testBootstrapElements.push('<div class="text-' + cc + ' bg-' + cc + '">' + cc + '</div>');
			}
			testBootstrapElements.push('</div>');

			testBootstrapElements = testBootstrapElements.join('');

			if(!$j('.' + queryClassName).length) {
				if($j('body > .container').length)
					$j(testBootstrapElements).appendTo('body > .container');
				else
					$j(testBootstrapElements).appendTo('body > .container-fluid');
			}

			var qe = $j('.' + queryClassName);
			var text = {}, background = {}, border = {};

			for (var i = 0; i < colors.length; i++) {
				var cc = colors[i];
				text[cc] = qe.find('.text-' + cc).css('color');
				background[cc] = qe.find('.bg-' + cc).css('background-color');
				border[cc] = qe.find('.bg-' + cc).css('border-top-color');
			}

			this._bootstrapQueriesCached = {
				tableBorderColor: qe.find('.table-bordered').css("border-left-color"),
				tableHeadCell: qe.find('.table-bordered th').get(0),
				text: text,
				background: background,
				border: border
			};

			return this._bootstrapQueriesCached;
		},

		enforceBootstrapColorClasses: function() {
			if($j('#enforceBootstrapColorClasses').length) return;

			var bsq = this.bootstrapQueries(),
				colors = ['danger', 'info', 'primary', 'success', 'warning'],
				bgClasses = [],
				textClasses = [];

			for (var i = 0; i < colors.length; i++) {
				var cc = colors[i];
				bgClasses.push(
					'.fc-event.' + cc + ', .fc-event.bg-' + cc + ' { ' + 
						'background-color: ' + bsq.background[cc] + ' !important; ' + 
						'border-color: ' + bsq.border[cc] + ' !important; ' + 
					'} ' +
					'.' + cc + ' .fc-event-dot, .bg-' + cc + ' .fc-event-dot { ' + 
						'background-color: ' + bsq.text[cc] + ' !important; ' + 
						'border-color: ' + bsq.border[cc] + ' !important; ' + 
					'} '
				);
				textClasses.push('.fc-event.text-' + cc + ' { color: ' + bsq.text[cc] + ' !important; } ');
			}

			$j([
				'<style id="enforceBootstrapColorClasses">',
					bgClasses.join(''),
					textClasses.join(''),
				'</style>'
			].join('')).appendTo('body');
		},

		urlParam: function(param) {
			var url = new URL(window.location.href);
			return url.searchParams.get(param);
		},

		urlDate: function(defaultDateCode) {
			var format = 'YYYY-MM-DD',

				// parse various values of defaultDate string ...
				parser = {
					'[today]': moment(),
					'[yesterday]': moment().subtract(1, 'days'),
					'[last-month]': moment().subtract(1, 'months'),
					'[last-year]': moment().subtract(1, 'years'),
					'[tomorrow]': moment().add(1, 'days'),
					'[next-month]': moment().add(1, 'months'),
					'[next-year]': moment().add(1, 'years'),
					'custom-date': moment(defaultDateCode, format), // check this later using .isValid()
					'url': moment(this.urlParam('date'), format) // check this later using .isValid()
				};

			// highest priority is given to URL
			if(parser['url'].isValid()) return parser['url'].format(format);

			// next priority is for custom-date
			if(parser['custom-date'].isValid()) return parser['custom-date'].format(format);

			// next, try to see if defaultDateCode is a valid date code
			if(parser.hasOwnProperty(defaultDateCode)) return parser[defaultDateCode].format(format);

			// if all above fails, return today
			return moment().format(format);
		},

		urlView: function(defaultView) {
			var view = this.urlParam('view'), 
				views = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];
			
			// highest priority is given to URL
			if(views.indexOf(view) != -1) return view;

			// next is for defaultView
			if(views.indexOf(defaultView) != -1) return defaultView;

			// if all above fails, use month view
			return views[0];
		},

		updateUrlDate: function(newDate, view) {
			history.replaceState({}, document.title, '?' + $j.param({
				date: newDate,
				view: view
			}));
		},

		fullCalendarBootstrapize: function(cal) {
			if(typeof(cal) !== 'object') cal = $j(cal);
			
			cal.removeClass('fc-unthemed').addClass('bootstrap-themed');

			replace = {
				'fc-button-group': 'btn-group',
				'fc-button': 'btn',
				'fc-button-primary': 'btn-primary',
				'fc-button-info': 'btn-info',
				'fc-button-warning': 'btn-warning',
				'fc-button-danger': 'btn-danger',
				'fc-button-default': 'btn-default',
				'fc-icon': 'glyphicon',
				'fc-icon-chevron-left': 'glyphicon-chevron-left',
				'fc-icon-chevrons-left': 'glyphicon-fast-backward',
				'fc-icon-chevron-right': 'glyphicon-chevron-right',
				'fc-icon-chevrons-right': 'glyphicon-fast-forward',
				'fc-icon-minus-square': 'glyphicon-minus-sign',
				'fc-icon-plus-square': 'glyphicon-plus-sign',
				'fc-icon-refresh': 'glyphicon-refresh',
				'fc-icon-x': 'glyphicon-remove',
			};

			for (var f in replace) cal.find('.' + f).addClass(replace[f]).removeClass(f);

			this.enforceBootstrapColorClasses();
		},

		fullCalendarFixes: function(cal, view) {
			if(typeof(cal) !== 'object') cal = $j(cal);

			var disabledBut = '.fc-' + view.type + '-button';

			cal.find('.fc-today').addClass('bg-info');
			cal.find('.fc-today .fc-day-number').addClass('text-info text-bold');
			
			// apply bootstrap table border colors to calendar table
			var thStyle = getComputedStyle(this.bootstrapQueries().tableHeadCell);
			cal.find('td, th').css({ 'border-color': this.bootstrapQueries().tableBorderColor });
			cal.find('th').css({
				'padding-top': thStyle['padding-top'],
				'padding-bottom': thStyle['padding-bottom']
			});

			cal.find('.fc-head-container table').css({ 'margin-bottom': 'unset' });
			cal.find('.fc-row, .fc-divider, .fc-list-view').css({ 'border-color': this.bootstrapQueries().tableBorderColor });
			cal.find(disabledBut).prop('disabled', true);
			cal.find('.fc-dayGridMonth-button, .fc-timeGridWeek-button, .fc-timeGridDay-button, .fc-listWeek-button')
				.not(disabledBut)
				.prop('disabled', false);
		},

		newEventButtons: {
			_get: function(type) {
				return $j('.new-event.type-' + type);
			},

			// showNewEventButton: newEventButtons.show
			show: function(i) {
				$j('.new-event').each(function() {
					$j(this).removeClass('hidden').data('event', {
						'calendar.startDate': moment(i.start).format('YYYY-MM-DD'),
						'calendar.startTime': moment(i.start).format('HH:mm:ss'),
						'calendar.endDate': moment(i.end).format('YYYY-MM-DD'),
						'calendar.endTime': moment(i.end).format('HH:mm:ss'),
						'calendar.allDay': i.allDay,
						'calendar.newEventType': $j(this).data('type')
					});
				});
			},

			hide: function() {
				setTimeout(function() {
					$j('.new-event').addClass('hidden').data('event', null);
				}, 250);
			},

			/*
				buttons is an array of objects, each like this:
				{
					type: 'new-order', // /^[a-z-]+$/
					color: 'success',
					title: 'New order',
					table: 'orders'
				} 
			*/
			create: function(buttons) {
				// abort if no types specified
				if(!buttons.length) return;

				// abort if page has no container
				// TODO: refactor as a separate container() function and reuse in all code
				var container = $j('body > .container, body > .container-fluid').eq(0);
				if(!container.length) return;

				var bottom = 1.5;
				for(var i = 0; i < buttons.length; i++) {
					var btn = buttons[i];

					// proceed only if button hasn't already been created
					var btnChk = this._get(btn.type);
					if(btnChk.length) continue;

					$j('<div><i class="glyphicon glyphicon-plus"></i></div>')
						.addClass('hidden new-event label label-' + btn.color + ' type-' + btn.type)
						.attr('title', btn.title)
						.css('bottom', bottom + 'em')
						.data('type', btn.type)
						.data('table', btn.table)
						.on('click', function() {
							var btn = $j(this);
							var params = btn.data('event');
							params.Embedded = 1;
							params.addNew_x = 1;

							modal_window({
								// example url: ../orders_view.php?calendar.startDate=2018-03-04&calendar.startTime=13%3A30%3A00&calendar.endDate=2018-03-04&calendar.endTime=21%3A30%3A00&calendar.allDay=false&calendar.newEventType=new-special-order&Embedded=1&addNew_x=1
								url: '../' + btn.data('table') + '_view.php?' + $j.param(params),
								size: 'full',
								title: btn.attr('title'),
								// on closing modal, reload events in calendar
								close: function() {
									AppGini.Calendar._fullCal.refetchEvents();
								}
							});
						})
						.appendTo(container);

					bottom += 2.5;
				}

				// continue only if new event buttons style doesn't exist
				if($j('#new-event-button-style').length) return;

				$j(
					'<style type="text/css" id="new-event-button-style">' +
						'.new-event {' +
							'padding: 0.5em;' +
							'font-size: 3em;' +
							'border-radius: 50%;' +
							'z-index: 999;' +
							'position: fixed;' +
							'right: 1em;' +
							'box-shadow: 0 0 11px 0px black;' +
							'cursor: pointer;' +
							'opacity: .9;' +
						'}' +
					'</style>'
				).appendTo(container);
			}

		}, // end of AppGini.Calendar.newEventButtons

		fullHeight: function() {
			if(undefined === this._fullCal) return;

			this._fullCal.setOption('height', 'auto');
			this._fullCal.setOption('contentHeight', 'auto');
		},

		compactHeight: function() {
			if(undefined === this._fullCal) return;
			
			this._fullCal.setOption('height', undefined);
			this._fullCal.setOption('contentHeight', undefined);

			if(undefined === this.scrollTime) return;

			this._fullCal.scrollToTime(this.scrollTime);
		},

		/*
		 *	UI translation
		 *	--------------
		 *	Usage:
		 *	Translate the entire UI whenever DOM mutated:
		 *		AppGini.Calendar.Translate.live();
		 *		
		 *	Execute callback when language files are ready:
		 *		AppGini.Calendar.Translate.ready(callback);
		 *	
		 *	Translate the entire UI:
		 *	    AppGini.Calendar.Translate.ui(callback);
		 *	    
		 *	Return translation of a single key, after performing variable replacements:
		 *	    translation = AppGini.Calendar.Translate.word('key', replacements);
		 *	    
		 *	Change language:
		 *		AppGini.Calendar.Translate.setLang(lang);
		 *		// you should then reload page or call AppGini.Calendar.Translate.ui() to apply the new language
		 *	    
		 *	Current language is stored in AppGini.Calendar.selectedLanguage (default is 'en')
		 *	    
		 *	Note: AppGini.Calendar.Translate.word() will fail if called before AppGini.Calendar.Translate.ui()
		*/
		Translate: {
			langPath: '../resources/plugin-calendar/language/',
			live: function() {
				// call only once!
				if(this._liveCalled !== undefined) return;
				this._liveCalled = true;

				var body = $j('body').get(0), config = { attributes: true, childList: true, subtree: true };

				// auto translate UI whenever DOM changes
				var observer = new MutationObserver(function(list, observer) {
					// prevent re-triggering MutationObserver while translating DOM!
					observer.disconnect();

					// translate UI
					AppGini.Calendar.Translate.ui(function() {
						// start observing (again!)
						observer.observe(body, config);
					});
				});

				// trigger MutationObserver
				observer.observe(body, config);
				$j('body').append('<span class="hidden">MutationObserver.trigger()</span>');
			},
			setLang: function(lang) {
				localStorage.setItem('AppGini.Calendar.selectedLanguage', lang);
				AppGini.Calendar.selectedLanguage = lang;
			},
			ui: function(doneCallback) {
				var self = this, parent = AppGini.Calendar;
				
				var error = function(err) { console.error(err); return false; },
					langFile = function(lang) { return self.langPath + lang + '.js';	},
					loadLanguageFiles = function(callback) {
						parent.selectedLanguage = localStorage.getItem('AppGini.Calendar.selectedLanguage') || 'en';

						// If the stored language files are already loaded, execute callback and quit
						if(parent.language !== undefined && parent.language[parent.selectedLanguage] !== undefined) {
							callback();
							return;
						}

						// load default language file (en) ...
						$j.getScript(langFile('en'))
						.fail(function() {
							error('Error loading ' + langFile('en'));
						})
						
						// then configured language file (if not 'en')
						.done(function() {
							self._langFileEn = true;

							if(parent.selectedLanguage == 'en') {
								self._langFile = true;
								callback();
								return;
							}

							$j.getScript(langFile(parent.selectedLanguage))
							.fail(function() {
								error('Error loading ' + langFile(parent.selectedLanguage));
							})

							.done(function() {
								self._langFile = true;
							})

							// we're always calling callback even if selectedLanguage fails to load because 
							// we'd then fallback to English
							.always(callback);
						})
					};

				loadLanguageFiles(function() {
					var els = $j('.language');
					for(var i = 0; i < els.length; i++) {
						var el = els.eq(i);
						var replace = el.data();
						if(undefined === replace.key) continue;

						var translation = self.word(replace.key, replace);
						if(translation !== false) el.html(translation);
					}
					
					// set document direction
					var rtl = parent.language[parent.selectedLanguage].rtl;
					if(rtl === undefined) rtl = false;
					$j('body').css('direction', rtl ? 'rtl' : 'ltr');

					// After translation is done, call doneCallback
					if(typeof(doneCallback) == 'function') {
						doneCallback();
					}
				});
			},
			word: function(key, replace) {
				var self = this, parent = AppGini.Calendar;

				if(undefined === parent.selectedLanguage) return false;
				if(undefined === parent.language[parent.selectedLanguage]) return false;

				// try to find the translation in the selected language
				var translation = parent.language[parent.selectedLanguage][key];
				if(undefined === replace || typeof(replace) !== 'object' || replace.length !== undefined) replace = false;

				// if that fails, try the english translation
				if(undefined === translation) {
					translation = parent.language['en'][key];
					if(undefined === translation) {
						console.error('Translation of ' + key + ' not defined.');
						return false;
					}
				}

				if(false !== replace)
					// replace placeholders with provided replacements ...
					for(var r in replace) {
						if(!replace.hasOwnProperty(r)) continue;
						// do a global (all occurances) replace of %{r}% with corresponding value
						translation = translation.replace(new RegExp('%' + r + '%', 'g'), replace[r]);
					}

				return translation;
			},
			ready: function(callback) {
				var self = this, parent = AppGini.Calendar;
				// poll every 50 msec until language ready
				if(
					self._langFileEn === undefined || 
					self._langFile === undefined
				) {
					setTimeout(function() { self.ready(callback) }, 50);
					return;
				}

				callback();
			}
		} // end of AppGini.Calendar.Translate

	} // end of AppGini.Calendar

})
