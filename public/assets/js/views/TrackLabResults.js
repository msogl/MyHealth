const TrackLabResults = {
  init: function () {
    this.initEvents();
  },

  initEvents: function () {
    const self = this;

    document.getElementById('save-btn')
      .addEventListener('click', function (e) {
        this.disabled = true;
        self.save();
      });
  },

  save: async function () {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));
    TrackMessage.clearMessage();

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      document.getElementById('save-btn').disabled = false;
      return;
    }

    const date = document.getElementById('lab-date');
    const ldl = document.getElementById('ldl');
    const hdl = document.getElementById('hdl');
    const triglycerides = document.getElementById('triglycerides');
    const cholesterol = document.getElementById('cholesterol');
    const hba1c = document.getElementById('hba1c');
    const glucose = document.getElementById('glucose');

    const params = {
      lab_date: date.value,
      ldl: ldl.value,
      hdl: hdl.value,
      triglycerides: triglycerides.value,
      cholesterol: cholesterol.value,
      hba1c: hba1c.value,
      glucose: glucose.value,
    }

    const data = await doFetch('post', 'track-lab-results-save', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      document.getElementById('save-btn').disabled = false;
      return;
    }

    TrackMessage.showMessage('success', 'Saved!');
    redirectAfter('my-vital-stats', 2000);
  },

  validate: function() {
    const date = document.getElementById('lab-date');
    const ldl = document.getElementById('ldl');
    const hdl = document.getElementById('hdl');
    const triglycerides = document.getElementById('triglycerides');
    const cholesterol = document.getElementById('cholesterol');
    const hba1c = document.getElementById('hba1c');
    const glucose = document.getElementById('glucose');

    const errors = [];

    if (date.value == '') {
      errors.push('Date is required');
      date.classList.add('input-error');
    }

    if (ldl.value != '' && isNaN(ldl.value)) {
      errors.push('LDL value must be a number');
      ldl.classList.add('input-error');
    }

    if (hdl.value != '' && isNaN(hdl.value)) {
      errors.push('HDL value must be a number');
      hdl.classList.add('input-error');
    }

    if (triglycerides.value != '' && isNaN(triglycerides.value)) {
      errors.push('Triglycerides must must be a number');
      triglycerides.classList.add('input-error');
    }

    if (cholesterol.value != '' && isNaN(cholesterol.value)) {
      errors.push('Total Cholesterol value must be a number');
      cholesterol.classList.add('input-error');
    }

    if (hba1c.value != "" && isNaN(hba1c.value)) {
      errors.push('HbA1c value must be a number');
      hba1c.classList.add('input-error');
    }

    if (glucose.value != "" && isNaN(glucose.value)) {
      errors.push('Glucose value must be a number');
      glucose.classList.add('input-error');
    }

    if (
      ldl.value == '' &&
      hdl.value == '' &&
      triglycerides.value == '' &&
      cholesterol.value == '' &&
      hba1c.value == '' &&
      glucose.value == ''
    ) {
      ldl.classList.add('input-error');
      hdl.classList.add('input-error');
      triglycerides.classList.add('input-error');
      cholesterol.classList.add('input-error');
      hba1c.classList.add('input-error');
      glucose.classList.add('input-error');
      errors.push('No data to save.')
    }

    return errors;
  }
}

TrackLabResults.init();