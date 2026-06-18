(() => {
	if (window.UPOS_THEME_TOGGLE_READY) {
		return;
	}
	window.UPOS_THEME_TOGGLE_READY = true;

	const storageKey = 'upos-theme-mode';
	const storage = (() => {
		try {
			return window.sessionStorage;
		} catch (error) {
			return null;
		}
	})();
	const root = document.documentElement;
	const body = document.body;
	const toggle = document.querySelector('.upos-theme-toggle__button');
	const state = document.querySelector('.upos-theme-toggle__state');

	if (!root || !body || !toggle || !state) {
		return;
	}

	const applyMode = (mode) => {
		const isLight = mode === 'light';
		root.classList.toggle('upos-theme-light', isLight);
		body.classList.toggle('upos-theme-light', isLight);
		root.setAttribute('data-upos-theme', isLight ? 'light' : 'dark');
		body.setAttribute('data-upos-theme', isLight ? 'light' : 'dark');
		toggle.setAttribute('aria-pressed', String(!isLight));
		state.textContent = isLight ? 'Off' : 'On';
		document.dispatchEvent(
			new CustomEvent('upos:theme-change', {
				detail: {
					mode,
					isLight,
				},
			})
		);
	};

	const isMobileViewport = window.matchMedia('(max-width: 921px)').matches;
	const allowMobileToggle = window.UPOS_THEME_TOGGLE_ALLOW_MOBILE === true;

	let savedMode = null;
	try {
		if (window.localStorage) {
			window.localStorage.removeItem(storageKey);
		}
	} catch (error) {
		// Ignore legacy localStorage cleanup failures.
	}

	try {
		savedMode = storage ? storage.getItem(storageKey) : null;
	} catch (error) {
		savedMode = null;
	}

	const preferredMode = savedMode === 'dark' || savedMode === 'light' ? savedMode : 'light';
	applyMode((isMobileViewport && !allowMobileToggle) ? 'light' : preferredMode);

	if (isMobileViewport && !allowMobileToggle) {
		try {
			if (storage) {
				storage.setItem(storageKey, 'light');
			}
		} catch (error) {
			// Ignore storage failures on mobile.
		}
		return;
	}

	let lastToggleAt = 0;

	const handleToggle = (event) => {
		if (event) {
			event.preventDefault();
			event.stopPropagation();
		}

		const now = Date.now();
		if (now - lastToggleAt < 250) {
			return;
		}
		lastToggleAt = now;

		const nextMode = body.classList.contains('upos-theme-light') ? 'dark' : 'light';
		window.requestAnimationFrame(() => {
			try {
				if (storage) {
					storage.setItem(storageKey, nextMode);
				}
			} catch (error) {
				// Ignore storage failures.
			}
			applyMode(nextMode);
		});
	};

	toggle.addEventListener('click', handleToggle);
})();
