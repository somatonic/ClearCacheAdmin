
/**
 * ClearCacheAdmin JS
 */
(function($){

    $(function(){

        // toggle all checkboxed
        $('input.toggle_all').on("change", function(e){
            var that = $(this);
            var $checkboxes = $("#" + that.data("target")).find("input[type=checkbox]");
            if($checkboxes.length){
                that.is(":checked") ? $checkboxes.prop("checked", "checked") : $checkboxes.prop("checked", "");
            }
        });

        var $submitFiles = $("#wrap_Inputfield_submitfiles").hide();
        $('#clearcachedirs').on("change", function(){
            var $checkboxes = $(this).find("input[type=checkbox]:checked");
            if($checkboxes.length){
                $submitFiles.fadeIn();
            } else {
                $submitFiles.fadeOut();
            }
        });

        var $submitWireCache = $("#wrap_Inputfield_submitwirecache").hide();
        $('#clearwirecache').on("change", function(){
            var $checkboxes = $(this).find("input[type=checkbox]:checked");
            if($checkboxes.length){
                $submitWireCache.fadeIn();
            } else {
                $submitWireCache.fadeOut();
            }
        });
    });

})(jQuery);
