<script>
	$j(function(){
		var tn = 'applications_leases';

		/* data for selected record, or defaults if none is selected */
		var data = {
			tenants: { id: '<?php echo $rdata['tenants']; ?>', value: '<?php echo $rdata['tenants']; ?>', text: '<?php echo $jdata['tenants']; ?>' },
			property: { id: '<?php echo $rdata['property']; ?>', value: '<?php echo $rdata['property']; ?>', text: '<?php echo $jdata['property']; ?>' },
			unit: { id: '<?php echo $rdata['unit']; ?>', value: '<?php echo $rdata['unit']; ?>', text: '<?php echo $jdata['unit']; ?>' }
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		/* saved value for tenants */
		cache.addCheck(function(u, d){
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'tenants' && d.id == data.tenants.id)
				return { results: [ data.tenants ], more: false, elapsed: 0.01 };
			return false;
		});

		/* saved value for property */
		cache.addCheck(function(u, d){
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'property' && d.id == data.property.id)
				return { results: [ data.property ], more: false, elapsed: 0.01 };
			return false;
		});

		/* saved value for unit */
		cache.addCheck(function(u, d){
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'unit' && d.id == data.unit.id)
				return { results: [ data.unit ], more: false, elapsed: 0.01 };
			return false;
		});

		cache.start();
	});
</script>

