const TrackMessage = {
  showMessage: function(type, msg) {
    if (!['error','success'].includes(type)) {
      type = 'error';
    }

    const elem = document.getElementById('track-message');
    if (elem == null) {
      return;
    }
    
    elem.classList.remove('track-error', 'track-success');
    elem.classList.add(`track-${type}`);

    const icon = (type == 'success' ? 'fa-check-circle' : 'fa-times-circle');

    elem.innerHTML = `
      <div><i class="fa ${icon}"></i></div>
      <div>${msg}</div>
    `;
    elem.style.display = 'flex';
  },

  clearMessage: function() {
    const elem = document.getElementById('track-message');
    elem.classList.remove('track-error', 'track-success');
    elem.style.display = 'none';
  }
}