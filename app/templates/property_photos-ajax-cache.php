<script>
	$j(function(){
		var tn = 'property_photos';

		/* data for selected record, or defaults if none is selected */
		var data = {
			property: <?php echo json_encode(array('id' => $rdata['property'], 'value' => $rdata['property'], 'text' => $jdata['property'])); ?>
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		/* saved value for property */
		cache.addCheck(function(u, d){
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'property' && d.id == data.property.id)
				return { results: [ data.property ], more: false, elapsed: 0.01 };
			return false;
		});

		cache.start();
	});
</script>

