/* WP FastY — cookie consent (152-FZ): analytics loads only after "Accept all". No external requests. */
(function () {
	var NAME = 'wpfasty_cookie_consent';
	var banner = document.getElementById('cookie-banner');
	if (!banner) { return; }
	function get() {
		var m = document.cookie.match(new RegExp('(?:^|; )' + NAME + '=([^;]*)'));
		return m ? decodeURIComponent(m[1]) : '';
	}
	function set(value) {
		document.cookie = NAME + '=' + value + '; max-age=' + (180 * 86400) + '; path=/; SameSite=Lax';
		banner.hidden = true;
		document.dispatchEvent(new CustomEvent('wpfasty:consent', { detail: { analytics: value === 'all' } }));
	}
	if (!get()) { banner.hidden = false; }
	banner.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-cookie]');
		if (btn) { set(btn.getAttribute('data-cookie')); }
	});
	document.addEventListener('click', function (e) {
		if (e.target.closest('[data-cookie-open]')) { e.preventDefault(); banner.hidden = false; }
	});
})();
