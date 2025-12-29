const Privacy = {
  init: function() {
    this.gpcOptoutValue();
  },

  gpcOptoutValue: function() {
    const gpcElem = document.getElementById('gpc-current-value');

    if (typeof navigator.globalPrivacyControl === 'undefined') {
      gpcElem.innerText = 'Unsupported by your browser';
      return;
    }

    if (navigator.globalPrivacyControl) {
      gpcElem.classList.remove('off');
      gpcElem.classList.add('on');
      gpcElem.innerText = 'On';
      return;
    }

    gpcElem.classList.remove('on');
    gpcElem.classList.add('off');
    gpcElem.innerText = 'Off';
  }
}

Privacy.init();