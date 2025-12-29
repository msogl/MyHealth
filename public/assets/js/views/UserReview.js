const UserReview = {
  init: function () {
    this.initEvents();
  },

  initEvents: function () {
    const self = this;

    document.getElementById('redisplay-btn').addEventListener('click', function (e) {
      self.redisplay();
    });

    document.querySelectorAll('input.active').forEach(function (elem) {
      elem.addEventListener('click', function (e) {
        // checked state is already changed by the time we get here
        const row = this.closest('tr');
        if (row == null) {
          console.warn('parent node not found');
          return;
        }

        const accountId = row.dataset.id;
        self.toggleActive(accountId, this.checked);
      });
    })
  },

  redisplay: async function() {
    await showLoader();
    const activeOnly = (document.getElementById('show-inactive-cb').checked ? '1' : '0');
    const idleDays = document.getElementById('idle-days').value;
    redirect(`user-review?idledays=${idleDays}&inactive=${activeOnly}&token=${App.CSRFToken}`);
  },

  toggleActive: async function (accountId, active) {
    const data = await Account.toggleActive(accountId, active);

    if (data && data.error) {
      document.querySelector(`#users-table tbody tr[data-id="${accountId}"] td.toggle input[type="checkbox"]`).checked = !active;
      return;
    }

    const row = document.querySelector(`#users-table tbody tr[data-id="${accountId}"]`);
    const activeCell = row.querySelector('td.active');
    activeCell.setAttribute('data-value', (data.response.isActive ? '1' : '0'));
    activeCell.innerHTML = (data.response.isActive ? App.CHECKMARK : App.XMARK);

    const toggleSwitch = row.querySelector('td.toggle .switch');

    if (data.response.isActive) {
      toggleSwitch.classList.add('on');
    }
    else {
      toggleSwitch.classList.remove('on');
    }
  }
}

UserReview.init();