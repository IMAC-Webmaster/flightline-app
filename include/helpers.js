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
    buildDropdown: function(result, dropdown, emptyMessage)
    {
        dropdown.html('');
        if (emptyMessage !== null) {
            dropdown.append('<option value="">' + emptyMessage + '</option>');
        }
        if(typeof result !== 'undefined' && result !== '')
        {
            $.each(result, function(k, v) {
                dropdown.append('<option value="' + v.id + '">' + v.name + '</option>');
            });
        }
    }
}