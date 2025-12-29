const TrackGlucose = {
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

    const date = document.getElementById('reading-date');
    const diabetesType = document.querySelector('input[name="diabetes_type"]:checked');
    const glucose = document.getElementById('glucose');
    const timeOfDay = document.getElementById('time-of-day');
    const fasting = document.querySelector('input[name="fasting"]:checked');
    const comments = document.getElementById('comments');

    const params = {
      reading_date: date.value,
      diabetes_type: diabetesType.value,
      glucose: glucose.value,
      time_of_day: timeOfDay.value,
      fasting: fasting.value,
      comments: comments.value,
    }

    const data = await doFetch('post', 'track-glucose-save', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      document.getElementById('save-btn').disabled = false;
      return;
    }

    TrackMessage.showMessage('success', 'Saved!');
    redirectAfter('my-vital-stats', 2000);
  },

  validate: function() {
    const date = document.getElementById('reading-date');
    const glucose = document.getElementById('glucose');
    const timeOfDay = document.getElementById('time-of-day');

    const errors = [];

    if (document.querySelector('input[name="diabetes_type"]:checked') === null) {
			errors.push('Please select which type of diabetes you have');
      document.querySelectorAll('input[name="diabetes_type"]').forEach(elem => {
        elem.classList.add('input-error');
        if (elem.parentNode.nodeName === 'LABEL') {
          elem.parentNode.classList.add('input-error');
        }
      });
		}

		if (glucose.value == '') {
			errors.push('Please enter the glucose reading');
			glucose.classList.add('input-error');
		}
		else if (isNaN(glucose.value)) {
			errors.push('Glucose value should be a number');
			glucose.classList.add('input-error');
		}

		if (date.value == '') {
			errors.push('Please enter the reading date');
			date.classList.add('input-error');
		}

		if (timeOfDay.value == '') {
			errors.push('Please select time of day');
			timeOfDay.classList.add('input-error');
		}

		if (document.querySelector('input[name="fasting"]:checked') === null) {
			errors.push('Please select fasting or non-fasting');
      document.querySelectorAll('input[name="fasting"]').forEach(elem => {
        elem.classList.add('input-error');
        if (elem.parentNode.nodeName === 'LABEL') {
          elem.parentNode.classList.add('input-error');
        }
      });
		}

    return errors;
  }
}

TrackGlucose.init();