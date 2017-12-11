(function() {
	// @TODO clean this up / refactor
	// @TODO minify
	// @TODO doc

	if (!window.XMLHttpRequest) {
		console.info('[decosdata] no xhr support detected, disabling all JS features');
		return;
	}

	var form = document.forms.decosdatasearch,
		searchBox = form.elements['tx_decosdata[search]'],
		searchTimeout = null,
		searchDelay = 600,
		searchAtLength = 3,
		submitAllowed = true,
		lastSearchValue = '',
		// @LOW what if we can cache results through LocalStorage, or otherwise SessionStorage, with a lifetime of e.g. 1 hour?
		dataCache = [];

	var	dataElement = null,
		sectionElement = null,
		originalSection = null,
		overlayElement = null,
		pagingElements = [],
		countElements = [],
		templateItem = null,
		xhrPagingElement = null;

	// @LOW should improve this to be able to track multiple elements, with their own callbacks
	var onReach = (function() {
		var isEnabled = false,
		listenerAllowed = true,
		listener = function(event) {
			if (listenerAllowed) {
				listenerAllowed = false;
				// https://developer.mozilla.org/en-US/docs/Web/Events/scroll
				window.requestAnimationFrame(function() {
					areWeThereYet();
					listenerAllowed = true;
				});
			}
		},
		areWeThereYet = function() {
			if (__this.elementPositionReached(__this.element)) {
				__this.disable();
				onReachCallback();
			}
		},
		onReachCallback = null,
		__this = {
			element: null,
			enable: function(element, callback) {
				if (isEnabled) {
					console.warn('[decosdata] onReach-feature cannot be enabled twice!');
					return;
				}
				this.element = element;
				onReachCallback = callback;
				isEnabled = true;
				window.addEventListener('scroll', listener);
				window.addEventListener('resize', listener);
				// we may already be there, before any scrolling or resizing :o)
				areWeThereYet();
			},
			disable: function() {
				if (isEnabled) {
					window.removeEventListener('scroll', listener);
					window.removeEventListener('resize', listener);
					isEnabled = false;
				}
			},
			elementPositionReached: function(element) {
				var isquirks = document.compatMode !== 'BackCompat',
					page = isquirks ? document.documentElement : document.body,
					viewportBottomPos = page.scrollTop + ('innerHeight' in window ? window.innerHeight : page.clientHeight),
					elemTopPos = 0;
				while (element.offsetParent !== null) {
					elemTopPos += element.offsetTop;
					element = element.offsetParent;
				}
				// pos is counted from the top, so higher number means lower position vertically
				return viewportBottomPos >= elemTopPos;
			}
		};

		return __this;
	})();

	function resetSection() {
		console.info('[decosdata] resetting section');
		sectionElement.parentNode.insertBefore(originalSection, sectionElement);
		sectionElement.remove();
		sectionElement = originalSection;
		initNonSearchPaging();
		parseSection();
	}


	function xhrRequest(method, url, data, cacheKey, ondata, onend) {
		cacheKey += '-' + url;
		if (dataCache[cacheKey]) {
			console.info('[decosdata] data processing from cache');
			if (ondata !== null) {
				ondata(dataCache[cacheKey]);
			}
			if (onend !== null) {
				onend(dataCache[cacheKey]);
			}
			return;
		}

		// @TODO maybe we can elevate some caching to the browser with cache control headers?
		var xhr = new XMLHttpRequest();
		xhr.open(method, url, true);
		xhr.responseType = 'json';
		xhr.onload = function () {
			if (this.status !== 200) {
				console.error('[decosdata] ' + this.status + ': ' + this.statusText);
				console.info(this);
			}
			if (this.response) {
				var response = this.response;
				// IE doesn't automatically parse responseType json
				if (typeof(response) !== 'object') response = JSON.parse(response);
				if (response.data) {
					console.log(response);
					dataCache[cacheKey] = response;
					if (ondata !== null) {
						ondata(response);
					}
					console.info('[decosdata] data processing success')
				} else {
					console.error('[decosdata] no valid xhr json response');
					console.info(response);
				}
			} else {
				console.error('[decosdata] no valid xhr json response');
				console.info(this);
			}
		};
		xhr.onerror = function() {
			// @TODO test if getting here this also still does an onload
			console.error('[decosdata] failed to execute xhr');
			console.info(this);
		};
		xhr.ontimeout = function() {
			console.error('[decosdata] xhr timeout');
			console.info(this);
		};
		xhr.onloadend = function() {
			if (onend !== null) {
				onend(this);
			}
		};
		// @TODO errors should provide visual cue, and probably restore non-xhr process
		xhr.send(data);
	}


	function getData(data) {
		var newData = '';
		for (var item of data) {
			var contentElements = templateItem.getElementsByClassName('content');
			for (var content of contentElements) {
				if (content.dataset.cid && item['content' + content.dataset.cid]) {
					content.innerHTML = item['content' + content.dataset.cid];
				}
			}
			// @LOW what if I have multiple classes? using classList.toggle/add/remove is not supported on IE11
			templateItem.className = 'item item-' + item.id;
			newData += templateItem.outerHTML;
		}
		return newData;
	}


	function doSubmit() {
		if (!submitAllowed) {
			// disable visual cue
			searchBox.className = 'search-box';
			return false;
		}
		// disable form
		submitAllowed = false;

		// if same as last search: do nothing
		var searchValue = searchBox.value.trim();
		if (searchValue.localeCompare(lastSearchValue) === 0) {
			submitAllowed = true;
			// disable visual cue
			searchBox.className = 'search-box';
			return false;
		}
		lastSearchValue = searchValue;

		// make sure we don't keep any old onReach listeners active
		onReach.disable();

		// if empty search submitted: reset section
		if (searchValue.length === 0) {
			resetSection();
			submitAllowed = true;
			// disable visual cue
			searchBox.className = 'search-box';
			return false;
		}

		// visual cue
		dataElement.parentNode.insertBefore(overlayElement, dataElement);

		xhrRequest('POST', form.dataset.xhr, new FormData(form), searchValue, function(response) {
			initPagingElements();
			if (response.paging) {
				initCountChange(response.paging);
				initXhrPaging(response.paging);
			}
			dataElement.innerHTML = getData(response.data);
		}, function(response) {
			// disable visual cues
			searchBox.className = 'search-box';
			overlayElement.remove();
			// re-enable form
			submitAllowed = true;
		});
	}

	function searchListener(event) {
		var length = searchBox.value.trim().length;
		if (length === 0 || length >= searchAtLength) {
			// visual cue
			searchBox.className = 'search-box loader active';
			// clear previous search-in-wait if any
			clearTimeout(searchTimeout);
			// set delay
			searchTimeout = setTimeout(doSubmit, searchDelay);
		}
	}

	function parseSection2(sectionElement) {
		if (sectionElement !== null) {
			dataElement = sectionElement.querySelector('.data');
			if (dataElement !== null) {
				var itemElement = dataElement.querySelector('.item');
				if (itemElement !== null) {
					templateItem = itemElement.cloneNode(true);
				}
			}
		}
	}

	// @TODO unite this one and #2
	function parseSection() {
		if (sectionElement !== null) {
			originalSection = sectionElement.cloneNode(true);
			pagingElements = sectionElement.querySelectorAll('.pagebrowser .pagebrowser-navigation');
			countElements = sectionElement.querySelectorAll('.pagebrowser .resultcount');
			dataElement = sectionElement.querySelector('.data');
			if (dataElement !== null) {
				var itemElement = dataElement.querySelector('.item');
				if (itemElement !== null) {
					templateItem = itemElement.cloneNode(true);
				}
			}
			overlayElement = document.createElement('div');
			overlayElement.className = 'overlay loader active';
		}
	}

	function initPagingElements() {
		if (pagingElements.length > 0) {
			if (xhrPagingElement === null) {
				xhrPagingElement = pagingElements[0].parentNode.cloneNode();
			}
			Array.from(pagingElements).forEach(function(p) {
				p.remove();
			});
			pagingElements = [];
		} else if (xhrPagingElement === null) {
			xhrPagingElement = document.createElement('div');
		}
		xhrPagingElement.className = 'xhr-paging';
		delete xhrPagingElement.dataset.xhr;
		dataElement.parentNode.appendChild(xhrPagingElement);
	}

	function initCountChange(paging) {
		if ( countElements.length > 0 && paging.resultCount !== null ) {
			// replace counts
			var newCount = parseInt(paging.resultCount);
			Array.from(countElements).forEach(function(c) {
				c.innerHTML = c.innerHTML.replace(c.dataset.count, newCount);
				c.dataset.count = newCount;
			});
		}
	}

	// @TODO unite this one and #2
	function initXhrPaging(paging) {
		if (xhrPagingElement !== null) {
			if (paging.more !== false) {
				xhrPagingElement.className = 'xhr-paging loader inactive';
				xhrPagingElement.dataset.xhr = paging.more;
				onReach.enable(xhrPagingElement, function() {
					console.info('[decosdata] paging in reach');
					xhrPagingElement.className = 'xhr-paging loader active';
					xhrRequest('GET', xhrPagingElement.dataset.xhr, null, null, function(response) {
						dataElement.innerHTML += getData(response.data);
						if (response.paging) {
							initXhrPaging(response.paging);
						}
					}, null);
					// @LOW if paging does not exist, we can end up with an forever active xhr paging loader without an onend()
				});
			} else {
				xhrPagingElement.className = 'xhr-paging';
				delete xhrPagingElement.dataset.xhr;
			}
		}
	}

	function initXhrPaging2(xhrPagingElement, more) {
		if (xhrPagingElement !== null) {
			if (more !== false) {
				xhrPagingElement.className = 'xhr-paging loader inactive';
				xhrPagingElement.dataset.xhr = more;
				onReach.enable(xhrPagingElement, function() {
					console.info('[decosdata] paging in reach');
					xhrPagingElement.className = 'xhr-paging loader active';
					xhrRequest('GET', xhrPagingElement.dataset.xhr, null, null, function(response) {
						dataElement.innerHTML += getData(response.data);
						if (response.paging) {
							initXhrPaging2(xhrPagingElement, response.paging.more);
						}
					}, null);
					// @LOW if paging does not exist, we can end up with an forever active xhr paging loader without an onend()
				});
			} else {
				xhrPagingElement.className = 'xhr-paging';
				delete xhrPagingElement.dataset.xhr;
			}
		}
	}

	function initNonSearchPaging() {
		var xhrPagingElements = document.querySelectorAll('.tx-decosdata .section .xhr-paging');
		if (xhrPagingElements.length > 0) {
			Array.from(xhrPagingElements).forEach(function(x) {
				// @TODO if we have more than one, this is going to break (depends on config)
					// so best to make an object class which contains these for every xhr-paging instance
				xhrPagingElement = x;
				if (x.dataset.xhr) {
					sectionElement = null;
					dataElement = null;
					do {
						sectionElement = x.parentNode;
					} while ( !(sectionElement === null || sectionElement.classList.contains('section')) );
					parseSection2(sectionElement);
					if (dataElement !== null) {
						x.href = '#';
						// @TODO if we have more than one, this is going to break (depends on config)
							// so best to make an object class which contains these for every xhr-paging instance
						onReach.enable(x, function() {
							console.info('[decosdata] paging in reach');
							x.className = 'xhr-paging loader active';
							xhrRequest('GET', x.dataset.xhr, null, null, function(response) {
								dataElement.innerHTML += getData(response.data);
								if (response.paging) {
									initXhrPaging2(x, response.paging.more);
								}
							}, null);
							// @LOW if paging does not exist, we can end up with a forever active xhr paging loader
						});
					}
				}
			});
		}
	}


	// @TODO this method is such a hacky mess, so clean it up once time allows it!
	initNonSearchPaging();

	if (form.dataset.xhr) {
		// hide submit button
		form.elements['tx_decosdata[submit]'].className = 'search-submit invisible';
		searchBox.className = 'search-box full-width';

		// get item template
		if (form.dataset.section) {
			// @LOW replace once element.closest() is fully supported on every major browser
			sectionElement = document.querySelector('.tx-decosdata .section-' + form.dataset.section);
			parseSection();
		} else {
			// @TODO create a custom templateItem and dataElement?
			console.info('[decosdata] no section set for search xhr');
			return;
		}

		// @TODO should test if dataElement, overlayElement, templateItem exist
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			// if a submit occurs while a timeout is in progress..
			clearTimeout(searchTimeout);
			return doSubmit();
		});

		//searchBox.addEventListener('keypress', searchListener);
		//searchBox.addEventListener('cut', searchListener);
		//searchBox.addEventListener('paste', searchListener);
		searchBox.addEventListener('input', searchListener);
	}
})();