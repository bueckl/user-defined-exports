(function($){

    $.entwine('ss', function($){

        $('.user-defined-export-button').entwine({
            onchange: function () {
                console.log('..on change called...');
                var option = $('option:selected', this).val();
                console.log(option);
                $('.js_export_button').attr('data-exportbid', option);
            }
        });


        $('.ss-gridfield .js_export_button').entwine({
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