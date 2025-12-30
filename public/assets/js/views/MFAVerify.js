const MFAVerify = {
  ticker: 0,
  startDateTime: '',

  init: function () {
    this.initEvents();

    const startDateTime = document.getElementById('verify-container').dataset.start;
    document.getElementById('verify-container').removeAttribute('data-start');

    if (startDateTime == '') {
      document.getElementById('verify-container').removeChild(document.getElementById('instructions'));
      document.getElementById('verify-container').removeChild(document.getElementById('mfa-code-form'));
      //document.getElementById('resend-btn')?.remove();
    }
    else if (document.getElementById('expire-time') != null) {
      var dt = new Date(`${startDateTime} UTC`);	// Date is stored in UTC
      this.startDateTime = new Date(dt.toString());		// Convert it to local time
      this.checkElapsedTime();
    }

    document.getElementById('mfa-code')?.focus();
  },

  initEvents: async function () {
    const self = this;

    document.getElementById('mfa-code')?.addEventListener('keydown', function(e) {
      if (isControlKey(e.key, e.ctrlKey)) {
        return;
      }

      if (!isNumericKey(e.key, false) || this.value.length >= 6) {
        e.preventDefault();
      }
    });

    document.getElementById('resend-btn')?.addEventListener('click', function(e) {
      self.resend();
    });

    document.getElementById('submit-btn').addEventListener('click', function(e) {
      this.disabled = true;
      wait();
      document.getElementById('mfa-code-form').submit();
    });
  },

  checkElapsedTime: function() {
    const self = this;

    let totalTime = (15 * 60 * 1000);		// 15 minutes
		let curDateTime = new Date();
		let elapsedTimeMS = totalTime - (curDateTime - this.startDateTime);
		let seconds = parseInt(Math.floor(elapsedTimeMS / 1000));
		let minutes = parseInt(Math.floor(seconds / 60));
    const errorElem = document.querySelector('.errormsg');

		if (minutes > 0) {
			seconds -= (minutes * 60);
		}

		let expireTime = document.getElementById('expire-time');
		expireTime.innerHTML = minutes+':'+(seconds.toString().length == 1 ? '0'+seconds : seconds);

		if (minutes == 1) {
			expireTime.style.color = '#ff9900';
		}

		if (minutes == 0) {
			expireTime.style.color = '#ff0000';
		}

		if (minutes <= 0 && seconds <= 0) {
			if (this.ticker) {
				clearInterval(this.ticker);
			}

			expireTime.innerHTML = '0:00';
			expireTime.style.color = '#ff0000';

			document.getElementById('instructions')?.remove();
			document.getElementById('mfa-code-form')?.remove();
      document.getElementById('resend-btn')?.remove();

      errorElem.innerHTML = 'Authentication code has expired'
      errorElem.classList.remove('hidden');

			return;
		}

		if (!this.ticker) {
			this.ticker = setInterval(() => { self.checkElapsedTime(); }, 1000);
		}
  },

  resend: async function() {
    document.getElementById('resend-btn').disabled = true;

    wait();
    await delay(50);

    const form = document.getElementById('mfa-code-form');
    form.innerHTML += '<input type="hidden" name="resend" value="1">';
    document.getElementById('mfa-code-form').submit();
  }
}

MFAVerify.init();