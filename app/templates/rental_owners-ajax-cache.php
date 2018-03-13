<script>
	$j(function(){
		var tn = 'rental_owners';

		/* data for selected record, or defaults if none is selected */
		var data = {
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		cache.start();
	});
</script>

