$j(function() {
	var runningAjaxRequest = null;
	var cache = {};
	var storedQueries = []; // [{name, query}, ..]

	var getResults = function(options) {
		options = options || {};

		if(runningAjaxRequest !== null) {
			runningAjaxRequest.abort();
			runningAjaxRequest = null;
		}

		var sql = $j('#sql').val();
		if(!validSql(sql)) {
			if(typeof options.complete == 'function') options.complete();
			return noResults();
		}

		if(cache[sql] !== undefined && $j('#useCache').prop('checked')) {
			if(typeof options.complete == 'function') options.complete();
			return showResults(cache[sql]);
		}

		runningAjaxRequest = $j.ajax({
			url: 'ajax-sql.php',
			data: {    sql: sql, csrf_token: $j('#csrf_token').val() },
			beforeSend: function() {
				$j('#sql-error').addClass('hidden');
				if(!$j('#auto-execute').hasClass('active')) noResults();
				$j('#results-loading').removeClass('hidden');
			},
			error: function(xhr) {
				if(xhr.status == 403) {
					// need new csrf token
					$j('#csrf-expired').removeClass('hidden');
					$j('#no-sql-results').addClass('hidden');
					return;
				}

				noResults();
			},
			success: function(resp) {
				cache[sql] = resp;
				showResults(resp);
			},
			complete: function() {
				runningAjaxRequest = null;
				$j('#results-loading').addClass('hidden');
				if(typeof options.complete == 'function')
					options.complete();
			}
		})
	}

	var showResults = function(resp) {
		resetResults();
		if(
			typeof(resp) != 'object' ||
			resp.titles === undefined ||
			resp.data === undefined ||
			!resp.data.length
		) return noResults(resp);

		var thead = $j('#sql-results > table > thead > tr'),
			tbody = $j('#sql-results > table > tbody'),
			tr = null;

		for(var i = 0; i < resp.titles.length; i++) {
			var th = $j('<th>' + resp.titles[i] + '</th>');

			// max length of corresponding data
			var maxDataLength = 0;
			for(var ri = 0; ri < resp.data.length; ri++)
				if(resp.data[ri][i].length > maxDataLength)
					maxDataLength = resp.data[ri][i].length;

			// if data is too wide, offer option to expand/collapse column
			if(maxDataLength > 3 * resp.titles[i].length) {
				var width = resp.titles[i].length * 10 + 20;
				$j('<i class="pull-right text-muted glyphicon glyphicon-menu-left" style="cursor: pointer;"></i>')
					.on('click', function() {
						var me = $j(this),
							index = me.parents('th').index();

						if(me.hasClass('rotate180')) { // collapsed, requesting expansion
							me.removeClass('rotate180');
							$j('tbody > tr > td:nth-child(' + (index + 1) + ')')
								.css({
									'max-width': 'unset',
									overflow: 'auto'
								})                     
						} else { // expanded (default), requesting collapse
							me.addClass('rotate180');
							$j('tbody > tr > td:nth-child(' + (index + 1) + ')')
								.css({
									'max-width': width + 'px',
									overflow: 'hidden'
								})
						}
					})
					.appendTo(th);
			}

			th.appendTo(thead);
		}

		for(var ri = 0; ri < resp.data.length; ri++) {
			tr = $j('<tr></tr>');
			$j('<td class="row-counter">' + (ri + 1) + '</td>').appendTo(tr);

			for(i = 0; i < resp.data[ri].length; i++)
				$j('<td>' + resp.data[ri][i] + '</td>').appendTo(tr);

			tr.appendTo(tbody);
		}

		$j('#sql-results').removeClass('hidden');
		$j('#no-sql-results, #sql-error').addClass('hidden');

		$j('#sql-results-truncated').toggleClass('hidden', resp.data.length != 1000);
	}

	var noResults = function(resp) {
		$j('#sql-results').addClass('hidden');
		$j('#no-sql-results').removeClass('hidden');
		$j('#results-loading').addClass('hidden');

		var hasError = (resp !== undefined && resp.error);
		$j('#sql-error').toggleClass('hidden', !hasError).html(hasError ? resp.error : '');
	}

	var validSql = function(sql) {
		if(sql === undefined) sql = $j('#sql').val();
		$j('#sql-begins-not-with-select').toggleClass('hidden', /^\s*(SELECT|SHOW)\s+/i.test(sql));
		return /^\s*SELECT\s+.*?\s+FROM\s+\S+/i.test(sql) || /^\s*SHOW\s+/i.test(sql);
	}

	var resetResults = function() {
		var table = $j('#sql-results > table');
		table.find('th:not(.row-counter)').remove();
		table.find('tbody > tr').remove();
	}

	var listStoredQueries = function() {
		var list = $j('#manage-queries-dialog .list-group');
		list.empty();

		for(var i = 0; i < storedQueries.length; i++) {
			// add item to list of bookmarks
			$j('<li class="list-group-item">' +
				'<button class="btn btn-link delete-bookmark" type="button" data-id="' + i + '">' +
					'<i class="text-danger glyphicon glyphicon-trash"></i>' +
				'</button>' +
				'<button class="btn btn-link load-bookmark" type="button" data-id="' + i + '">' +
					storedQueries[i].name +
				'</button>' +
			'</li>').appendTo(list);
		}
	}

	var saveStoredQueries = function() {
		$j.ajax({
			url: 'ajax-saved-sql.php',
			data: {
				queries: JSON.stringify(storedQueries),
				csrf_token: $j('#csrf_token').val()
			}
		});
	}

	var cleanName = function(name) { return name.trim().replace(/[^\w \-_$+,;.]/g, ''); }

	$j('#execute').on('click', getResults);

	var autoExecTimeout = null;
	$j('#sql').on('keyup', function() {
		if(!validSql()) return;
		if(!$j('#auto-execute').hasClass('active')) return;

		// auto retrieve results if no typing for 2 seconds
		clearTimeout(autoExecTimeout)
		autoExecTimeout = setTimeout(function() { getResults(); }, 2000);
	});

	$j('#useCache').on('click', function() {
		if(!$j(this).prop('checked'))
			getResults();
	})

	$j('#reset').on('click', function() {
		$j('#sql').val('').focus();
		resetResults();
		noResults();
	})

	// lock/unlock #execute button
	$j('#auto-execute').on('click', function() {
		var enable = $j(this).hasClass('active');
		$j(this).toggleClass('active', !enable);
		$j('#execute').prop('disabled', !enable);  
	})

	// retrieve stored queries
	$j.ajax({
		url: 'ajax-saved-sql.php',
		data: { csrf_token: $j('#csrf_token').val() },
		success: function(resp) {
			storedQueries = resp.length ? JSON.parse(resp) : [];
			if(storedQueries.length === undefined) storedQueries = [];

			// validate and clean storedQueries
			storedQueries = storedQueries.filter(function(i) {
				return typeof i.name == 'string' && typeof i.query == 'string';
			}).map(function(i) {
				return { name: cleanName(i.name), query: i.query }; 
			});

			listStoredQueries();
		}
	});

	// insertables
	$j('.insertable').on('click', function() {
		var sql = $j('#sql'),
			insertable = $j(this).text(),
			selStart = sql[0].selectionStart,
			selEnd = sql[0].selectionEnd,
			txt = sql.val();

		sql.val(txt.substr(0, selStart) + insertable + txt.substr(selEnd));
		sql[0].selectionStart = selStart;
		sql[0].selectionEnd = selStart + insertable.length;
		sql.focus();
	})

	// handle bookmarked queries
		// auto-focus query name on showing bookmark dialog
		$j('#manage-queries-dialog').on('shown.bs.collapse', function() { $j('#save-query-as').val('').focus(); });

		$j('#save-query').on('click', function() {
			var name = cleanName($j('#save-query-as').val());
			if(!name.length) return $j('#save-query-as').val('').focus();

			// if name exists, update
			var nameExists = false;
			storedQueries.map(function(i) {
				if(i.name != name) return;
				i.query = $j('#sql').val();
				nameExists = true;
			});

			if(!nameExists) storedQueries.push({ name: name, query: $j('#sql').val() });
			listStoredQueries();
			saveStoredQueries();

			$j('#save-query-as').val('');
			$j('#save-query-as-dialog').collapse('hide');
		})

		$j('#manage-queries-dialog')
			.on('click', '.delete-bookmark', function() {
				var id = $j(this).data('id');
				storedQueries.splice(id, 1);
				listStoredQueries();
				saveStoredQueries();
			})
			.on('click', '.load-bookmark', function() {
				var id = $j(this).data('id');
				$j('#sql').val(storedQueries[id].query);
				getResults({
					complete: function() {
						$j('#manage-queries-dialog').collapse('hide');
					}
				});
			})
})
