/*
 * Copyright (c) 2019 Dan Carroll
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

var helpers =
{
    cleanData: function(type, data) {
        var newData = new Array();
        switch(type) {
            case "Pilot":
                $.each(data, function (i, v) {
                    var blFound = false;
                    for (var o in newData) {
                        if (newData[o].id === v["pilotId"]) {
                            blFound = true;
                            continue;
                        }
                    }
                    if (!blFound) {
                        newData.push(
                            {
                                id: v["pilotId"],
                                name: v["fullName"]
                            }
                        );
                    }
                });
                break;
            case "Round":
                $.each(data, function (i, v) {
                    newData.push(
                        {
                            id: v["roundId"],
                            name: (v["imacClass"] + " " + v["imacType"] + " Round " + v["roundNum"] + (v["sequences"] == "1" ? " (Single)" : " (Double)"))
                        }
                    );
                });
                break;
        }
        return newData;
    },

    buildDropdownWithMessage: function(result, dropdown, emptyMessage, selectedId)
    {
        dropdown.html('');
        if (emptyMessage !== null) {
            dropdown.append('<option value="">' + emptyMessage + '</option>');
        }
        if(typeof result !== 'undefined' && result !== '' && result !== null)
        {
            $.each(result, function(k, v) {
                if (v.id == selectedId) {
                    dropdown.append('<option selected value="' + v.id + '">' + v.name + '</option>');
                } else {
                    dropdown.append('<option value="' + v.id + '">' + v.name + '</option>');
                }
            });
        }
    },

    buildDropdownWithDefaultValue: function(result, dropdown, emptyMessage, emptyValue, selectedId)
    {
        dropdown.html('');
        if (emptyMessage !== null) {
            dropdown.append('<option value="' + emptyValue + '">' + emptyMessage + '</option>');
        }
        if(typeof result !== 'undefined' && result !== '' && result !== null)
        {
            $.each(result, function(k, v) {
                if (v.id == selectedId) {
                    dropdown.append('<option selected value="' + v.id + '">' + v.name + '</option>');
                } else {
                    dropdown.append('<option value="' + v.id + '">' + v.name + '</option>');
                }
            });
        }
    },

    emptyDropdown: function(dropdown, emptyMessage)
    {
        dropdown.html('');
        if (emptyMessage !== null) {
            dropdown.append('<option value="">' + emptyMessage + '</option>');
        }
    },

    getFormData: function (form){
        var unindexed_array = form.serializeArray();
        var indexed_array = {};

        $.map(unindexed_array, function(n, i){
            indexed_array[n['name']] = n['value'];
        });

        return indexed_array;
    }
}