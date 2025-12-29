const PasswordEvaluator = {
  passwordFieldId: null,
  passwordMeterId: null,
  retypeFieldId: null,
  charCountId: null,
  passwordPolicyId: null,
  minLength: 10,
  minUpper: 1,
  minLower: 1,
  minNumeric: 1,
  minSpecial: 1,
  noNumericSequence: false,
  noAlphaSequence: false,
  noRepeat: false,
  numeric: '0123456789',
  lowerChars: 'abcdefghijklmnopqrstuvwxyz',
  upperChars: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
  specialChars: '~!@#$%^&*()-_=+[]{}\\|,.<>/?;\':"',

  init: function (opts) {
    this.passwordFieldId = opts.passwordFieldId || null;
    this.passwordMeterId = opts.passwordMeterId || null;
    this.retypeFieldId = opts.retypeFieldId || null;
    this.retypeErrorId = opts.retypeErrorId || null;
    this.charCountId = opts.charCountId || null;
    this.passwordPolicyId = opts.passwordPolicyId || null;

    if (typeof opts.policy !== 'undefined') {
      if (opts.policy.minLength) { this.minLength = opts.policy.minLength; }
      if (opts.policy.minUpper) { this.minUpper = opts.policy.minUpper; }
      if (opts.policy.minLower) { this.minLower = opts.policy.minLower; }
      if (opts.policy.minNumeric) { this.minNumeric = opts.policy.minNumeric; }
      if (opts.policy.minSpecial) { this.minSpecial = opts.policy.minSpecial; }
      if (opts.policy.noNumericSequence) { this.noNumericSequence = opts.policy.noNumericSequence; }
      if (opts.policy.noAlphaSequence) { this.noAlphaSequence = opts.policy.noAlphaSequence; }
      if (opts.policy.noRepeat) { this.noRepeat = opts.policy.noRepeat; }
    }

    this.showPolicy();
    this.initEvents();
  },

  initEvents: function () {
    this.watchMeter();
    this.watchRetype();
  },

  calcMaxStrength: function () {
    let maxStrength = this.minLength;
    if (this.minUpper > 0) { maxStrength++; }
    if (this.minLower > 0) { maxStrength++; }
    if (this.minNumeric > 0) { maxStrength++; }
    if (this.minSpecial > 0) { maxStrength++; }
    if (this.noNumericSequence) { maxStrength++; }
    if (this.noAlphaSequence) { maxStrength++; }
    if (this.noRepeat) { maxStrength++; }
    return maxStrength;
  },

  watchMeter: function () {
    if (!this.passwordFieldId || !this.passwordMeterId) {
      return;
    }

    const self = this;
    const passwordElem = document.getElementById(this.passwordFieldId);
    const events = ['input', 'propertyChange', 'paste', 'keydown'];

    for (let evt of events) {
      passwordElem.addEventListener(evt, function (e) {
        self.updateMeter();

        if (self.charCountId) {
          self.updateCharCount();
        }
      });
    }
  },

  watchRetype: function () {
    if (!this.retypeFieldId || !this.retypeErrorId) {
      return;
    }

    const self = this;
    this.initStyle();

    const retypeElem = document.getElementById(this.retypeFieldId);
    const events = ['input', 'propertyChange', 'paste'];

    for (let evt of events) {
      retypeElem.addEventListener(evt, function (e) {
        self.retypeCompare();
      });
    }
  },

  evalPolicyStrength: function () {
    const password = document.getElementById(this.passwordFieldId).value;

    let ch1, ch2, ch3, ch4;
    let asc1, asc2, asc3, asc4;
    let passLen = password.length;
    let numericCount = 0;
    let upperCount = 0;
    let lowerCount = 0;
    let specialCount = 0;
    let sawRepeat = false;
    let sawNumericSequence = false;
    let sawAlphaSequence = false;

    for (let ix = 0; ix < passLen; ix++) {
      asc1 = asc2 = asc3 = asc4 = 0;
      ch1 = ch2 = ch3 = ch4 = '';

      ch1 = password.charAt(ix);
      asc1 = ch1.charCodeAt(0);

      if (ix < passLen - 1) {
        ch2 = password.charAt(ix + 1);
        asc2 = ch2.charCodeAt(0);
      }

      if (ix < passLen - 2) {
        ch3 = password.charAt(ix + 2)
        asc3 = ch3.charCodeAt(0);

        if (ch1 == ch2 && ch1 == ch3) {
          sawRepeat = true;
        }

        if (this.numeric.indexOf(ch1) > -1) {
          if (asc1 == (asc2 - 1) && asc1 == (asc3 - 2)) {
            sawNumericSequence = true;
          }

          if (asc1 == (asc2 + 1) && asc1 == (asc3 + 2)) {
            sawNumericSequence = true;
          }
        }
      }

      if (ix < passLen - 3) {
        ch4 = password.charAt(ix + 3);
        asc4 = ch4.charCodeAt(0);

        if (this.lowerChars.indexOf(ch1) > -1 || this.upperChars.indexOf(ch1) > -1) {
          if (asc1 == (asc2 - 1) && asc1 == (asc3 - 2) && asc1 == (asc4 - 3)) {
            sawAlphaSequence = true;
          }

          if (asc1 == (asc2 + 1) && asc1 == (asc3 + 2) && asc1 == (asc4 + 3)) {
            sawAlphaSequence = true;
          }
        }
      }

      if (this.numeric.indexOf(ch1) > -1) {
        numericCount++;
      }

      if (this.upperChars.indexOf(ch1) > -1) {
        upperCount++;
      }

      if (this.lowerChars.indexOf(ch1) > -1) {
        lowerCount++;
      }

      if (this.specialChars.indexOf(ch1) > -1) {
        specialCount++;
      }
    }

    let strength = password.length;

    if (password.length >= this.minLength) {
      this.pass('minlength');
      strength = this.minLength;
    }
    else {
      this.fail('minlength');
    }

    if (numericCount >= this.minNumeric) {
      this.pass('minnumeric');
      strength++;
    }
    else {
      this.fail('minnumeric');
    }

    if (upperCount >= this.minUpper) {
      this.pass('minupper');
      strength++;
    }
    else {
      this.fail('minupper');
    }

    if (lowerCount >= this.minLower) {
      this.pass('minlower');
      strength++;
    }
    else {
      this.fail('minlower');
    }

    if (specialCount >= this.minSpecial) {
      this.pass('minspecial');
      strength++;
    }
    else {
      this.fail('minspecial');
    }

    if (!sawRepeat && this.noRepeat && password.length > 3) {
      this.pass('norepeat');
      strength++;
    }
    else {
      this.fail('norepeat');
    }

    if (!sawAlphaSequence && this.noAlphaSequence && password.length >= 4) {
      this.pass('noalphasequence');
      strength++;
    }
    else {
      this.fail('noalphasequence');
    }

    if (!sawNumericSequence && this.noNumericSequence && password.length >= 3) {
      this.pass('nonumericsequence');
      strength++;
    }
    else {
      this.fail('nonumericsequence');
    }

    return strength;
  },

  updateMeter: function () {
    const password = document.getElementById(this.passwordFieldId).value;

    // Do not show anything when the length of password is zero.
    if (password.length === 0) {
      document.getElementById(this.passwordMeterId).innerHTML = '';
      let elems = document.querySelectorAll('li[data-rule]');
      if (elems != null) {
        elems.forEach(function (elem) {
          elem.classList.remove('rule-passed');
        })
      }
      return;
    }

    const maxStrength = this.calcMaxStrength();
    const strength = this.evalPolicyStrength();

    let strengthColor = '';
    let strengthMsg = '';

    if (strength < (maxStrength * .5)) {
      strengthMsg = 'Weak';
      strengthColor = 'red';
    }
    else if (strength < maxStrength) {
      strengthMsg = 'Medium';
      strengthColor = 'orange';
    }
    else {
      strengthMsg = 'Strong';
      strengthColor = 'green';
    }

    const meter = document.getElementById(this.passwordMeterId);

    if (meter == null) {
      return;
    }

    let progress = document.querySelector(`#${this.passwordMeterId} progress`);

    if (progress == null) {
      progress = document.createElement("progress");
      progress.id = 'password-meter-bar';
      progress.setAttribute('max', maxStrength);
      meter.appendChild(progress);
    }

    let meterText = document.querySelector(`#${this.passwordMeterId} #progress-meter-text`);

    if (meterText == null) {
      meterText = document.createElement("label");
      meterText.id = 'progress-meter-text';
      meterText.setAttribute('for', 'password-meter-bar');
      meter.appendChild(meterText);
    }

    progress.setAttribute('value', strength);
    progress.style.width = '100%';
    progress.classList.remove('weak', 'medium', 'strong');
    progress.classList.add(strengthMsg.toLowerCase());
    meterText.textContent = strengthMsg;
    meterText.style.color = strengthColor;
  },

  retypeCompare: function () {
    document.getElementById(this.retypeErrorId).innerHTML = '';

    if (document.getElementById(this.retypeFieldId).value == '') {
      return;
    }

    if ((document.getElementById(this.passwordFieldId).value != document.getElementById(this.retypeFieldId).value) &&
      (document.getElementById(this.retypeFieldId).value.length > 0)) {
      document.getElementById(this.retypeErrorId).innerHTML = 'Does not match';
      document.getElementById(this.retypeErrorId).style.color = 'red';
    }
    else {
      document.getElementById(this.retypeErrorId).innerHTML = '&#10004; Matches';
      document.getElementById(this.retypeErrorId).style.color = 'green';
    }
  },

  updateCharCount: function () {
    const charCount = document.getElementById(this.passwordFieldId).value.length;
    document.getElementById(this.charCountId).innerText = `# chars: ${charCount}`;
  },

  meetsPolicy: function () {
    return (this.evalPolicyStrength() >= this.calcMaxStrength());
  },

  showPolicy: function() {
    if (!this.passwordPolicyId) {
      return;
    }

    let policy = '';

    if (this.minLength > 0) {
			policy += `<li data-rule="minlength">Minimum length: ${this.minLength}</li>`;
		}

		if (this.minNumeric > 0) {
			policy += `<li data-rule="minnumeric">Must have at least ${this.minNumeric} numeric characters</li>`;
		}

		if (this.minUpper > 0) {
			policy += `<li data-rule="minupper">Must have at least ${this.minUpper} uppercase characters</li>`;
		}

    if (this.minLower > 0) {
			policy += `<li data-rule="minlower">Must have at least ${this.minLower} lowercase characters</li>`;
		}

		if (this.minSpecial > 0) {
			policy += `<li data-rule="minspecial">Must have at least ${this.minSpecial} special characters</li>`;
		}

		if (this.noRepeat) {
			policy += `<li data-rule="norepeat">Cannot have the same characters repeating 3 or more times in a row</li>`;
		}

		if (this.noAlphaSeq) {
			policy += `<li data-rule="noalphasequence">Cannot have 4 or more sequential letters</li>`;
		}

		if (this.noNumSeq) {
			policy += `<li data-rule="nonumericsequence">Cannot have 3 or more sequential numbers</li>`;
		}

		if (policy !== '') {
			policy = `<ul>${policy}</ul>`;
    }

    document.getElementById(this.passwordPolicyId).innerHTML = policy;
  },

  initStyle: function () {
    if (!this.passwordPolicyId) {
      return;
    }

    let style = document.createElement('style');
    style.innerHTML = `#${this.passwordPolicyId} ul {
      margin-left: 0;
      padding: 0;
      list-style-type: none;
    }
    
    li[data-rule]::before {
      content: "";
      display: inline-block;
      width: 1.3em;
    }

    li[data-rule].rule-passed::before {
      content: "\\2713\\0020";
    }

    li[data-rule].rule-passed {
      color: #080;
    }`;
    
    document.head.appendChild(style);
  },

  pass: function (rule) {
    const elem = document.querySelector('li[data-rule="' + rule + '"]');
    if (elem != null) {
      elem.classList.add('rule-passed')
    }
  },

  fail: function (rule) {
    let elem = document.querySelector('li[data-rule="' + rule + '"]');
    if (elem != null) {
      elem.classList.remove('rule-passed')
    }
  }
}