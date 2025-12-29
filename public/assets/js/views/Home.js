const Home = {
  init: function() {
		if (fyi.length == 0) {
			const row = '<tr><td colspan="2" style="line-height:3em;">';
			row += '<em>Nothing new at this time...</em>';
			row += '</td></tr>'
      document.querySelector('#for-your-info tbody').innerHTML += row;
		}
		else {
			for (let ix=0; ix<fyi.length; ix++) {
				const row = `<tr>
          <td>${fyi[ix].StartDate}</td>
          <td class="info-item" data-value="${ix}">
            ${this.fyiNewIndicator(fyi[ix].StartDate)}<a href="javascript:void(0);">${fyi[ix].Subject}</a>
          </td>
        </tr>`;
        document.querySelector('#for-your-info tbody').innerHTML += row;
			}
		}

    this.initEvents();
  },

  initEvents: function() {
    const self = this;

    document.querySelector('.materials-table')?.addEventListener('click', function(e) {
      if (!e.target.matches('a')) {
        return;
      }

      window.open(`service/download?url=${e.target.dataset.href}&type=material&token=${App.CSRFToken}`)
    });

    document.getElementById('video-body').addEventListener('click', function(e) {
        if (!e.target.matches('.vjs-fullscreen-control')) {
            return;
        }

        self.toggleFullScreen(e.target);
    });

    document.getElementById('pdf-body').addEventListener('click', function(e) {
      if (!e.target.matches('#pdf-download-btn')) {
          return;
      }

      const url = `dl.php?url=${this.dataset.href}&type=material&download=1&token=${App.CSRFToken}`;
      window.open(url);
    });

    document.querySelectorAll('.info-item')?.forEach(function(elem) {
      elem.addEventListener('click', function(e) {
        const ix = this.dataset.value;
        const contentElem = document.querySelector('#popupMessage .content')
        contentElem.innerHTML = fyi[ix].Content;

        showPopup('popupMessage', 600, 'auto', true, true);
      });
    });
  },

  fyiNewIndicator: function(fyiDate) {
    const today = new Date();
    let compareDate = new Date(fyiDate);
    compareDate = compareDate.setDate(compareDate.getDate() + 7);
    return (today <= compareDate ? '<span style="color:#f00;"><b><i>NEW!</i></b></span> ' : '');
  },

  toggleFullScreen: function(elem) {
    if (elem.closest('.vjs-fullscreen') == null) {
        // going into full screen mode, so change title
        elem.setAttribute('title', 'Exit full screen');
    }
    else {
        // exiting full screen mode, so change title
        elem.setAttribute('title', 'Full screen');
    }
  },

	resizeVideoArea: function(selector, height)	{
		let vidWidth = document.querySelector(selector).dataset.videoWidth;
		let vidHeight = $(selector).dataset.videoHeight;

		let ratio = height/vidHeight;
		vidWidth = (vidWidth * ratio);

		if (viewportWidth < vidWidth) {
			vidWidth = viewportWidth;
		}

    const videoElem = document.querySelector('#video-body.content');
    videoElem.style.height = height+'px';
    videoElem.style.padding = 0;

		showPopup('popup-video', vidWidth, height+34, true);
	}
}