<script>
	$j(function(){
		var tn = 'properties';

		/* data for selected record, or defaults if none is selected */
		var data = {
			owner: { id: '<?php echo $rdata['owner']; ?>', value: '<?php echo $rdata['owner']; ?>', text: '<?php echo $jdata['owner']; ?>' }
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		/* saved value for owner */
		cache.addCheck(function(u, d){
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'owner' && d.id == data.owner.id)
				return { results: [ data.owner ], more: false, elapsed: 0.01 };
			return false;
		});

		cache.start();
	});
</script>

