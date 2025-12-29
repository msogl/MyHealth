const GPC = {
  uspFramework: {
    version: 1,
    notice: 'Y',
    optOut: 'N',
    lspa: 'Y'
  },

  init: function() {
    this.initEvents();

    if (typeof navigator.globalPrivacyControl !== 'undefined') {
      this.uspFramework.optOut = (navigator.globalPrivacyControl ? 'Y' : 'N');
    }
  },

  initEvents: function() {
    const self = this;
    
    window.__uspapi = (command, version, callback) => {
      if (command === 'getUSPData' && version === 1) {
        callback(self.uspString(), true)
      }
    }

    document.getElementById('gpc').addEventListener('click', function(e) {
      window.__uspapi('getUSPData', 1, (uspString, something) => {
        //console.debug('Do Not Track =', uspString)
      });
      alert(`Your browser is indicating a desire for privacy with a Global Privacy Control signal (also known as Do Not Track). We do not collect data for marketing purposes, so nothing to worry about there.`);

    });
  },

  uspString: function() {
    // Will return a USP string like `1YYY`
    return Object.values(this.uspFramework).reduce((a, c) => { return a+c }, '')
  }
}

GPC.init();