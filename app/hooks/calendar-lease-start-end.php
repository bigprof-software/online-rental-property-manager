<?php
	define('PREPEND_PATH', '../');
	define('FULLCAL_PATH', PREPEND_PATH . 'resources/fullcalendar/');
	
	$hooks_dir = dirname(__FILE__);
	include("{$hooks_dir}/../defaultLang.php");
	include("{$hooks_dir}/../language.php");
	include("{$hooks_dir}/../lib.php");
	
	include_once("{$hooks_dir}/../header.php");
	
	/* check access */
	$mi = getMemberInfo();
	if(!in_array($mi['group'], array('Admins'))) {
		echo error_message("Access denied");
		include_once("{$hooks_dir}/../footer.php");
		exit;
	}

	?>

	<link href="<?php echo FULLCAL_PATH; ?>core/main.min.css" rel="stylesheet" />
	<link href="<?php echo FULLCAL_PATH; ?>daygrid/main.min.css" rel="stylesheet" />
	<link href="<?php echo FULLCAL_PATH; ?>timegrid/main.min.css" rel="stylesheet" />
	<link href="<?php echo FULLCAL_PATH; ?>list/main.min.css" rel="stylesheet" />
	
	<script src="<?php echo FULLCAL_PATH; ?>core/main.min.js"></script>
	<script src="<?php echo FULLCAL_PATH; ?>core/locales-all.min.js"></script>
	<script src="<?php echo FULLCAL_PATH; ?>interaction/main.min.js"></script>
	<script src="<?php echo FULLCAL_PATH; ?>daygrid/main.min.js"></script>
	<script src="<?php echo FULLCAL_PATH; ?>timegrid/main.min.js"></script>
	<script src="<?php echo FULLCAL_PATH; ?>list/main.min.js"></script>
	<script src="<?php echo PREPEND_PATH; ?>resources/plugin-calendar/calendar-common.js"></script>

	<script>
		$j(function() {
			var Cal = AppGini.Calendar;
			var calId = 'lease-start-end';

			Cal.scrollTime = '08:00:00';

			Cal._fullCal = new FullCalendar.Calendar($j('#' + calId).get(0), {
				plugins: ['interaction', 'dayGrid', 'timeGrid', 'list'],
				customButtons: {
					reload: {
						text: 'Reload',
						icon: 'refresh',
						click: function() {
							Cal._fullCal.refetchEvents();
						}
					}
				},
				header: {
					left: 'prevYear,prev,next,nextYear reload today',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
					eventLimit: true,
				},

				height: 'auto', // https://fullcalendar.io/docs/height
				contentHeight: 'auto', // https://fullcalendar.io/docs/contentHeight
				aspectRatio: 2.5, // https://fullcalendar.io/docs/aspectRatio

				defaultDate: Cal.urlDate('[today]'),
				defaultView: Cal.urlView('dayGridMonth'),
				views: {
					dayGridMonth: {
						eventLimit: 5,
						eventLimitClick: 'day'
					},
					timeGridWeek : {
						nowIndicator: true,
						scrollTime: Cal.scrollTime
					},
					timeGridDay: {
						nowIndicator: true,
						scrollTime: Cal.scrollTime
					}
				},

				eventSources: [
					{
						url: 'calendar-events-end-of-lease.json.php',
						failure: function() {
							$j('#' + calId + '-events-loading-error').removeClass('hidden');
						}
					},
					{
						url: 'calendar-events-start-of-lease.json.php',
						failure: function() {
							$j('#' + calId + '-events-loading-error').removeClass('hidden');
						}
					},
				],
				eventRender: function (e) {
					switch(e.view.type) {
						case 'dayGridMonth':
						case 'timeGridWeek':
						case 'timeGridDay':
							// this is necessary to render HTML titles, 
							// https://github.com/fullcalendar/fullcalendar/issues/2919#issuecomment-459909185
							e.el.firstChild.innerHTML = e.event.title;
							break;
						case 'listWeek':
							e.el.lastChild.firstChild.innerHTML = e.event.title;
							break;
					}
				},
				eventClick: function(e) {
					e.jsEvent.preventDefault();
					if(e.event.url) {
						// strip html from title, and shorten to 100 chars max
						var title = $j('<span>' + e.event.title + '</span>').text(),
							maxChars = 100;
						if(title.length > maxChars) title = title.substr(0, maxChars - 3) + '...';

						modal_window({
							url: e.event.url,
							size: 'full',
							title: title,
							// on closing modal, reload events in calendar
							close: function() {
								e.view.calendar.refetchEvents();
							}
						});
					}
				},

				/* Adding new events */
				selectable: true,
				select: function(i) {
					Cal.newEventButtons.show(i);
				},
				unselect: function(e) {
					Cal.newEventButtons.hide();
				},
				
				fixedWeekCount: false,
				loading: function(isLoading) {
					var viewCont = $j('.fc-view-container');
					if(isLoading) {
						$j('#' + calId + '-loading')
							.removeClass('hidden')
							.offset({ top: viewCont.length ? viewCont.offset().top : null });
						$j('#' + calId + '-events-loading-error').addClass('hidden');
						return;
					}

					// finished loading
					(function(view) {
						setTimeout(function() {
							Cal.fullCalendarFixes('#' + calId, view);
							$j('#' + calId + '-loading').addClass('hidden');
						}, 100)
					})(this.view);
				},
				datesRender: function(i) {
					Cal.fullCalendarFixes('#' + calId, i.view);
					Cal.updateUrlDate(moment(this.getDate()).format('YYYY-MM-DD'), i.view.type);

					switch(i.view.type) {
						case 'dayGridMonth':
						case 'listWeek':
							setTimeout(Cal.fullHeight, 5);
							break;
						case 'timeGridWeek':
						case 'timeGridDay':
							setTimeout(Cal.compactHeight, 5);
							break;
					}
				},
				viewSkeletonRender: function(i) {
					Cal.fullCalendarFixes('#' + calId, i.view);
				}
			});
			Cal._fullCal.render();

			Cal.fullCalendarBootstrapize('#' + calId);
			Cal.Translate.ready(function() {
				Cal.newEventButtons.create([
					{
						type: 'end-of-lease',
						color: 'danger',
						title: AppGini.Calendar.Translate.word('new_x', { event: 'end of lease' }),
						table: 'applications_leases'
					},
					{
						type: 'start-of-lease',
						color: 'success',
						title: AppGini.Calendar.Translate.word('new_x', { event: 'start of lease' }),
						table: 'applications_leases'
					},
				]);
			});

			$j('#' + calId + '-events-loading-error').click(function() {
				Cal._fullCal.refetchEvents();
			});

			if($j('div.hidden-print').length == 3) $j('div.hidden-print').eq(1).remove();
			AppGini.Calendar.Translate.live();
		})
	</script>

	<div class="page-header"><h1>
		<img src="<?php echo PREPEND_PATH; ?>resources/table_icons/calendar.png">
		Leases starting/ending				</h1></div>

	<div 
		id="lease-start-end-loading" 
		class="hidden alert alert-info text-center" 
		style="width: 88%; height: 70vh; position: fixed; z-index: 500; top: 26vh;"
	>
		<img src="<?php echo PREPEND_PATH; ?>loading.gif"> <span class="language" data-key="please_wait"></span>
	</div>
	<div id="lease-start-end-events-loading-error" class="hidden alert alert-warning text-center">
		<?php echo $Translation['Connection error']; ?>
		<button type="button" class="btn btn-warning reload-calendar"><i class="glyphicon glyphicon-refresh"></i></button>
	</div>
	<div id="lease-start-end"></div>

	<?php
	include_once("{$hooks_dir}/../footer.php");
