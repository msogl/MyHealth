document.addEventListener("DOMContentLoaded", function() {
	var mobileMenu = document.querySelector('a.mobile');

	if (mobileMenu != null) {
		mobileMenu.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var menubar = document.querySelector('#menubar ul');

			if (menubar.classList.contains('is-active')) {
				menubar.classList.remove('is-active');
			}
			else {
				menubar.classList.add('is-active');
			}

			return false;
		});
	}
});
