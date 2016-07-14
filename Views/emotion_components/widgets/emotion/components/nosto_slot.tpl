<div class="nosto_element" id="{$Data.slot_id}"></div>
<script type="text/javascript">
    if (typeof nostojs != 'undefined' && nostojs != null) {
        try {
            nostojs(function (api) {
                api.loadRecommendations()
            });
        } catch (err) {}
    }
</script>