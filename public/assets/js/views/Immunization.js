const Immunization = {
  init: function() {
    this.initEvents();
  },

  initEvents: function() {
    const self = this;

    document.getElementById('save-btn')
      .addEventListener('click', function(e) {
        self.save();
      });
  },

  save: async function() {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));
    TrackMessage.clearMessage();

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      document.getElementById('save-btn').disabled = false;
      return;
    }

    const date = document.getElementById('flu-shot-date');

    const params = {
      flu_shot_date: date.value
    }

    const data = await doFetch('post', 'immunization-save', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      document.getElementById('save-btn').disabled = false;
      return;
    }

    TrackMessage.showMessage('success', 'Saved!');
    redirectAfter('my-immunizations', 2000);
  },

  validate: function() {
    const date = document.getElementById('flu-shot-date');

    const errors = [];

    if (date.value == '') {
      errors.push('Please enter the flu shot date');
      date.classList.add('input-error');
    }

    return errors;
  }
}

Immunization.init();