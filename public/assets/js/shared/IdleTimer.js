const IdleTimer = {
  countdownTimer: 0,
  timeoutSeconds: 15 * 60,
  warningPoint: 2 * 60,

  // The time when the user will be logged out
  timeMaxMs: 0,

  // The time when a warning will be shown to the user
  timeWarningMs: 0,

  warningShown: false,

  keepAliveTimer: 0,

  // How often to trigger the keep alive
  keepAliveIntervalSeconds: 5 * 60,

  // Once the keep alive fires, record the next time it will fire.
  // It may not be exact due to inactive tab suspension, but it
  // should be close enough.
  nextKeepAliveTimeMs: 0,

  debug: false,

  init: function () {
    this.initEvents();

    // Start the client-side idle timeout
    this.startCountdown();

    // Make sure we keep the server session
    // alive, too, by sending a heartbeat.
    // This is independent from the idle countdown.
    this.startKeepAlive();
  },

  initEvents: function () {
    const self = this;

    const events = [
      'mousedown', 'touchstart', 'keyup'
    ];

    events.forEach(function (event) {
      window.addEventListener(event, function () {
        self.startCountdown();
      })
    });
  },

  startCountdown: function () {
    const self = this;
    clearInterval(this.countdownTimer);
    this.timeMaxMs = Date.now() + (this.timeoutSeconds * 1000);
    this.timeWarningMs = this.timeMaxMs - (this.warningPoint * 1000);
    this.hideWarning();
    this.warningShown = false;

    this.countdownTimer = setInterval(function () {
      const timeLeftMs = self.timeMaxMs - Date.now();
      //console.log(`timeLeftMs = ${timeLeftMs} (${self.convertToHuman(timeLeftMs)})`);

      if (Date.now() >= self.timeWarningMs) {
        const time = self.convertToHuman(timeLeftMs);

        if (!self.warningShown) {
          self.showWarning(time);
          self.warningShown = true;
        }
        else {
          document.getElementById('idle-time-left').textContent = time;
        }
      }

      if (timeLeftMs <= 0) {
        redirect('logoff');
        return;
      }
    }, 1000);
  },

  showWarning: function (time) {
    if (document.getElementById('overlay') == null) {
      document.querySelector('body').innerHTML += '<div id="overlay"></div>';
    }

    if (document.getElementById('popup-idle-timeout-warning') == null) {
      const html = `<div id="popup-idle-timeout-warning" class="popup">
        <div class="content">
          <div class="center pt-1 pb-1">
            Your session will be logged out <span id="idle-time-left">${time}</span>.<br>
            Click anywhere to stay logged in.
          </div>
        </div>
        <div class="footer right">
            <button type="button" class="button" onclick="hidePopup('popup-idle-timeout-warning');">OK</button>
        </div>
      </div>`;

      document.getElementById('overlay').innerHTML += html;
    }

    showPopup('popup-idle-timeout-warning', 400, 'auto', true, true);
  },

  hideWarning: function () {
    const popup = document.getElementById('popup-idle-timeout-warning');
    if (popup != null && popup.style.display != 'none') {
      hidePopup('popup-idle-timeout-warning');
    }
  },

  convertToHuman: function (milliseconds) {
    if (milliseconds <= 0) {
      return 'now'
    }

    let seconds = Math.floor(milliseconds / 1000);
    let m = Math.floor(seconds / 60)
    let s = seconds - (m * 60);

    let phrase = 'in ';

    if (m > 0) {
      phrase = `${m} minute${(m != 1 ? 's' : '')}, `;
    }

    if (s > 0) {
      phrase += `${s} second${(s != 1 ? 's' : '')}`;
    }

    return phrase;
  },

  startKeepAlive: function () {
    const self = this;
    clearInterval(this.keepAliveTimer);

    this.keepAliveTimer = setInterval(function () {
      if (Date.now() >= self.nextKeepAliveTimeMs) {
        self.keepAlive();
        self.nextKeepAliveTimeMs = Date.now() + (self.keepAliveIntervalSeconds * 1000);
      }
    }, 1000);
  },

  keepAlive: function () {
    fetch('heartbeat')
      .then(response => {
        if (!response.ok) {
          throw new Error(response.statusText);
        }

        return response.text();
      })
      .then(data => {
        if (!data) {
          return;
        }

        //console.log('heartbeat:', data);
      })
      .catch(error => { /* noop */ })
  }
}

IdleTimer.init();