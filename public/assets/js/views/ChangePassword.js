const ChangePassword = {
  init: function() {
    this.initEvents();

    setDefaultButton('change-btn');
		setCancelButton('cancel-btn');

    PasswordEvaluator.init({
      passwordFieldId: 'password',
      passwordMeterId: 'password-meter',
      retypeFieldId: 'retype',
      retypeErrorId: 'retype-error',
      charCountId: 'password-char-count',
      passwordPolicyId: 'password-policy'
    });

		document.getElementById('password').focus();
  },

  initEvents: function() {
    const self = this;

    document.getElementById('cancel-btn').addEventListener('click', function(e) {
			self.cancel();
		});

    document.getElementById('change-btn').addEventListener('click', function(e) {
      self.changePassword();
    });
  },

  cancel: async function() {
    wait();
    const data = await doFetch('post', 'change-password-cancel');
    redirect(data.response.next);
  },

  changePassword: async function() {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));
    TrackMessage.clearMessage();

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      return;
    }

    disableElems('.button');
    wait();

    const params = {
      password: document.getElementById('password').value,
      meta: App.meta
    }
    
    const data = await doFetch('post', 'change-password-action', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      enableElems('.button');
      removeWait();
      return;
    }

    // NOTE: We are intentionally NOT re-enabling buttons on success. We're going to
    // redirect after a couple of seconds
    TrackMessage.showMessage('success', data.response.message);
    redirectAfter(data.response.next, 2000);
  },

  validate: function() {
    const password = document.getElementById('password');
    const retype = document.getElementById('retype');

    const errors = [];

    if (password.value == '') {
      errors.push('Your new password cannot be blank');
      password.classList.add('input-error');
    }
    else if (password.value != retype.value) {
      errors.push('Your new password and the re-typed password do not match. Please try again.');
      retype.classList.add('input-error');
    }
    else if (!PasswordEvaluator.meetsPolicy()) {
      errors.push('This is not a strong enough password');
      password.classList.add('input-error');
    }

    return errors;
  }
}

ChangePassword.init();