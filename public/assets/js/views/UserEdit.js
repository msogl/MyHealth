const UserEdit = {
  _aid: '',
  _curMemberId: '',

  init: function () {
    this._curMemberId = document.getElementById('memberid').value;
    const container = document.querySelector('.edit-container[data-aid]');
    this._aid = container.dataset.aid ?? '';
    this._userInfo = container.dataset.info ?? '';
    this.initEvents();
  },

  initEvents: function () {
    const self = this;

    document.getElementById('active-toggle-btn').addEventListener('click', function(e) {
      self.enableDisable();
    });

    document.getElementById('memberid').addEventListener('blur', function(e) {
      self.getMemberInfo(this.value);
    });

    document.getElementById('cancel-btn').addEventListener('click', function(e) {
      redirect('users');
    });

    document.getElementById('save-btn').addEventListener('click', function(e) {
      self.save();
    });

    document.getElementById('revoke-mfa-btn')?.addEventListener('click', function(e) {
      self.revokeMFA();
    });
  },

  enableDisable: async function() {
    TrackMessage.clearMessage();

    const button = document.getElementById('active-toggle-btn');
    const activeIndicator = document.getElementById('active-indicator');
    const status = (button.innerText == 'Enable');

    button.disabled = true;

    const yn = confirm(`Are you sure you want to ${status} this account?`);
    if (!yn) {
      button.disabled = false;
      return;
    }

    const data = await Account.toggleActive(this._aid, status)
    button.disabled = false;

    if (data.error) {
      alert(data.error);
      return;
    }

    if (data.response.isActive) {
      button.innerText = 'Disable';
      activeIndicator.classList.add('hidden');
    }
    else {
      button.innerText = 'Enable';
      activeIndicator.classList.remove('hidden');
    }
  },

  getMemberInfo: async function(memberId) {
    TrackMessage.clearMessage();

    if (memberId == this._curMemberId) {
      return;
    }

    const params = {
      mid: memberId
    }

    const data = await doFetch('post', 'get-member-info', params)

    const membername = document.getElementById('membername');
    const dob = document.getElementById('dob');

    if (data.error) {
      membername.innerHTML = '&lt;member not found&gt;'; 
      dob.innerHTML = '';
      return
    }

    membername.innerHTML = data.response.name;
    dob.innerHTML = data.response.dob;
    this._curMemberId = memberId;
  },

  save: async function() {
    const self = this;
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));
    TrackMessage.clearMessage();

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      return;
    }

    const saveButton = document.getElementById('save-btn');
    saveButton.disabled = true;

    const params = {
      aid: this._aid,
      email: document.getElementById('email').value,
      firstname: document.getElementById('firstname').value,
      nickname: document.getElementById('nickname').value,
      memberid: document.getElementById('memberid').value,
      changenext: (document.getElementById('changenext').checked ? 1 : 0)
    }

    wait();
    const data = await doFetch('post', 'user-save', params);
    saveButton.disabled = false;
    removeWait();

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      return;
    }

    TrackMessage.showMessage('success', data.response);
    redirectAfter('users', 2000);
  },

  revokeMFA: async function() {
    const self = this;
    TrackMessage.clearMessage();

    const revokeMfaButton = document.getElementById('revoke-mfa-btn');
    revokeMfaButton.disabled = true;

    const yn = confirm('Are you sure you want to revoke MFA tokens for this account?')
    if (yn) {
      wait();

      const params = {
        aid: this._aid
      }

      const data = await doFetch('revoke-mfa', 'revoke-mfa', params);

      removeWait();

      if (data.error) {
        revokeMfaButton.disabled = false
        TrackMessage.showMessage('error', data.error);
        return;
      }

      setTimeout(() => { alert('MFA revoked'); }, 100);
    }
    else {
      revokeMfaButton.disabled = false;
    }
  },

  validate: function() {
    const email = document.getElementById('email');
    const firstname = document.getElementById('firstname');
    const memberid = document.getElementById('memberid');

    const errors = [];

    if (email.value == '') {
      errors.push('Email is required');
      email.classList.add('input-error');
    }
    else if (!validateEmail(email.value)) {
      errors.push('Invalid email address');
			email.classList.add('input-error');
    }

    if (firstname.value == '') {
      errors.push('First name is required');
      firstname.classList.add('input-error');
    }

    if (memberid.value == '') {
      errors.push('Member ID is required');
      memberid.classList.add('input-error');
    }

    return errors;
  }
}

UserEdit.init();