const Register1 = {
  init: function() {
    const errorMsg = document.getElementById('track-message').innerHTML;
    if (errorMsg != '') {
      TrackMessage.showMessage('error', errorMsg);
    }

    this.initEvents();

    setCancelButton('cancel-btn');
    setDefaultButton('next-btn');
    document.getElementById('first').focus();
  },

  initEvents: function() {
    const self = this;

    document.getElementById('next-btn').addEventListener('click', function(e) {
      self.next();
    });

    document.getElementById('cancel-btn').addEventListener('click', function(e) {
      redirect('login');
    });

    document.getElementById('memberid-help').addEventListener('click', function() {
      alert('For security purposes, we require your member ID and date of birth so we can validate your identity and provide you with access to your health information.');
    });

    document.getElementById('insurance').addEventListener('change', function() {
      self.showMemberCard(this.value);
    });
  },

  showMemberCard: function(insurance)
	{
    if (insurance === '') {
      document.getElementById('membercard').classList.add('hidden');
      return;
    }

    const images = new Map([
      ['BCBSIL', 'bcbsil_card.gif'],
      ['HUMANA', 'humana_card.gif'],
    ]);

    let image = images.get(insurance);

    if (typeof image === 'undefined') {
      image = 'notavailable_card.gif';
    }

    document.getElementById('membercardimg').src = `assets/images/${image}?${new Date().getTime()}`;
    document.getElementById('membercard').classList.remove('hidden');
	},

  next: function() {
    document.querySelectorAll('.input-error')?.forEach(elem => elem.classList.remove('input-error'));

    const errors = this.validate();

    if (errors.length > 0) {
      TrackMessage.showMessage('error', errors.join('<br>'));
      return;
    }
    
    TrackMessage.clearMessage();
    wait();
    disableElems('.button');
    document.getElementById('register-form').submit();
  },

  submitStep2: function() {

  },

  validate: function() {
    return true;
    return false;
  }
}

Register1.init();