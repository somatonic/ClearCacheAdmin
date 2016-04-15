
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

    });

})(jQuery);