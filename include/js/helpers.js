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

    buildDropdown: function(result, dropdown, emptyMessage, selectedId)
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