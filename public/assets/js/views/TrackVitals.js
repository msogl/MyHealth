const TrackVitals = {
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

    const date = document.getElementById('vitals-date');
    const systolic = document.getElementById('systolic');
    const diastolic = document.getElementById('diastolic');
    const weight = document.getElementById('weight');
    const feet = document.getElementById('feet');
    const inches = document.getElementById('inches');

    const params = {
      vitals_date: date.value,
      systolic: systolic.value,
      diastolic: diastolic.value,
      weight: weight.value,
      feet: feet.value,
      inches: inches.value,
    }

    const data = await doFetch('post', 'track-vitals-save', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      document.getElementById('save-btn').disabled = false;
      return;
    }

    TrackMessage.showMessage('success', 'Saved!');
    redirectAfter('my-vital-stats', 2000);
  },

  validate: function() {
    const date = document.getElementById('vitals-date');
    const systolic = document.getElementById('systolic');
    const diastolic = document.getElementById('diastolic');
    const weight = document.getElementById('weight');
    const feet = document.getElementById('feet');
    const inches = document.getElementById('inches');

    const errors = [];

    if (date.value == '') {
      errors.push('Date is required');
      date.classList.add('input-error');
    }

    if (systolic.value != '' && isNaN(systolic.value)) {
      errors.push('Systolic value must be a number');
      systolic.classList.add('input-error');
    }

    if (diastolic.value != '' && isNaN(diastolic.value)) {
      errors.push('Diastolic value must be a number');
      diastolic.classList.add('input-error');
    }

    if (systolic.value == '' && diastolic.value != '') {
      errors.push('Missing systolic value');
      systolic.classList.add('input-error');
    }

    if (systolic.value != '' && diastolic.value == '') {
      errors.push('Missing diastolic value');
      diastolic.classList.add('input-error');
    }

    if (!isNaN(systolic.value) && !isNaN(diastolic.value)) {
      if (parseInt(diastolic.value) > parseInt(systolic.value)) {
        errors.push('The diastolic number must not be larger than the systolic number');
        systolic.classList.add('input-error');
        diastolic.classList.add('input-error');
      }
    }

    if (weight.value != '' && isNaN(weight.value)) {
      errors.push('Weight value must be a number');
      weight.classList.add('input-error');
    }

    if (feet.value != '' && isNaN(feet.value)) {
      errors.push('Height (feet) value must be a number');
      feet.classList.add('input-error');
    }

    if (inches.value != "" && isNaN(inches.value)) {
      errors.push('Height (inches) value must be a number');
      inches.classList.add('input-error');
    }

    if (inches.value != '' && !isNaN(inches.value)) {
      if (feet.value == '' && inches.value > 11) {
        this.populateHeight(inches.value);
      }
      else if (inches.value > 11) {
        errors.push('Height must be expressed in feet and inches (up to 11 inches) or just enter the total height in inches and leave feet blank');
        feet.classList.add('input-error');
        inches.classList.add('input-error');
      }
    }

    if (
      systolic.value == '' &&
      diastolic.value == '' &&
      weight.value == '' &&
      feet.value == '' &&
      inches.value == ''
    ) {
      systolic.classList.add('input-error');
      diastolic.classList.add('input-error');
      weight.classList.add('input-error');
      feet.classList.add('input-error');
      inches.classList.add('input-error');
      errors.push('No data to save.')
    }

    return errors;
  },

  /**
   * Convert height in inches to feet and inches
   * @param {float} inches 
   */
  populateHeight(inches) {
    if (inches == 0) {
      return;
    }

    const feet = Math.floor(Math.round(inches) / 12);
    inches = inches % 12;
    document.getElementById('feet').value = feet;
    document.getElementById('inches').value = inches;
  }
}

TrackVitals.init();