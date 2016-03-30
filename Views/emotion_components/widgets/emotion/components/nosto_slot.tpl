<div class="nosto_element" id="{$Data.slot_id}"></div>
<script>
if (nostojs !== null) {
		try {
				nostojs(function(api) {
						api.loadRecommendations('{$Data.slot_id}')
				});
		} catch(err) { }
}
</script>