const DoctorSearch = {
  init: function () {
    this.initEvents();
    setDefaultButton('search-btn');
  },

  initEvents: function () {
    const self = this;

    document.getElementById('search-btn').addEventListener('click', function (e) {
      self.search();
    });
  },

  getSpecialties: async function () {
    TrackMessage.clearMessage();

    const searchButton = document.getElementById('search-btn');
    const specialtyElem = document.getElementById('specialty');
    const resultsElem = document.getElementById('results');

    searchButton.disabled = true;
    specialtyElem.disabled = true;
    specialtyElem.innerHTML = '<option value="">-- select --</option>';
    resultsElem.innerHTML = '';
    
    const params = 'c=getspecialties';
    const data = await doFetch('GET', 'doctor-search', params);

    specialtyElem.disabled = false;
    searchButton.disabled = false;

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      return;
    }

    specialtyElem.innerHTML += '<option value="PCP">Primary Care Provider</option>';

    data.response.results.forEach(function (specialty) {
      specialtyElem.innerHTML += `<option value="${specialty.code}">${specialty.specialty}</option>`;
    });
  },

  search: async function () {
    TrackMessage.clearMessage();

    const searchButton = document.getElementById('search-btn');
    const specialtyElem = document.getElementById('specialty');
    const resultsElem = document.getElementById('results');

    searchButton.disabled = true;
    resultsElem.innerHTML = '';

    wait();
    resultsElem.innerHTML = 'Searching...';

    const params = {
      c: 'provsearch',
      last: document.getElementById('lastname').value,
      first: document.getElementById('firstname').value,
      spec: (specialtyElem.value == 'PCP' ? '' : specialtyElem.value),
      language: document.getElementById('language').value,
      city: document.getElementById('city').value,
      zip: document.getElementById('zip').value,
      radius: document.getElementById('radius').value,
      pcp: (specialtyElem.value == 'PCP' ? 'Y' : ''),
      gender: document.querySelector('.gender-container input[name="gender"]:checked') ?? '',
      morning: (document.getElementById('morning-hours').checked ? 'Y' : ''),
      evening: (document.getElementById('evening-hours').checked ? 'Y' : ''),
      weekend: (document.getElementById('weekend-hours').checked ? 'Y' : '')
    }
    const data = await doFetch('GET', 'doctor-search', params);

    removeWait();
    searchButton.disabled = false;

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      resultsElem.innerText = data.error;
      return;
    }

    if (!data.response.rows || data.response.rows.length == 0) {
      resultsElem.innerHTML = '<em>Sorry... no physicians found</em>';
      return;
    }

    let record;
    let location;
    let lastSpecialty = '';

    data.response.rows.forEach(function(prov) {
      record = '';

      if (lastSpecialty != prov.specialty) {
        record += `<div class="specialty-header">${prov.specialty}</div>`;
      }

      record += '<div class="container">';
      record += '<p class="header-name">' + prov.fullname + ' ';
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
      for (let locIx = 0; locIx < prov.locations.length; locIx++) {
        if (prov.locations[locIx].accepting_new_patients == 'NO') {
          notAcceptingNewPatients++;
        }
      }

      if (notAcceptingNewPatients == prov.locations.length) {
        record += '<p class="header">*** NOT ACCEPTING NEW PATIENTS AT THIS TIME ***</p>';
      }

      if (prov.languages.length > 0) {
        record += '<p class="header">Languages</p>'
        for (let langIx = 0; langIx < prov.languages.length; langIx++) {
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
        prov.schools.forEach(function (school) {
          record += school.medical_school + ' (' + school.year_start + ' - ' + school.year_end + ')<br/>'
        });
      }

      record += '<p class="header">Location' + (prov.locations.length > 1 ? 's' : '') + '</p>';

      for (var locIx = 0; locIx < prov.locations.length; locIx++) {
        location = prov.locations[locIx];

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

        record += location.address1 + (location.address2 != '' ? ' ' + location.address2 : '') + '<br/>';
        record += location.city + ', ' + location.state + ' ' + location.zip + '<br/>';
        record += 'Phone: ' + location.phone + '<br/>'
        record += 'Fax: ' + location.fax + '<br/>'

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

      resultsElem.innerHTML += record;
    });

    scrollWindowTo('#results', 200);
  }
}

DoctorSearch.init();