(function($){

    $.entwine('ss', function($){

        $('.user-defined-export-button').entwine({
            onchange: function () {
                var option = $('option:selected', this).val();
                $('.js_export_button').attr('data-exportbid', option);
            }
        });


        $('#Form_EditForm_action_userdefinedexport').entwine({
            onclick: function(e){
                var exportID = $('.js_export_button').attr('data-exportbid');
                if(exportID > 0) {
                    window.location.href = this.actionurl()+'&exportbutton='+exportID;
                    e.preventDefault();
                    return false;
                }
                return false;
            }
        });



    });

})(jQuery);