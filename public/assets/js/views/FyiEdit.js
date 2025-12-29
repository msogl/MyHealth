const FyiEdit = {
  init: function() {

    this.initEvents();
  },

  initEvents: function() {
    const self = this;

    document.getElementById('cancel-btn').addEventListener('click', function() {
      window.location.href = 'fyi';
    })

    document.getElementById('save-btn').addEventListener('click', function() {
      self.save();
		});

    document.getElementById('delete-btn').addEventListener('click', function() {
			self.delete();
		});

    document.getElementById('preview-btn').addEventListener('click', function() {
			const content = unescapeAndMarkdown(document.getElementById('content').value);
      document.querySelector('#popupMessage .content').innerHTML = content;
			showPopup('popupMessage', 600, 478, true);
		});
  },

  save: async function() {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));
    TrackMessage.clearMessage();

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      return;
    }

    disableElems('.button');
    wait();

    const params = {
      id: document.getElementById('id').value,
      startdate: document.getElementById('startdate').value,
      enddate: document.getElementById('enddate').value,
      subject: document.getElementById('subject').value,
      content: document.getElementById('content').value,
    }

    const data = await doFetch('post', 'fyi-save', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      enableElems('.button');
      return;
    }

    TrackMessage.showMessage('success', 'Saved successfully!');
    redirectAfter('fyi', 2000);
  },

  delete: async function() {
    disableElems('.button');
    await delay(10);

    const ok = confirm('Are you sure you want to delete this entry?');

    if (!ok) {
      enableElems('.button');
      return;
    }

    wait();

    const params = {
      id: document.getElementById('id').value,
    }

    const data = await doFetch('post', 'fyi-delete', params);

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      enableElems('.button');
      return;
    }

    TrackMessage.showMessage('success', 'Deleted successfully!');
    redirectAfter('fyi', 2000);
  },
  
  validate: function() {
    const startdate = document.getElementById('startdate');
    const subject = document.getElementById('subject');
    const content = document.getElementById('content');

    const errors = [];

    if (startdate.value == '') {
      errors.push('Start date cannot be blank');
      startdate.classList.add('input-error');
    }

    if (subject.value == '') {
      errors.push('Subject cannot be blank');
      subject.classList.add('input-error');
    }

    if (content.value == '') {
      errors.push('Content cannot be blank');
      content.classList.add('input-error');
    }

    return errors;
  },
}

FyiEdit.init();