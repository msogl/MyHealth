var PasswordPolicy = {
    minLength: 8,
    minUpper: 1,
    minNumeric: 1,
    minSpecial: 1,
    numeric: '0123456789',
    lowerChars: 'abcdefghijklmnopqrstuvwxyz',
    upperChars: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    specialChars: '~!@#$%^&*()',
    strengthWeak: 4,
    strengthStrong: 6,

    watchMeter: function(passwordFieldId, meterElementId) {
        var self = this;
        var passwordElem = document.getElementById(passwordFieldId);

        passwordElem.addEventListener('input', function(e) {
            self.updateMeter(this.value, meterElementId);
        });

        passwordElem.addEventListener('propertyChange', function(e) {
            self.updateMeter(this.value, meterElementId);
        });

        passwordElem.addEventListener('paste', function(e) {
            self.updateMeter(this.value, meterElementId);
        });
    },

    watchRetype: function(passwordFieldId, retypeFieldId, retypeErrorId) {
        var self = this;
        var retypeElem = document.getElementById(retypeFieldId);

        retypeElem.addEventListener('input', function(e) {
            self.retypeCompare(passwordFieldId, retypeFieldId, retypeErrorId);
        });

        retypeElem.addEventListener('propertyChange', function(e) {
            self.retypeCompare(passwordFieldId, retypeFieldId, retypeErrorId);
        });

        retypeElem.addEventListener('paste', function(e) {
            self.retypeCompare(passwordFieldId, retypeFieldId, retypeErrorId);
        });
    },

    updateMeter: function(password, meterElementId) {
        if (typeof meterElementId === 'undefined') {
            return;
        }

        // Do not show anything when the length of password is zero.
        if (password.length === 0) {
            document.getElementById(meterElementId).innerHTML = '';
            return;
        }

        var ch1, ch2, ch3, ch4;
        var asc1, asc2, asc3, asc4;
        var passLen = password.length;
        var numericCount = 0;
        var upperCount = 0;
        var specialCount = 0;
        var sawRepeat = false;
        var sawNumericSequence = false;
        var sawAlphaSequence = false;

        for(var ix=0; ix<passLen; ix++) {
            asc1 = 0;
            asc2 = 0;
            asc3 = 0;
            asc4 = 0;

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

                if (this.numeric.indexOf(ch1) - 1) {
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
                    if (asc1 == (asc2 - 1) && asc1 == (asc3 - 2)) {
                        sawAlphaSeq = true;
                    }
                    
                    if (asc1 == (asc2 + 1) && asc1 == (asc3 + 2)) {
                        sawAlphaSeq = true;
                    }
                }
            }

            if (this.numeric.indexOf(ch1) > -1) {
                numericCount++;
            }
            
            if (this.upperChars.indexOf(ch1) > -1) {
                upperCount++;
            }
            
            if (this.specialChars.indexOf(ch1) > -1) {
                specialCount++;
            }
        }

        var strength = 0;

        if (password.length >= this.minLength) {
            strength++;
        }

        if (numericCount >= this.minNumeric) {
            strength++;
        }

        if (upperCount >= this.minUpper) {
            strength++;
        }

        if (specialCount >= this.minSpecial) {
            strength++;
        }

        if (!sawNumericSequence) {
            strength++;
        }

        if (!sawAlphaSequence) {
            strength++;
        }

        if (!sawRepeat) {
            strength++;
        }

        var strengthColor = '';
        var strengthMsg = '';

        if (strength < this.strengthWeak) {
            strengthMsg = 'Weak';
            strengthColor = 'red';
        }
        else if (strength < this.strengthStrong) {
            strengthMsg = 'Medium';
            strengthColor = 'orange';
        }
        else {
            strengthMsg = 'Strong';
            strengthColor = 'green';
        }

        var meter = document.getElementById(meterElementId);

        if (meter == null) {
            return;
        }

        var progress = document.querySelector('#'+meterElementId+' progress');

        if (progress == null) {
            progress = document.createElement("progress");
            progress.setAttribute('max', '7');
            meter.appendChild(progress);
        }

        var meterText = document.querySelector('#'+meterElementId+' #progress-meter-text');

        if (meterText == null) {
            meterText = document.createElement("div");
            meterText.setAttribute('id', 'progress-meter-text');
            meter.appendChild(meterText);
        }

        progress.setAttribute('value', strength);
        progress.style.width = '100%';
        progress.classList.remove('weak','medium','strong');
        progress.classList.add(strengthMsg.toLowerCase());
        meterText.textContent = strengthMsg;
        meterText.style.color = strengthColor;
    },

    retypeCompare: function(passwordFieldId, retypeFieldId, retypeErrorId) {
        document.getElementById(retypeErrorId).innerHTML = '';

        if (document.getElementById(retypeFieldId).value == '') {
            return;
        }

        if ((document.getElementById(passwordFieldId).value != document.getElementById(retypeFieldId).value) &&
            (document.getElementById(retypeFieldId).value.length > 0)) {
            document.getElementById(retypeErrorId).innerHTML = 'Doesn\'t match';
            document.getElementById(retypeErrorId).style.color = 'red';
        }
        else {
            document.getElementById(retypeErrorId).innerHTML = '&#10004; Matches';
            document.getElementById(retypeErrorId).style.color = 'green'; 
        }
    }
}