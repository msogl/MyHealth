var Global = Global || {};
Global.url = "";
Global.apikey = "";

function apiInit(url, key)
{
    Global.url = url;
    Global.apikey = key;
}

function getSpecialties()
{
    $('#search-btn').prop('disabled', true);
    $('#specialty').prop('disabled', true);
    $('#specialty').empty();
    $('#specialty').append('<option value="">-- select --</option>');

    if (Global.url == "") {
        document.getElementById('results').innerHTML = "Error retrieving data";
        console.log("API not configured");
        return;
    }

    var jqXHR = $.ajax({
        url: Global.url,
        cache: false,
        async: true,
        dataType: "json",
        data: {
            apikey: Global.apikey,	
            c: "getspecialties",
        }
    });

    jqXHR.done(function(data, textStatus, jqXHR) {
        $('#specialty').prop('disabled', false);
        $('#search-btn').prop('disabled', false);

        if (data && data.results) {

            $('#specialty').append('<option value="PCP">Primary Care Provider</option>');
            
            data.results.forEach(function(specialty) {
                $('#specialty').append('<option value="' + specialty.code + '">' + specialty.specialty + '</option>');
            });
        }
    });

    jqXHR.fail(function(jqXHR, textStatus, errorThrown) {
        $('#specialty').prop('disabled', false);
        $('#search-btn').prop('disabled', false);
        document.getElementById('results').innerHTML = errorThrown;
    });
}

$('#search-btn').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();

    if (Global.url == "") {
        document.getElementById('results').innerHTML = "Error retrieving data";
        console.log("API not configured");
        return;
    }

    var pcp = ($('#specialty').val() == 'PCP' ? 'P' : '');
    var spec = $('#specialty').val();

    $('body').css('cursor','wait');
    $('#results').html('Searching...');

    var gender = '';
    if ($('.gender-container input[name="gender"]:checked').length > 0) {
        gender = $('.gender-container input[name="gender"]:checked').val();
    }

    var pcp = '';
    var specialty = $('#specialty option:selected').val();
    if (specialty == 'PCP') {
        specialty = '';
        pcp = 'Y'
    }

    var jqXHR = $.ajax({
        url: Global.url,
        cache: false,
        async: true,
        dataType: "json",
        data: {
            apikey: Global.apikey,	
            c: "provsearch",
            last: $('#lastname').val(),									// specific Dr. last name or blank
            first: $('#firstname').val(),								// specific Dr. first name or blank
            spec: specialty,											// specific specialty code or blank
            language: $('#language option:selected').val(),				// specific language or blank
            city: $('#city').val(),										// specific city or blank
            zip: $('#zip').val(),										// zip code or blank
            radius: $('#radius option:selected').val(),					// number of miles radius of zip code (only works if there's a zip code)
            pcp: pcp,													// Y if searching for Primary Care Providers, N or blank otherwise
            gender: gender,												// M, F or blank
            morning: ($('#morning-hours').prop('checked') ? 'Y' : ''),	// Y for morning hours, N or blank otherwise
            evening: ($('#evening-hours').prop('checked') ? 'Y' : ''), 	// Y for evening hours, N or blank otherwise
            weekend: ($('#weekend-hours').prop('checked') ? 'Y' : '')	// Y for weekend hours, N or blank otherwise
        }
    });

    jqXHR.done(function(data, textStatus, jqXHR) {
        //console.log(data);
        if (data) {
            $('#results').empty();	
            if (data.error) {
                $('#results').text(data.error);
            }
            else if (data.rows) {
                var record;
                var prov;
                var provname;

                if (data.rows.length > 0) {
                    var prov;
                    var location;

                    let lastSpecialty = '';

                    for (var ix=0; ix<data.rows.length; ix++) {
                        prov = data.rows[ix];
                        record = '';
                        
                        if (ix > 0) {
                            // record += '<br/>';
                        }

                        //console.log(prov);

                        if (lastSpecialty != prov.specialty) {
                            record += `<div class="specialty-header">${prov.specialty}</div>`;
                        }

                        record += '<div class="container">';
                        record += '<p class="header-name">' + prov.fullname+' ';
                        if (prov.organization != '') {
                            record += '<span style="font-size:smaller;">(' + prov.organization + ')</span>';
                        }
                        record += '</p>';
                        record += '<hr/>';

                        if (prov.website != '') {
                            record += '<p class="website"><a href="' + prov.website + '" target="_blank">Visit website</a></p>';
                        }

                        //record += '<p class="header">Specialty: '+prov.specialty+'</p>';

                        record += '<p class="header">Telehealth:</p>';
                        record += (prov.telehealth != '' ? prov.telehealth : 'Call office');

                        var notAcceptingNewPatients = 0;
                        for (var locIx=0; locIx<prov.locations.length; locIx++) {
                            if (prov.locations[locIx].accepting_new_patients == 'NO') {
                                notAcceptingNewPatients++;
                            }
                        }

                        if (notAcceptingNewPatients == prov.locations.length) {
                            record += '<p class="header">*** NOT ACCEPTING NEW PATIENTS AT THIS TIME ***</p>';
                        }

                        if (prov.languages.length > 0) {
                            record += '<p class="header">Languages</p>'
                            for (var langIx=0; langIx<prov.languages.length; langIx++) {
                                if (langIx > 0) {
                                    record += '<br>';
                                }

                                record += prov.languages[langIx];
                            }

                            record += '<br/>';
                        }
                    
                        /*
                        record += 'Board certified? ' + (prov.board_certified == 'Y' ? 'Yes' : 'No') + '<br/>';

                        if (prov.certifications.length > 0) {
                            record += 'Certifications:<br/>'
                            prov.certifications.forEach(function(certification) {
                                record += '&nbsp;&nbsp;&nbsp;&nbsp;' + certification + '<br/>'
                            });
                        }
                        */

                        if (prov.schools.length > 0) {
                            record += '<p class="header">Education</p>'
                            prov.schools.forEach(function(school) {
                                record += school.medical_school + ' (' + school.year_start + ' - ' + school.year_end + ')<br/>'
                            });
                        }

                        record += '<p class="header">Location' + (prov.locations.length > 1 ? 's' : '') + '</p>';

                        for (var locIx=0; locIx<prov.locations.length; locIx++) {
                            var location = prov.locations[locIx];

                            if (locIx > 0) {
                                record += '<br/>';
                            }

                            location.phone = location.phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                            location.fax = location.fax.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');

                            location.mon = location.mon.replace(/-/g, ' - ');
							location.tue = location.tue.replace(/-/g, ' - ');
							location.wed = location.tue.replace(/-/g, ' - ');
							location.thu = location.tue.replace(/-/g, ' - ');
							location.fri = location.tue.replace(/-/g, ' - ');
							location.sat = location.tue.replace(/-/g, ' - ');
							location.sun = location.tue.replace(/-/g, ' - ');
							location.mon2 = location.mon2.replace(/-/g, ' - ');
							location.tue2 = location.tue2.replace(/-/g, ' - ');
							location.wed2 = location.tue2.replace(/-/g, ' - ');
							location.thu2 = location.tue2.replace(/-/g, ' - ');
							location.fri2 = location.tue2.replace(/-/g, ' - ');
							location.sat2 = location.tue2.replace(/-/g, ' - ');
							location.sun2 = location.tue2.replace(/-/g, ' - ');
                            
                            if (notAcceptingNewPatients != prov.locations.length) {
                                if (location.accepting_new_patients == 'NO') {
                                    record += '*** NOT ACCEPTING NEW PATIENTS AT THIS LOCATION ***<br>';
                                }
                            }
                            
                            record += location.address1+(location.address2 != '' ? ' '+location.address2 : '') + '<br/>';
                            record += location.city+', '+location.state+' '+location.zip + '<br/>';
                            record += 'Phone: ' + location.phone+'<br/>'
                            record += 'Fax: ' + location.fax+'<br/>'

                            record += '<p class="header">Office Hours</p>'

                            var hasHours = true;
                            if (location.mon == '' && location.tue == '' && location.wed == '' && location.thu == '' && location.fri == '' && location.sat == '' && location.sun == '' &&
                                location.mon2 == '' && location.tue2 == '' && location.wed2 == '' && location.thu2 == '' && location.fri2 == '' && location.sat2 == '' && location.sun2 == '') {
                                hasHours = false;
                            }

                            if (!hasHours) {
                                record += '<div>Call office for current hours</div>';
                            }
                            else {
                                record += '<div class="office-hours">';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Mon</div>'
                                record += '  <div class="hours">' + location.mon + (location.mon2 != '' ? '<br/>' + location.mon2 : '') + '</div>';
                                record += ' </div>';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Tue</div>'
                                record += '  <div class="hours">' + location.tue + (location.tue2 != '' ? '<br/>' + location.tue2 : '') + '</div>';
                                record += ' </div>';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Wed</div>'
                                record += '  <div class="hours">' + location.wed + (location.wed2 != '' ? '<br/>' + location.wed2 : '') + '</div>';
                                record += ' </div>';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Thu</div>'
                                record += '  <div class="hours">' + location.thu + (location.thu2 != '' ? '<br/>' + location.thu2 : '') + '</div>';
                                record += ' </div>';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Fri</div>'
                                record += '  <div class="hours">' + location.fri + (location.fri2 != '' ? '<br/>' + location.fri2 : '') + '</div>';
                                record += ' </div>';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Sat</div>'
                                record += '  <div class="hours">' + location.sat + (location.sat2 != '' ? '<br/>' + location.sat2 : '') + '</div>';
                                record += ' </div>';
                                record += ' <div class="day-container">';
                                record += '  <div class="day">Sun</div>'
                                record += '  <div class="hours">' + location.sun + (location.sun2 != '' ? '<br/>' + location.sun2 : '') + '</div>';
                                record += ' </div>';
                                record += '</div>';
                            }

                            /*
                            record += '<table border="1" style="border-collapse:collapse;">';
                            record += ' <thead>';
                            record += '  <tr>';
                            record += '   <th style="width:100px;">Mon</th>';
                            record += '   <th style="width:100px;">Tue</th>';
                            record += '   <th style="width:100px;">Wed</th>';
                            record += '   <th style="width:100px;">Thu</th>';
                            record += '   <th style="width:100px;">Fri</th>';
                            record += '   <th style="width:100px;">Sat</th>';
                            record += '   <th style="width:100px;">Sun</th>';
                            record += '  </tr>';
                            record += ' </thead>';
                            record += ' <tbody>';
                            record += '  <tr>';
                            record += '   <td class="center nowrap">' + location.mon + (location.mon2 != '' ? '<br/>' + location.mon2 : '') + '</td>';
                            record += '   <td class="center nowrap">' + location.tue + (location.tue2 != '' ? '<br/>' + location.tue2 : '') + '</td>';
                            record += '   <td class="center nowrap">' + location.wed + (location.wed2 != '' ? '<br/>' + location.wed2 : '') + '</td>';
                            record += '   <td class="center nowrap">' + location.thu + (location.thu2 != '' ? '<br/>' + location.thu2 : '') + '</td>';
                            record += '   <td class="center nowrap">' + location.fri + (location.fri2 != '' ? '<br/>' + location.fri2 : '') + '</td>';
                            record += '   <td class="center nowrap">' + location.sat + (location.sat2 != '' ? '<br/>' + location.sat2 : '') + '</td>';
                            record += '   <td class="center nowrap">' + location.sun + (location.sun2 != '' ? '<br/>' + location.sun2 : '') + '</td>';
                            record += '  </tr>';
                            record += ' </tbody>';
                            record += '</table>';
                            */
                        };

                        record += '</div>';
                        lastSpecialty = prov.specialty;

                        $('#results').append(record);
                    }
                }
                else {
                    $('#results').html('<em>Sorry... no physicians found</em>');
                }
            }
            else {
                $('#results').text('Unexpected response from server');
            }
        }

        scrollWindowTo('#results', 200);
        $('body').css('cursor','default');
    });

    jqXHR.fail(function(jqXHR, textStatus, errorThrown) {
        document.getElementById('results').innerHTML = errorThrown;
        $('body').css('cursor','default');
    });

    return false;	
});

if (typeof String.prototype.startsWith != 'function') {
    String.prototype.startsWith = function (str){
        return this.slice(0, str.length) == str;
    };
}
