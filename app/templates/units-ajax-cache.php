<?php
	$rdata = array_map('to_utf8', array_map('safe_html', array_map('html_attr_tags_ok', $rdata)));
	$jdata = array_map('to_utf8', array_map('safe_html', array_map('html_attr_tags_ok', $jdata)));
?>
<script>
	$j(function() {
		var tn = 'units';

		/* data for selected record, or defaults if none is selected */
		var data = {
			property: <?php echo json_encode(['id' => $rdata['property'], 'value' => $rdata['property'], 'text' => $jdata['property']]); ?>,
			country: <?php echo json_encode($jdata['country']); ?>,
			street: <?php echo json_encode($jdata['street']); ?>,
			city: <?php echo json_encode($jdata['city']); ?>,
			state: <?php echo json_encode($jdata['state']); ?>,
			postal_code: <?php echo json_encode($jdata['postal_code']); ?>
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		/* saved value for property */
		cache.addCheck(function(u, d) {
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'property' && d.id == data.property.id)
				return { results: [ data.property ], more: false, elapsed: 0.01 };
			return false;
		});

		/* saved value for property autofills */
		cache.addCheck(function(u, d) {
			if(u != tn + '_autofill.php') return false;

			for(var rnd in d) if(rnd.match(/^rnd/)) break;

			if(d.mfk == 'property' && d.id == data.property.id) {
				$j('#country' + d[rnd]).html(data.country);
				$j('#street' + d[rnd]).html(data.street);
				$j('#city' + d[rnd]).html(data.city);
				$j('#state' + d[rnd]).html(data.state);
				$j('#postal_code' + d[rnd]).html(data.postal_code);
				return true;
			}

			return false;
		});

		cache.start();
	});
</script>

