const MyAccount = {
  init: function() {
    this.initEvents();
  },

  initEvents: function() {
    const self = this;

    document.getElementById('update-btn')?.addEventListener('click', function(e) {
      self.saveMyAccount();
    });

    document.querySelector('.switch').addEventListener('click', function(e) {
      e.preventDefault();
      const parent = this.closest('.switch-setting');

      if (this.classList.contains('on')) {
        parent.classList.remove('on');
        this.classList.remove('on');
        this.querySelector('input[type="checkbox"]').checked = false;
      }
      else {
        parent.classList.add('on');
        this.classList.add('on');
        this.querySelector('input[type="checkbox"]').checked = true;
      }
    });
    
    document.querySelector('a[href="change-password"]').addEventListener('click', function(e) {
      e.preventDefault();
      redirect(`change-password?meta=${encodeURIComponent(App.meta)}&token=${App.CSRFToken}`);
    });
  },

  saveMyAccount: async function() {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));
    TrackMessage.clearMessage();

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      document.getElementById('update-btn').disabled = false;
      return;
    }

    const email = document.getElementById('email');
    const nickname = document.getElementById('nickname');
    const mfa = document.getElementById('mfa');

    const params = {
      email: email.value,
      nickname: nickname.value,
      mfa: (mfa.checked ? 1 : 0),
    }

    const data = await doFetch('post', 'my-account-save', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      document.getElementById('update-btn').disabled = false;
      return;
    }

    TrackMessage.showMessage('success', 'Saved!');
    redirectAfter(data.response.next, 2000);
  },

  validate: function() {
    const email = document.getElementById('email');

    const errors = [];

    if (email.value == '') {
      errors.push('Email cannot be blank');
      email.classList.add('input-error');
    }
    else if (!validateEmail(email.value)) {
      errors.push('Invalid email address');
			email.classList.add('input-error');
    }

    return errors;
  }
}

MyAccount.init();