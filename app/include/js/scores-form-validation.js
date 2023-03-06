/*
 * Copyright (c) 2020 Dan Carroll
 *
 * This file is part of FlightLine.
 *
 * FlightLine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * FlightLine is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FlightLine.  If not, see <https://www.gnu.org/licenses/>.
 */

// Wait for the DOM to be ready

function validateScoreForm() {
    let form_valid = true;

    // Always allow undo/deleted to be pressed.
    if ($('input[name=button]').val() === "undo" || $('input[name=button]').val() == "delete")
        return true;

    if ($('#form_editscore #score').val() === "") {
        $('#form_editscore #score').parent('.field_container').addClass('error');
        $('#form_editscore #score-error').text("Score cannot be empty.").show();
        form_valid = false;

    }

    if (form_valid === true) {
        // Anything else to do before submit?
    }

    return form_valid;
}

$(function() {
    // Initialize form validation on the registration form.
    // It has the name attribute "registration"
    jQuery.validator.setDefaults({
        ignore: [],
    });

    jQuery.validator.addMethod("validIMACScore", function(value, element) {
        let valid = false;
        switch (value) {
            case "0.0":
            case "0":
            case "0.5":
            case "1.0":
            case "1":
            case "1.5":
            case "2.0":
            case "2":
            case "2.5":
            case "3.0":
            case "3":
            case "3.5":
            case "4.0":
            case "4":
            case "4.5":
            case "5.0":
            case "5":
            case "5.5":
            case "6.0":
            case "6":
            case "6.5":
            case "7.0":
            case "7":
            case "7.5":
            case "8.0":
            case "8":
            case "8.5":
            case "9.0":
            case "9":
            case "9.5":
            case "10":
            case "10.0":
                valid = true;
                break;
            default:
                valid = false;
                break;
        }

        return this.optional(element) || valid;

    }, "Score must be between 0 and 10 in 0.5 increments");

    jQuery.validator.addMethod("zeroWithBreak", function(value, element) {
        let valid = true;
        let score  = parseFloat($('input[name=score]').val());
        if (value != "0" && score !== 0 ) {
            valid = false;
        }

        return this.optional(element) || valid;
    }, "Breaks are only valid with zeros.");


    $("form[id='form_editscore']").validate({
        // Specify validation rules
        rules: {
            // The key name on the left side is the name attribute
            // of an input field. Validation rules are defined
            // on the right side
            score: {
                required: true,
                validIMACScore: true
            },
            cdcomment: {
                required: false
            },
            breakFlag: {
                zeroWithBreak: true
            }
        },
        // Specify validation error messages
        messages: {
            score: {
                required: "A score cannot be blank.",
                validIMACScore: "Should be a correct imac score.",
            },
            cdcomment: "Please enter a reason for the adjustment.",
            password: {
                required: "Please provide a password",
                minlength: "Your password must be at least 5 characters long"
            },
            email: "Please enter a valid email address"
        },
        // Make sure the form is submitted to the destination defined
        // in the "action" attribute of the form when valid
        submitHandler: function(e) {
            //e.preventDefault();

            if (validateScoreForm()){
                // Send score information to database
                //hide_ipad_keyboard();
                //hide_lightbox();
                show_loading_message();
                let formObject = helpers.getFormData($('#form_editscore'));
                let formMethod = 'get';
                let action = $('input[name=button]').val();
                //action = 'save';

                switch(action) {
                    case 'undo':  //delete the adjustment.
                        formMethod = 'delete';
                        break;
                    case 'delete':  //delete the score.
                        formMethod = 'post';
                        break;
                    default:
                        formMethod = 'post';
                        if (!validateScoreForm()) {
                            return;
                        }
                        break;
                }
                var request   = $.ajax({
                    url:            "/api/1/sheets/" + formObject.sheetId + "/" + formObject.figureNum + "/adjustment",
                    cache:          false,
                    data:           JSON.stringify(formObject),
                    dataType:       'json',
                    contentType:    'application/json; charset=utf-8',
                    type:           formMethod
                });
                request.done(function(output){
                    if (output.result === 'success'){
                        // Reload datable
                        $('.lightbox_close').click();
                        hide_loading_message();
                        show_message(output.message, 'success');
                        $('#reload').click();
                        // Should we reload the table?   Probably...
                        //table.ajax.reload(function(){
                        //    hide_loading_message();
                        //    show_message("Score X edited successfully." + output.message, 'success');
                        //}, true);
                    } else {
                        hide_loading_message();
                        show_message('Edit request failed: ' + output.message, 'error');
                    }
                });
                request.fail(function(jqXHR, textStatus){
                    hide_loading_message();
                    show_message('Edit request failed: ' + textStatus, 'error');
                });
            }
        }
    });
});