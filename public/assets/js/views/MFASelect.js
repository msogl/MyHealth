const MFASelect = {
  init: function () {
    this.initEvents();
  },

  initEvents: async function () {
    document.querySelectorAll('.send-to').forEach(elem => {
      elem.addEventListener('click', async (e) => {
        elem.classList.add('active');

        wait();
        await delay(100);
        redirect(`mfa-select?type=${elem.dataset.type}`);
      });
    });
  }
}

MFASelect.init();