

/* Inserted by Calendar plugin on 2020-11-19 12:27:41 */
(function($j) {
	var urlParam = function(param) {
		var url = new URL(window.location.href);
		return url.searchParams.get(param);
	};

	var setDate = function(dateField, date, time) {
		var dateEl = $j('#' + dateField);
		if(!dateEl.length) return; // no date field present

		var d = date.split('-').map(parseFloat).map(Math.floor); // year-month-day
		
		// if we have a date field with day and month components
		if($j('#' + dateField + '-mm').length && $j('#' + dateField + '-dd').length) {
			dateEl.val(d[0]);
			$j('#' + dateField + '-mm').val(d[1]);
			$j('#' + dateField + '-dd').val(d[2]);
			return;
		}

		// for datetime fields that have datetime picker, populate with formatted date and time
		if(dateEl.parents('.datetimepicker').length == 1) {
			dateEl.val(
				moment(date + ' ' + time).format(AppGini.datetimeFormat('dt'))
			);
			return;
		}

		// otherwise, try to populate date and time as-is
		dateEl.val(date + ' ' + time);
	};

	$j(function() {
		// continue only if this a new record form
		if($j('[name=SelectedID]').val()) return;

		var params = ['newEventType', 'startDate', 'startTime', 'endDate', 'endTime', 'allDay'], v = {};
		for(var i = 0; i < params.length; i++)
			v[params[i]] = urlParam('calendar.' + params[i]);

		// continue only if we have a newEventType param
		if(v.newEventType === null) return;

		// continue only if event start and end specified
		if(v.startDate === null || v.endDate === null) return;

		// adapt event data types
		v.allDay = JSON.parse(v.allDay);
		v.start = new Date(v.startDate + ' ' + v.startTime);
		v.end = new Date(v.endDate + ' ' + v.endTime);

		// now handle various event types, populating the relevent fields
		switch(v.newEventType) {
			case 'end-of-lease':
				setDate('end_date', v.startDate, v.startTime);
				break;
			case 'start-of-lease':
				setDate('start_date', v.startDate, v.startTime);
				break;
		}

		// finally, trigger user-defined event handlers
		$j(function() { 
			$j(document).trigger('newCalendarEvent', [v]); 
		})
	});
})(jQuery);
/* End of Calendar plugin code */

