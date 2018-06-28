(function() {
	// @TODO minify
	// @TODO refactor into modules

	/*********************/
	/* BASIC XHR SUPPORT */
	/*********************/

	if (!window.XMLHttpRequest) {
		console.warn('[decosdata] no xhr support detected, cannot enable xhr features');
		return;
	}

	// @LOW what if we can cache results through LocalStorage, or otherwise SessionStorage, with a lifetime of e.g. 1 hour?
	var dataCache = [];

	/**
	 * XHR request
	 *
	 * @param string method
	 * @param string url
	 * @param FormData data
	 * @param string cacheKey
	 * @param object ondata
	 * @param object onend
	 * @return void
	 */
	function xhrRequest(method, url, data, cacheKey, ondata, onend) {
		var disallowOnEnd = true;

		// get from local cache?
		if (cacheKey !== null) {
			cacheKey += '-' + url;
			if (dataCache[cacheKey]) {
				console.info('[decosdata] data processing from cache');
				if (ondata !== null) disallowOnEnd = ondata(dataCache[cacheKey]) === false;
				if (!disallowOnEnd && onend !== null) onend(dataCache[cacheKey]);
				return;
			}
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
				let response = this.response;
				// IE doesn't automatically parse responseType json
				if (typeof(response) !== 'object') response = JSON.parse(response);
				if (response.data) {
					dataCache[cacheKey] = response;
					// if ondata returns false, disallowOnEnd becomes true
					if (ondata !== null) disallowOnEnd = ondata(response) === false;
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
			// only execute onend if disallowOnEnd is false
			if (!disallowOnEnd && onend !== null) onend(this);
		};
		// @TODO errors should provide visual cue, and probably restore non-xhr process
		xhr.send(data);
	}


	/*********************************************************************/
	/* IE11 POLYFILLS                                                    */
	/* --------------                                                    */
	/* Serving the Array.from().forEach() alternative to the unsupported */
	/* for .. of iteration of Array-like elements such as NodeLists.     */
	/*********************************************************************/

	// @see https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/remove
	// from:https://github.com/jserz/js_piece/blob/master/DOM/ChildNode/remove()/remove().md
	(function(arr) {
		arr.forEach(function(item) {
			if (item.hasOwnProperty('remove')) {
				return;
			}
			Object.defineProperty(item, 'remove', {
				configurable : true,
				enumerable : true,
				writable : true,
				value : function remove() {
					if (this.parentNode !== null)
						this.parentNode.removeChild(this);
				}
			});
		});
	})([Element.prototype, CharacterData.prototype, DocumentType.prototype]);

	// @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/from#Polyfill
	// Production steps of ECMA-262, Edition 6, 22.1.2.1
	if (!Array.from) {
		Array.from = (function() {
			var toStr = Object.prototype.toString;
			var isCallable = function(fn) {
				return typeof fn === 'function'
						|| toStr.call(fn) === '[object Function]';
			};
			var toInteger = function(value) {
				var number = Number(value);
				if (isNaN(number)) {
					return 0;
				}
				if (number === 0 || !isFinite(number)) {
					return number;
				}
				return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
			};
			var maxSafeInteger = Math.pow(2, 53) - 1;
			var toLength = function(value) {
				var len = toInteger(value);
				return Math.min(Math.max(len, 0), maxSafeInteger);
			};

			// The length property of the from method is 1.
			return function from(arrayLike/* , mapFn, thisArg */) {
				// 1. Let C be the this value.
				var C = this;

				// 2. Let items be ToObject(arrayLike).
				var items = Object(arrayLike);

				// 3. ReturnIfAbrupt(items).
				if (arrayLike == null) {
					throw new TypeError(
							'Array.from requires an array-like object - not null or undefined');
				}

				// 4. If mapfn is undefined, then let mapping be false.
				var mapFn = arguments.length > 1 ? arguments[1]
						: void undefined;
				var T;
				if (typeof mapFn !== 'undefined') {
					// 5. else
					// 5. a If IsCallable(mapfn) is false, throw a TypeError
					// exception.
					if (!isCallable(mapFn)) {
						throw new TypeError(
								'Array.from: when provided, the second argument must be a function');
					}

					// 5. b. If thisArg was supplied, let T be thisArg; else let
					// T be undefined.
					if (arguments.length > 2) {
						T = arguments[2];
					}
				}

				// 10. Let lenValue be Get(items, "length").
				// 11. Let len be ToLength(lenValue).
				var len = toLength(items.length);

				// 13. If IsConstructor(C) is true, then
				// 13. a. Let A be the result of calling the [[Construct]]
				// internal method
				// of C with an argument list containing the single item len.
				// 14. a. Else, Let A be ArrayCreate(len).
				var A = isCallable(C) ? Object(new C(len)) : new Array(len);

				// 16. Let k be 0.
				var k = 0;
				// 17. Repeat, while k < len… (also steps a - h)
				var kValue;
				while (k < len) {
					kValue = items[k];
					if (mapFn) {
						A[k] = typeof T === 'undefined' ? mapFn(kValue, k)
								: mapFn.call(T, kValue, k);
					} else {
						A[k] = kValue;
					}
					k += 1;
				}
				// 18. Let putStatus be Put(A, "length", len, true).
				A.length = len;
				// 20. Return A.
				return A;
			};
		}());
	}



	/****************/
	/* DATA PARSING */
	/****************/

	/**
	 * Formats raw data into usable HTML
	 *
	 * @param Element itemTemplate
	 * @param mixed data
	 * @return string
	 */
	function getDataHtml(itemTemplate, data) {
		var newData = '';
		data.forEach(function(item) {
			var contentElements = itemTemplate.getElementsByClassName('content');
			//for (let content of contentElements) {
			Array.from(contentElements).forEach(function(content) {
				if (content.dataset.cid && item['content' + content.dataset.cid]) {
					content.innerHTML = item['content' + content.dataset.cid];
				}
			});
			// @LOW what if I have multiple classes? using classList.toggle/add/remove is not supported on IE11
			itemTemplate.className = 'item item-' + item.id;
			newData += itemTemplate.outerHTML;
		});
		return newData;
	}

	/**
	 * Returns the element/item template from the datacontainer
	 *
	 * @param Element dataContainer
	 * @return Element
	 */
	function getItemTemplate(dataContainer) {
		if (dataContainer === null) throw 'no valid data container.'
		var itemElement = dataContainer.querySelector('.item');
		return itemElement === null ? null : itemElement.cloneNode(true);
	}


	/**************/
	/* XHR PAGING */
	/**************/

	// can track if given element is on screen
		// when calling enable(), we do explicitly disable all listeners,
		// but timing flukes are possible due to async behavior, so we stay vigilant
		// with additional validity checks on every level
	var OnReach = (function() {
		var isEnabled = false,
		listeners = {},
		areWeThereYet = function(id, onReachCallback) {
			// if yes, then we halt listener before calling the callback, however:
			// callback will not be called if the listener this call originates from was previously disabled
			if (__this.elementPositionReached(__this.element) && __this.haltListener(id)) onReachCallback();
		},
		__this = {
			id: null,
			element: null,
			enable: function(element, id, callback) {
				// disable all known listeners
				if (isEnabled) __this.disable();
				__this.id = id;
				__this.element = element;

				var listenerAllowed = true;
				var listener = function(event) {
					if (listenerAllowed) {
						listenerAllowed = false;
						// make sure this listener only runs as long as id remains valid
						if (id.localeCompare(__this.id) !== 0) return __this.haltListener(id);

						// https://developer.mozilla.org/en-US/docs/Web/Events/scroll
						window.requestAnimationFrame(function() {
							areWeThereYet(id, callback);
							listenerAllowed = true;
						});
					}
				};
				window.addEventListener('scroll', listener);
				window.addEventListener('resize', listener);
				listeners[id] = listener;
				isEnabled = true;

				// we may already be there, before any scrolling or resizing :o)
				areWeThereYet(id, callback);
			},
			haltListener: function(id) {
				if (listeners[id]) {
					window.removeEventListener('scroll', listeners[id]);
					window.removeEventListener('resize', listeners[id]);
					delete listeners[id];
					return true;
				}
				return false;
			},
			disable: function() {
				for (let id in listeners) __this.haltListener(id);
				isEnabled = false;
			},
			elementPositionReached: function(element) {
				var isquirks = document.compatMode !== 'BackCompat',
					// Edge likes to rebel I guess (against doing what it's supposed to do)
					page = isquirks && !/Edge/.test(navigator.userAgent) ? document.documentElement : document.body,
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

	/**
	 * XHR Pager Object constructor
	 *
	 * @param Element elem
	 * @return XhrPager
	 */
	function XhrPager(elem) {
		// initialize elem
		this.element = elem;
		this.more = false;
		this.first = false;
		if (elem.dataset.xhr) {
			this.more = elem.dataset.xhr;
			this.first = elem.dataset.xhr;
			delete elem.dataset.xhr;
		}
		if (elem.href) elem.href = '#';


		// determine section, dataContainer and itemTemplate
		var sectionContainer = null;
		do {
			sectionContainer = elem.parentNode;
		} while ( !(sectionContainer === null || sectionContainer.classList.contains('section')) );
		if (sectionContainer === null || !sectionContainer.dataset.section) {
			throw 'xhr pager is not in anything designated "section"';
		}
		this.section = sectionContainer.dataset.section;
		var dataContainer = sectionContainer.querySelector('.items'),
			itemTemplate = getItemTemplate(dataContainer);


		// every xhr pager gets its own OnReach instance
		this.onReach = Object.create(OnReach);


		// disables the xhr pager
		this.disable = function() {
			elem.className = 'xhr-paging';
			this.onReach.disable();
		};


		// enables the xhr pager
		this.enable = function(uri) {
			if (uri === false) {
				this.disable();
				return;
			}

			elem.className = 'xhr-paging loader inactive';
			this.more = uri;
			var _that = this;
			this.onReach.enable(elem, uri, function() {
				console.info('[decosdata] paging in reach');
				elem.className = 'xhr-paging loader active';
				xhrRequest('GET', uri, null, null, function(response) {
					// only execute if onReach has not changed id, in case of timing flukes
					if (uri.localeCompare(_that.onReach.id) !== 0) {
						console.warn('[decosdata] out-of-sync xhr paging request');
						console.info({'request': uri});
						return false;
					}
					dataContainer.innerHTML += getDataHtml(itemTemplate, response.data);
					if (response.paging) _that.enable(response.paging.more);
				}, null);
				// @LOW we can end up with a forever active xhr paging loader, if an unexpected error ensues
			});
		};


		// enable!
		this.enable(this.more);
	}

	var xhrPagingRegister = {};

	/**
	 * Initializes (pre-)existing XHR pagers
	 *
	 * @param Element container
	 * @return void
	 */
	function initXhrPagers(container) {
		var xhrPagingElements = container.querySelectorAll('.tx-decosdata .xhr-paging');
		if (xhrPagingElements.length > 0) {
			//for (let x of xhrPagingElements) {
			Array.from(xhrPagingElements).forEach(function(x) {
				try {
					var xhrPager = new XhrPager(x);
					// if xhrPager won't have a valid more-url, see if a previous incarnation exists and did
					if (!x.dataset.xhr && xhrPagingRegister[xhrPager.section]) {
						xhrPager.enable(xhrPagingRegister[xhrPager.section].first);
					}
					xhrPagingRegister[xhrPager.section] = xhrPager;
				} catch (e) {
					console.warn('[decosdata] ' + e);
				}
			});
		}
	}

	initXhrPagers(document);


	/**************/
	/* XHR SEARCH */
	/**************/

	/**
	 * Changes paging count number in count-elements
	 *
	 * @param array countElements
	 * @param object paging
	 * @return void
	 */
	function changePagingCount(countElements, paging) {
		if ( countElements.length > 0 && paging.resultCount !== null ) {
			// replace counts
			let newCount = parseInt(paging.resultCount);
			//for (let c of countElements) {
			Array.from(countElements).forEach(function(c) {
				c.innerHTML = c.innerHTML.replace(c.dataset.count, newCount);
				c.dataset.count = newCount;
			});
		}
	}

	/**
	 * SearchForm object constructor
	 *
	 * @param Element elem
	 * @param int searchDelay
	 * @param int searchAtLength
	 * @return void
	 */
	function SearchForm(elem, searchDelay, searchAtLength) {
		// initialize elem
		this.element = elem;
		if (!elem.dataset.xhr) throw 'searchform is not xhr-enabled';
		this.action = elem.dataset.xhr;
		delete elem.dataset.xhr;


		// hide submit button
		elem.elements['tx_decosdata[submit]'].className = 'search-submit invisible';


		// reset searchBox
		this.searchBox = elem.elements['tx_decosdata[search]'];
		// disable visual cues
		this.resetSearchBoxCues = function() {
			this.searchBox.className = 'search-box full-width';
		};
		this.resetSearchBoxCues();


		// initialize section container
		if (!elem.dataset.section) {
			// @TODO create a custom templateItem and dataElement so we can support xhr search on pages that initially show 0 data?
			throw 'no section set for search xhr';
		}
		// @LOW replace once element.closest() is fully supported on every major browser
		var sectionContainer = document.querySelector('.tx-decosdata .section-' + elem.dataset.section);
		if (sectionContainer === null) throw 'search form could not find its designated "section"';


		// is there already a XhrPager?
		this.xhrPager = xhrPagingRegister[elem.dataset.section] ? xhrPagingRegister[elem.dataset.section] : null;


		// create overlay for use in search submit
		var overlay = document.createElement('div');
		overlay.className = 'overlay loader active';


		// parse section
		var originalSection = null,
			pagingElements = [],
			countElements = [],
			dataContainer = null,
			itemTemplate = null;
		// parses sectionContainer
		function parseSection() {
			originalSection = sectionContainer.cloneNode(true);
			pagingElements = sectionContainer.querySelectorAll('.pagebrowser .pagebrowser-navigation');
			countElements = sectionContainer.querySelectorAll('.pagebrowser .resultcount');
			dataContainer = sectionContainer.querySelector('.items');
			itemTemplate = getItemTemplate(dataContainer);
		}
		parseSection();


		// clears paging elements
		function clearPagingElements() {
			if (pagingElements.length > 0) {
				//for (let p of pagingElements) {
				Array.from(pagingElements).forEach(function(p) {
					p.remove();
				});
				pagingElements = [];
			}
		}


		// resets section
		this.resetSection = function() {
			console.info('[decosdata] resetting section');
			sectionContainer.parentNode.insertBefore(originalSection, sectionContainer);
			sectionContainer.remove();
			sectionContainer = originalSection;
			initXhrPagers(sectionContainer);
			this.xhrPager = xhrPagingRegister[elem.dataset.section] ? xhrPagingRegister[elem.dataset.section] : null;
			parseSection();
		};


		// define internal search vars
		var submitAllowed = true,
			lastSearchValue = '',
			searchTimeout = null,
			_that = this;


		// submits form the xhr way, returns true if a request is triggered, false if not
		// needs to refer to _that everywhere because it's also called in EventListener contexts
		this.submit = function() {
			// disable submit while a submit is still in progress
			if (!submitAllowed) {
				// @LOW why disable cue if a submit was still in progress?
				_that.resetSearchBoxCues();
				return false;
			}
			submitAllowed = false;

			// if same as last search: do nothing
			var searchValue = _that.searchBox.value.trim();
			if (searchValue.localeCompare(lastSearchValue) === 0) {
				_that.resetSearchBoxCues();
				submitAllowed = true;
				return false;
			}
			lastSearchValue = searchValue;

			// make sure we don't continue with an active xhr pager
			if (_that.xhrPager) _that.xhrPager.disable();

			// if empty search submitted: reset section
			if (searchValue.length === 0) {
				_that.resetSection();
				_that.resetSearchBoxCues();
				submitAllowed = true;
				return false;
			}

			// visual cue
			dataContainer.parentNode.insertBefore(overlay, dataContainer);
			// search request
			xhrRequest('POST', _that.action, new FormData(_that.element), searchValue, function(response) {
				// only execute if this is still the last processed search
				if (searchValue.localeCompare(lastSearchValue) !== 0) {
					console.warn('[decosdata] out-of-sync xhr search request');
					console.info({'search': searchValue});
					// prevents onend callback
					return false;
				}
				// on data
				clearPagingElements();
				dataContainer.innerHTML = getDataHtml(itemTemplate, response.data);
				if (response.paging) {
					changePagingCount(countElements, response.paging);
					if (_that.xhrPager === null) {
						// create and register an xhr pager if none was bound to the searchform before
						let xhrPagingElement = document.createElement('div');
						dataContainer.parentNode.appendChild(xhrPagingElement);
						_that.xhrPager = new XhrPager(xhrPagingElement);
						xhrPagingRegister[_that.xhrPager.section] = _that.xhrPager;
					}
					_that.xhrPager.enable(response.paging.more);
				}
			}, function(response) {
				// on end
				overlay.remove();
				_that.resetSearchBoxCues();
				submitAllowed = true;
			});

			return true;
		};


		// replace default submit by our own
		elem.addEventListener('submit', function (e) {
			e.preventDefault();
			// clear previous search-in-wait if any
			clearTimeout(searchTimeout);
			// if the submit did not complete, don't pass this event on to other listeners
			if (! _that.submit() ) e.stopImmediatePropagation();
		});


		// submit on input
		function inputSearchListener(event) {
			var length = _that.searchBox.value.trim().length;
			if (length === 0 || length >= searchAtLength) {
				// visual cue
				_that.searchBox.className = 'search-box full-width loader active';
				// clear previous search-in-wait if any
				clearTimeout(searchTimeout);
				// set delay
				searchTimeout = setTimeout(_that.submit, searchDelay);
			}
		}
		// trigger on any type of input
		this.searchBox.addEventListener('input', inputSearchListener);
	}


	// currently we support only 1 searchform on a single page
	var searchForm = null;
	try {
		if (document.forms.decosdatasearch) searchForm = new SearchForm(document.forms.decosdatasearch, 600, 3);
	} catch (e) {
		console.warn('[decosdata] ' + e);
	}

})();