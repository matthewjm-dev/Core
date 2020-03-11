jQuery(function ($) {
    'use strict';

    $('.repeater-group .repeater-group-add').on('click', function(e) {
        e.preventDefault();

        var field_group = $(this).siblings('.repeater-group-fields').find('.repeater-group-item:first-child');
        var new_field_group = field_group.clone();

        $.each(new_field_group.find('input, select, textarea'), function(key, field) {
            $(field).val(function() {
                this.defaultValue;
            })
        });

        new_field_group.appendTo('.repeater-group-fields');
    });

    $('.repeater-group').on('click', '.repeater-group-item .repeater-group-item-remove', function() {
       $(this).parents('.repeater-group-item').remove();
    });
});