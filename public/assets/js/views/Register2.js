const Register2 = {
  init: function() {
    const errorsValue = document.getElementById('track-message').dataset.init;
    if (errorsValue != '') {
      console.log(errorsValue);
      const errors = JSON.parse(errorsValue);
      TrackMessage.showMessage('error', errors.join('<br>'));
    }

    document.getElementById('track-message').dataset.init = '';
    document.getElementById('track-message').removeAttribute('data-init');

    this.initEvents();

    PasswordEvaluator.init({
      passwordFieldId: 'password',
      passwordMeterId: 'password-meter',
      retypeFieldId: 'retype',
      retypeErrorId: 'retype-error',
      charCountId: 'password-char-count',
      passwordPolicyId: 'password-policy'
    });

    setDefaultButton('next-btn');
    document.getElementById('username').focus();
  },

  initEvents: function() {
    const self = this;

    document.getElementById('next-btn').addEventListener('click', function(e) {
      self.next();
    });

    document.getElementById('prev-btn')?.addEventListener('click', function(e) {
      history.go(-1);
    });
  },

  next: async function() {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));

    const errors = await this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      return;
    }

    TrackMessage.clearMessage();
    
    wait();
    disableElems('.button');
    document.getElementById('register-form').submit();
  },

  checkUsername: async function() {
    const username = document.getElementById('username');

    if (username.value == '') {
      return false;
    }

    const data = await doFetch('get', 'check-username', { username: username.value });
    
    if (data.error) {
      return false;
    }

    return data.response.isAvailable;
  },

  validate: async function() {
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const retype = document.getElementById('retype');

    const errors = []
    if (username.value == '') {
      errors.push('Username is required');
      username.classList.add('input-error');
    }
    else if (username.value.length < 3) {
      errors.push('Username must be at least 3 characters long');
      username.classList.add('input-error');
    }
    else if (!await this.checkUsername()) {
      errors.push('Username is unavailable');
      username.classList.add('input-error');
    }

    if (email.value == '') {
      errors.push('Email is required');
      email.classList.add('input-error');
    }
    else if (!validateEmail(email.value)) {
      errors.push('Email address is not valid');
      email.classList.add('input-error');
    }

    if (password.value == '') {
      errors.push('Password is required');
      password.classList.add('input-error');
    }
    else if (password.value != retype.value) {
      password.classList.add('input-error');
      errors.push('Password and re-typed password do not match.');
      retype.classList.add('input-error');
    }
    else if (!PasswordEvaluator.meetsPolicy()) {
      errors.push('Password is not strong enough');
      password.classList.add('input-error');
    }

    return errors;
  }
}

Register2.init();